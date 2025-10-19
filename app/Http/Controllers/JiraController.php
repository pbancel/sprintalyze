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
}
