<?php

namespace App\Http\Controllers;

use App\Services\JiraClient;
use App\Services\JiraOAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
     */
    public function callback(Request $request)
    {
        // Validate state parameter (CSRF protection)
        $state = $request->query('state');
        $sessionState = $request->session()->get('jira_oauth_state');

        if (!$state || $state !== $sessionState) {
            Log::warning('Invalid OAuth state parameter');
            return redirect()->route('jira.connect')
                ->with('error', 'Invalid request. Please try again.');
        }

        // Clear the state from session
        $request->session()->forget('jira_oauth_state');

        // Get authorization code
        $code = $request->query('code');

        if (!$code) {
            Log::warning('No authorization code received');
            return redirect()->route('jira.connect')
                ->with('error', 'Authorization failed. No code received.');
        }

        // Exchange code for access token
        $tokenData = $this->oauthService->exchangeCodeForToken($code);

        if (!$tokenData) {
            Log::error('Failed to exchange authorization code for token');
            return redirect()->route('jira.connect')
                ->with('error', 'Failed to obtain access token from Jira.');
        }

        // Get accessible resources (Jira sites)
        $resources = $this->oauthService->getAccessibleResources($tokenData['access_token']);

        if (!$resources || empty($resources)) {
            Log::error('No accessible Jira resources found');
            return redirect()->route('jira.connect')
                ->with('error', 'No Jira sites found for your account.');
        }

        // Store connection for each accessible site
        $userId = Auth::id();
        $connectionsCreated = 0;

        foreach ($resources as $resource) {
            try {
                $this->oauthService->storeConnection($userId, $tokenData, $resource);
                $connectionsCreated++;
            } catch (\Exception $e) {
                Log::error('Failed to store Jira connection', [
                    'resource' => $resource,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($connectionsCreated === 0) {
            return redirect()->route('jira.connect')
                ->with('error', 'Failed to save Jira connection.');
        }

        return redirect()->route('jira.connect')
            ->with('success', "Successfully connected to {$connectionsCreated} Jira site(s)!");
    }
}
