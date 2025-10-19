<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\JiraClient;
use App\Services\JiraOAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class JiraController extends Controller
{
    protected JiraClient $jiraClient;
    protected JiraOAuthService $oauthService;

    public function __construct(JiraClient $jiraClient, JiraOAuthService $oauthService)
    {
        $this->jiraClient = $jiraClient;
        $this->oauthService = $oauthService;
    }

    public function connect()
    {
        return view('jira-connect');
    }

    /**
     * Test the Jira connection
     */
    public function testConnection()
    {
        $result = $this->jiraClient->testConnection();

        return response()->json($result);
    }

    /**
     * Get all Jira projects
     */
    public function getProjects()
    {
        $result = $this->jiraClient->getProjects();

        return response()->json($result);
    }

    /**
     * Get a specific project
     */
    public function getProject(string $projectKey)
    {
        $result = $this->jiraClient->getProject($projectKey);

        return response()->json($result);
    }

    /**
     * Search issues
     */
    public function searchIssues(Request $request)
    {
        $jql = $request->input('jql', '');
        $maxResults = $request->input('maxResults', 50);
        $startAt = $request->input('startAt', 0);

        $result = $this->jiraClient->searchIssues($jql, $maxResults, $startAt);

        return response()->json($result);
    }

    /**
     * Get board sprints
     */
    public function getBoardSprints(int $boardId, Request $request)
    {
        $state = $request->input('state');
        $result = $this->jiraClient->getBoardSprints($boardId, $state);

        return response()->json($result);
    }

    /**
     * Get sprint issues
     */
    public function getSprintIssues(int $sprintId)
    {
        $result = $this->jiraClient->getSprintIssues($sprintId);

        return response()->json($result);
    }

    /**
     * Initiate OAuth authorization
     */
    public function authorize(Request $request)
    {
        $state = $this->oauthService->generateState();

        // Store state in session for CSRF validation
        $request->session()->put('jira_oauth_state', $state);

        $authUrl = $this->oauthService->getAuthorizationUrl($state);

        return redirect($authUrl);
    }

    /**
     * OAuth callback handler for Jira
     * Creates/finds user and auto-logins them
     */
    public function callback(Request $request)
    {
        // Validate state parameter (CSRF protection)
        $state = $request->query('state');
        $sessionState = $request->session()->get('jira_oauth_state');

        if (!$state || $state !== $sessionState) {
            Log::warning('Invalid OAuth state parameter');
            return redirect('/')
                ->with('error', 'Invalid request. Please try again.');
        }

        // Clear the state from session
        $request->session()->forget('jira_oauth_state');

        // Get authorization code
        $code = $request->query('code');

        if (!$code) {
            Log::warning('No authorization code received');
            return redirect('/')
                ->with('error', 'Authorization failed. No code received.');
        }

        // Exchange code for access token
        $tokenData = $this->oauthService->exchangeCodeForToken($code);

        if (!$tokenData) {
            Log::error('Failed to exchange authorization code for token');
            return redirect('/')
                ->with('error', 'Failed to obtain access token from Jira.');
        }

        // Get accessible resources (Jira sites)
        $resources = $this->oauthService->getAccessibleResources($tokenData['access_token']);

        if (!$resources || empty($resources)) {
            Log::error('No accessible Jira resources found');
            return redirect('/')
                ->with('error', 'No Jira sites found for your account.');
        }

        // Get the first resource (primary Jira site)
        $primaryResource = $resources[0];

        // Get Jira user info
        $jiraUser = $this->oauthService->getJiraUserInfo($tokenData['access_token'], $primaryResource['id']);

        if (!$jiraUser || !isset($jiraUser['emailAddress'])) {
            Log::error('Failed to get Jira user info');
            return redirect('/')
                ->with('error', 'Failed to retrieve your Jira user information.');
        }

        // Find or create Laravel user based on Jira email
        $user = User::firstOrCreate(
            ['email' => $jiraUser['emailAddress']],
            [
                'name' => $jiraUser['displayName'] ?? explode('@', $jiraUser['emailAddress'])[0],
                'password' => Hash::make(Str::random(32)), // Random password, user will use Jira OAuth
            ]
        );

        // Store Jira connections for this user
        $connectionsCreated = 0;
        foreach ($resources as $resource) {
            try {
                $this->oauthService->storeConnection($user->id, $tokenData, $resource);
                $connectionsCreated++;
            } catch (\Exception $e) {
                Log::error('Failed to store Jira connection', [
                    'resource' => $resource,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($connectionsCreated === 0) {
            return redirect('/')
                ->with('error', 'Failed to save Jira connection.');
        }

        // Log the user in
        Auth::login($user, true);

        Log::info('User authenticated via Jira OAuth', [
            'user_id' => $user->id,
            'email' => $user->email,
            'connections' => $connectionsCreated,
        ]);

        return redirect()->route('dashboard')
            ->with('success', "Welcome! You've been logged in successfully.");
    }
}
