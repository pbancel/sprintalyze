<?php

namespace App\Http\Controllers;

use App\Models\JiraConnection;
use App\Models\MonitoredIssue;
use App\Services\JiraClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ManageIssuesController extends Controller
{
    protected JiraClient $jiraClient;

    public function __construct(JiraClient $jiraClient)
    {
        $this->jiraClient = $jiraClient;
    }

    /**
     * Display the manage issues page
     */
    public function index()
    {
        $user = Auth::user();

        // Get the user's Jira connections
        $connections = $user->jiraConnections()->get();

        // Get the first connection by default
        $activeConnection = $connections->first();

        if (!$activeConnection) {
            return redirect()->route('dashboard')
                ->with('error', 'Please connect to Jira first.');
        }

        return view('manage-issues.index', compact('connections', 'activeConnection'));
    }

    /**
     * DataTable endpoint for available Jira issues
     */
    public function availableDatatable(Request $request)
    {
        $connectionId = $request->input('connection_id');

        try {
            // Verify the connection belongs to the authenticated user
            $connection = JiraConnection::where('user_id', Auth::id())
                ->where('id', $connectionId)
                ->firstOrFail();

            // Set connection (this will automatically refresh token if needed)
            $this->jiraClient->setConnection($connection);

            // Get monitored issue keys to filter them out
            $monitoredIssueKeys = MonitoredIssue::where('jira_connection_id', $connectionId)
                ->pluck('issue_key')
                ->toArray();

            // Build JQL to get recent Epic issues (last 30 days, ordered by updated date)
            $jql = 'type = Epic AND updated >= -30d ORDER BY updated DESC';

            Log::info('Fetching issues with JQL: ' . $jql);

            // Fetch issues from Jira
            $result = $this->jiraClient->searchIssues($jql, 100, 0);

            Log::info('Jira search result', ['success' => $result['success'], 'has_data' => isset($result['data'])]);
        } catch (\Exception $e) {
            Log::error('Error in available issues datatable: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'draw' => intval($request->input('draw', 0)),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Failed to load issues: ' . $e->getMessage()
            ]);
        }

        if (!$result['success']) {
            Log::error('Jira search failed', ['result' => $result]);
            return response()->json([
                'draw' => intval($request->input('draw', 0)),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $result['message'] ?? 'Failed to fetch issues from Jira'
            ]);
        }

        // Filter and transform issues
        $issues = collect($result['data']['issues'] ?? [])
            ->filter(function ($issue) use ($monitoredIssueKeys) {
                // Show issues not already monitored
                return !in_array($issue['key'], $monitoredIssueKeys);
            })
            ->values();

        $totalRecords = $issues->count();

        // Apply search filter if provided
        $searchValue = $request->input('search.value', '');
        if (!empty($searchValue)) {
            $issues = $issues->filter(function ($issue) use ($searchValue) {
                $searchLower = strtolower($searchValue);
                return str_contains(strtolower($issue['key'] ?? ''), $searchLower) ||
                       str_contains(strtolower($issue['fields']['summary'] ?? ''), $searchLower);
            })->values();
        }

        $filteredRecords = $issues->count();

        // Apply sorting
        if ($request->has('order')) {
            $orderColumnIndex = $request->input('order.0.column', 0);
            $orderDir = $request->input('order.0.dir', 'asc');

            // Map column indexes to data fields
            $columnMap = [
                0 => 'key',      // Issue Key
                1 => 'summary',  // Summary
                2 => null        // Action column (not sortable)
            ];

            if (isset($columnMap[$orderColumnIndex]) && $columnMap[$orderColumnIndex] !== null) {
                $sortField = $columnMap[$orderColumnIndex];
                $issues = $issues->sortBy(function ($issue) use ($sortField) {
                    if ($sortField === 'key') {
                        return $issue['key'];
                    } elseif ($sortField === 'summary') {
                        return $issue['fields']['summary'] ?? '';
                    }
                    return '';
                }, SORT_REGULAR, $orderDir === 'desc')->values();
            }
        }

        // Apply pagination
        $start = intval($request->input('start', 0));
        $length = intval($request->input('length', 10));
        $issues = $issues->slice($start, $length)->values();

        // Transform data for DataTable
        $data = [];
        foreach ($issues as $issue) {
            $issueKey = htmlspecialchars($issue['key']);
            $summary = htmlspecialchars($issue['fields']['summary'] ?? 'No summary');
            $issueId = $issue['id'];
            $status = $issue['fields']['status']['name'] ?? 'Unknown';
            $issueType = $issue['fields']['issuetype']['name'] ?? null;

            $assignee = $issue['fields']['assignee'] ?? null;
            $assigneeId = $assignee['accountId'] ?? null;
            $assigneeName = $assignee['displayName'] ?? null;

            // Get first letter for icon
            $firstLetter = strtoupper(substr($issueKey, 0, 1));

            // Format issue key with icon
            $issueKeyDisplay = '<div class="issue-info-cell">' .
                              '<div class="issue-icon-small">' . $firstLetter . '</div>' .
                              '<div class="issue-details-cell">' .
                              '<strong>' . $issueKey . '</strong>' .
                              '</div></div>';

            // Format summary
            $summaryDisplay = '<small>' . $summary . '</small>';

            // Add button (disabled for now)
            $actionButton = '<button class="btn btn-sm btn-success add-issue-btn" ' .
                          'data-issue-id="' . $issueId . '" ' .
                          'data-issue-key="' . $issueKey . '" ' .
                          'data-summary="' . htmlspecialchars($issue['fields']['summary'] ?? '') . '" ' .
                          'data-status="' . htmlspecialchars($status) . '" ' .
                          'data-issue-type="' . htmlspecialchars($issueType ?? '') . '" ' .
                          'data-assignee-id="' . htmlspecialchars($assigneeId ?? '') . '" ' .
                          'data-assignee-name="' . htmlspecialchars($assigneeName ?? '') . '" ' .
                          'disabled>' .
                          '<i class="fa fa-plus"></i> Add' .
                          '</button>';

            $row = [
                $issueKeyDisplay,
                $summaryDisplay,
                $actionButton
            ];

            $data[] = $row;
        }

        // Return standard DataTable JSON response
        return response()->json([
            'draw' => intval($request->input('draw', 0)),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data
        ]);
    }
}
