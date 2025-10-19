<?php

namespace App\Services;

use App\Models\JiraConnection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class JiraClient
{
    protected ?JiraConnection $connection = null;
    protected ?string $accessToken = null;
    protected ?string $cloudId = null;

    /**
     * Set the Jira connection to use for API calls
     */
    public function setConnection(JiraConnection $connection): self
    {
        $this->connection = $connection;
        $this->accessToken = $connection->decrypted_access_token;
        $this->cloudId = $connection->cloud_id;
        return $this;
    }

    /**
     * Get the base HTTP client with authentication
     */
    protected function client()
    {
        if (!$this->accessToken || !$this->cloudId) {
            throw new \Exception('Jira connection not set. Call setConnection() first.');
        }

        return Http::withToken($this->accessToken)
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->baseUrl("https://api.atlassian.com/ex/jira/{$this->cloudId}");
    }

    /**
     * Test the connection to Jira
     */
    public function testConnection(): array
    {
        try {
            $response = $this->client()->get('/rest/api/3/myself');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Connection successful',
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => 'Connection failed: ' . $response->status(),
                'data' => $response->json()
            ];
        } catch (\Exception $e) {
            Log::error('Jira connection failed: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Connection error: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Get all projects
     */
    public function getProjects(): array
    {
        try {
            $response = $this->client()->get('/rest/api/3/project');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to fetch projects',
                'data' => null
            ];
        } catch (\Exception $e) {
            Log::error('Failed to fetch Jira projects: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Get project by key
     */
    public function getProject(string $projectKey): array
    {
        try {
            $response = $this->client()->get("/rest/api/3/project/{$projectKey}");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => 'Project not found',
                'data' => null
            ];
        } catch (\Exception $e) {
            Log::error('Failed to fetch Jira project: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Search for issues using JQL
     */
    public function searchIssues(string $jql, int $maxResults = 50, int $startAt = 0): array
    {
        try {
            $response = $this->client()->post('/rest/api/3/search', [
                'jql' => $jql,
                'maxResults' => $maxResults,
                'startAt' => $startAt,
                'fields' => ['summary', 'status', 'assignee', 'created', 'updated']
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => 'Search failed',
                'data' => null
            ];
        } catch (\Exception $e) {
            Log::error('Jira search failed: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Get issue by key
     */
    public function getIssue(string $issueKey): array
    {
        try {
            $response = $this->client()->get("/rest/api/3/issue/{$issueKey}");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => 'Issue not found',
                'data' => null
            ];
        } catch (\Exception $e) {
            Log::error('Failed to fetch Jira issue: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Create an issue
     */
    public function createIssue(array $issueData): array
    {
        try {
            $response = $this->client()->post('/rest/api/3/issue', $issueData);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to create issue',
                'data' => $response->json()
            ];
        } catch (\Exception $e) {
            Log::error('Failed to create Jira issue: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Update an issue
     */
    public function updateIssue(string $issueKey, array $updateData): array
    {
        try {
            $response = $this->client()->put("/rest/api/3/issue/{$issueKey}", $updateData);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Issue updated successfully'
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to update issue',
                'data' => $response->json()
            ];
        } catch (\Exception $e) {
            Log::error('Failed to update Jira issue: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Get sprints for a board
     */
    public function getBoardSprints(int $boardId, string $state = null): array
    {
        try {
            $url = "/rest/agile/1.0/board/{$boardId}/sprint";

            if ($state) {
                $url .= "?state={$state}";
            }

            $response = $this->client()->get($url);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to fetch sprints',
                'data' => null
            ];
        } catch (\Exception $e) {
            Log::error('Failed to fetch Jira sprints: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Get issues for a sprint
     */
    public function getSprintIssues(int $sprintId): array
    {
        try {
            $response = $this->client()->get("/rest/agile/1.0/sprint/{$sprintId}/issue");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to fetch sprint issues',
                'data' => null
            ];
        } catch (\Exception $e) {
            Log::error('Failed to fetch sprint issues: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Get all users accessible in Jira
     */
    public function getUsers(int $startAt = 0, int $maxResults = 100): array
    {
        try {
            $response = $this->client()->get('/rest/api/3/users/search', [
                'startAt' => $startAt,
                'maxResults' => $maxResults,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to fetch users',
                'data' => null
            ];
        } catch (\Exception $e) {
            Log::error('Failed to fetch Jira users: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Search for users by query
     */
    public function searchUsers(string $query, int $maxResults = 50): array
    {
        try {
            $response = $this->client()->get('/rest/api/3/user/search', [
                'query' => $query,
                'maxResults' => $maxResults,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to search users',
                'data' => null
            ];
        } catch (\Exception $e) {
            Log::error('Failed to search Jira users: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
    }
}
