<?php

namespace App\Http\Controllers;

use App\Services\JiraClient;
use Illuminate\Http\Request;

class JiraController extends Controller
{
    protected JiraClient $jiraClient;

    public function __construct(JiraClient $jiraClient)
    {
        $this->jiraClient = $jiraClient;
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
     * OAuth callback handler for Jira
     *
     * This method handles the OAuth 2.0 callback from Jira after user authorization.
     *
     * What should be done here:
     * 1. Receive the authorization code from the query parameter
     * 2. Exchange the authorization code for an access token
     * 3. Store the access token and refresh token in the database (associated with the user)
     * 4. Optionally: Fetch and store user's Jira site information (cloudId, site URL)
     * 5. Redirect the user to a success page or back to jira-connect with success message
     */
    public function callback(Request $request)
    {
        // TODO: Implement OAuth 2.0 callback logic
        //
        // Expected query parameters:
        // - code: The authorization code from Jira
        // - state: CSRF protection token (should match what was sent in the authorization request)
        //
        // Steps to implement:
        // 1. Validate the state parameter
        // 2. Exchange authorization code for access token
        // 3. Store tokens in database
        // 4. Fetch accessible resources (Jira sites)
        // 5. Store site information
        // 6. Redirect user with success message

        return view('jira-callback', [
            'code' => $request->query('code'),
            'state' => $request->query('state')
        ]);
    }
}
