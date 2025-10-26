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

            // Add button
            $actionButton = '<button class="btn btn-sm btn-success add-issue-btn" ' .
                          'data-issue-id="' . $issueId . '" ' .
                          'data-issue-key="' . $issueKey . '" ' .
                          'data-summary="' . htmlspecialchars($issue['fields']['summary'] ?? '') . '" ' .
                          'data-status="' . htmlspecialchars($status) . '" ' .
                          'data-issue-type="' . htmlspecialchars($issueType ?? '') . '" ' .
                          'data-assignee-id="' . htmlspecialchars($assigneeId ?? '') . '" ' .
                          'data-assignee-name="' . htmlspecialchars($assigneeName ?? '') . '">' .
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

    /**
     * Add an issue to monitoring
     */
    public function store(Request $request)
    {
        Log::info('Store issue request received', ['data' => $request->all()]);

        try {
            $request->validate([
                'connection_id' => 'required|exists:jira_connections,id',
                'issue_id' => 'required|string',
                'issue_key' => 'required|string',
                'summary' => 'required|string',
                'status' => 'nullable|string',
                'issue_type' => 'nullable|string',
                'assignee_id' => 'nullable|string',
                'assignee_name' => 'nullable|string',
            ]);

            // Verify the connection belongs to the authenticated user
            $connection = JiraConnection::where('user_id', Auth::id())
                ->where('id', $request->connection_id)
                ->firstOrFail();

            // Check if issue is already being monitored
            $existing = MonitoredIssue::where('jira_connection_id', $connection->id)
                ->where('issue_key', $request->issue_key)
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'This issue is already being monitored',
                ], 422);
            }

            Log::info('Creating monitored issue', [
                'connection_id' => $connection->id,
                'issue_key' => $request->issue_key,
            ]);

            $monitoredIssue = MonitoredIssue::create([
                'jira_connection_id' => $connection->id,
                'issue_id' => $request->issue_id,
                'issue_key' => $request->issue_key,
                'summary' => $request->summary,
                'status' => $request->status,
                'issue_type' => $request->issue_type,
                'assignee_id' => $request->assignee_id,
                'assignee_name' => $request->assignee_name,
                'is_active' => true,
            ]);

            Log::info('Monitored issue created successfully', ['issue_id' => $monitoredIssue->id]);

            return response()->json([
                'success' => true,
                'message' => 'Issue added to monitoring successfully',
                'issue' => $monitoredIssue,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed: ' . json_encode($e->errors()));
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to add monitored issue: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to add issue to monitoring: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DataTable endpoint for monitored issues
     */
    public function monitoredDatatable(Request $request)
    {
        $connectionId = $request->input('connection_id');

        try {
            // Verify the connection belongs to the authenticated user
            $connection = JiraConnection::where('user_id', Auth::id())
                ->where('id', $connectionId)
                ->firstOrFail();

            // Build query for monitored issues
            $query = MonitoredIssue::where('jira_connection_id', $connectionId);

            // Apply search filter if provided
            $searchValue = $request->input('search.value', '');
            if (!empty($searchValue)) {
                $query->where(function ($q) use ($searchValue) {
                    $q->where('issue_key', 'LIKE', '%' . $searchValue . '%')
                      ->orWhere('summary', 'LIKE', '%' . $searchValue . '%');
                });
            }

            // Get total and filtered counts
            $totalRecords = MonitoredIssue::where('jira_connection_id', $connectionId)->count();
            $filteredRecords = $query->count();

            // Apply sorting
            if ($request->has('order')) {
                $orderColumnIndex = $request->input('order.0.column', 0);
                $orderDir = $request->input('order.0.dir', 'asc');

                // Map column indexes to database fields
                $columnMap = [
                    0 => 'issue_key',  // Issue Key
                    1 => 'is_active',  // Status
                    2 => null          // Actions (not sortable)
                ];

                if (isset($columnMap[$orderColumnIndex]) && $columnMap[$orderColumnIndex] !== null) {
                    $query->orderBy($columnMap[$orderColumnIndex], $orderDir);
                }
            } else {
                // Default sorting
                $query->orderBy('issue_key', 'asc');
            }

            // Apply pagination
            if ($request->has('start') && $request->has('length')) {
                $query->offset($request->input('start'))
                      ->limit($request->input('length'));
            }

            $monitoredIssues = $query->get();

            // Transform data for DataTable
            $data = [];
            foreach ($monitoredIssues as $issue) {
                $issueKey = htmlspecialchars($issue->issue_key);
                $summary = htmlspecialchars($issue->summary ?? 'No summary');
                $issueId = $issue->id;
                $isActive = $issue->is_active;

                // Get first letter for icon
                $firstLetter = strtoupper(substr($issueKey, 0, 1));

                // Format issue key with icon and summary
                $issueKeyDisplay = '<div class="issue-info-cell">' .
                                  '<div class="issue-icon-small">' . $firstLetter . '</div>' .
                                  '<div class="issue-details-cell">' .
                                  '<strong>' . $issueKey . '</strong>' .
                                  '<br><small class="text-muted">' . $summary . '</small>' .
                                  '</div></div>';

                // Status badge
                $statusBadge = '<div class="status-cell">' .
                              ($isActive
                                  ? '<span class="badge-status badge-active">Active</span>'
                                  : '<span class="badge-status badge-inactive">Inactive</span>') .
                              '</div>';

                // Action buttons
                $actionButtons = '<div class="btn-group">' .
                               '<button class="btn btn-sm btn-warning toggle-issue-status" data-id="' . $issueId . '" title="Toggle Status">' .
                               '<i class="fa fa-power-off"></i>' .
                               '</button>' .
                               '<button class="btn btn-sm btn-danger remove-issue" data-id="' . $issueId . '" title="Remove">' .
                               '<i class="fa fa-trash"></i>' .
                               '</button>' .
                               '</div>';

                $row = [
                    $issueKeyDisplay,
                    $statusBadge,
                    $actionButtons
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
        } catch (\Exception $e) {
            Log::error('Error in monitored issues datatable: ' . $e->getMessage());
            return response()->json([
                'draw' => intval($request->input('draw', 0)),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Failed to load monitored issues: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Remove an issue from monitoring
     */
    public function destroy(Request $request, int $id)
    {
        try {
            $monitoredIssue = MonitoredIssue::whereHas('jiraConnection', function ($query) {
                $query->where('user_id', Auth::id());
            })->findOrFail($id);

            $monitoredIssue->delete();

            return response()->json([
                'success' => true,
                'message' => 'Issue removed from monitoring successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to remove monitored issue: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to remove issue: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle issue monitoring status
     */
    public function toggleStatus(Request $request, int $id)
    {
        try {
            $monitoredIssue = MonitoredIssue::whereHas('jiraConnection', function ($query) {
                $query->where('user_id', Auth::id());
            })->findOrFail($id);

            $monitoredIssue->update([
                'is_active' => !$monitoredIssue->is_active,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Issue monitoring status updated',
                'is_active' => $monitoredIssue->is_active,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to toggle issue status: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update status: ' . $e->getMessage(),
            ], 500);
        }
    }
}
