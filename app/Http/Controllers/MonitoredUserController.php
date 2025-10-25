<?php

namespace App\Http\Controllers;

use App\Models\JiraConnection;
use App\Models\MonitoredUser;
use App\Services\JiraClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MonitoredUserController extends Controller
{
    protected JiraClient $jiraClient;

    public function __construct(JiraClient $jiraClient)
    {
        $this->jiraClient = $jiraClient;
    }

    /**
     * Display the monitored users management page
     */
    public function index()
    {
        $user = Auth::user();

        // Get the user's Jira connections
        $connections = $user->jiraConnections()->get();

        // Get the first connection by default (you can add UI to switch between connections later)
        $activeConnection = $connections->first();

        if (!$activeConnection) {
            return redirect()->route('jira.connect')
                ->with('error', 'Please connect to Jira first.');
        }

        return view('monitored-users.index', compact('connections', 'activeConnection'));
    }

    /**
     * Fetch available users from Jira
     */
    public function fetchUsers(Request $request)
    {
        $connectionId = $request->input('connection_id');
        $query = $request->input('query', '');

        $connection = JiraConnection::where('user_id', Auth::id())
            ->where('id', $connectionId)
            ->firstOrFail();

        $this->jiraClient->setConnection($connection);

        if ($query) {
            $result = $this->jiraClient->searchUsers($query);
        } else {
            $result = $this->jiraClient->getUsers();
        }

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Failed to fetch users',
            ], 500);
        }

        // Filter out already monitored users
        $monitoredAccountIds = MonitoredUser::where('jira_connection_id', $connectionId)
            ->pluck('jira_account_id')
            ->toArray();

        $availableUsers = collect($result['data'])->map(function ($user) use ($monitoredAccountIds) {
            return [
                'accountId' => $user['accountId'],
                'displayName' => $user['displayName'],
                'emailAddress' => $user['emailAddress'] ?? null,
                'avatarUrls' => $user['avatarUrls'] ?? null,
                'active' => $user['active'] ?? true,
                'isMonitored' => in_array($user['accountId'], $monitoredAccountIds),
            ];
        })->filter(function ($user) {
            return $user['active']; // Only show active users
        })->values();

        return response()->json([
            'success' => true,
            'users' => $availableUsers,
        ]);
    }

    /**
     * Add a user to monitoring
     */
    public function store(Request $request)
    {
        Log::info('Store request received', ['data' => $request->all()]);

        try {
            $request->validate([
                'connection_id' => 'required|exists:jira_connections,id',
                'jira_account_id' => 'required|string',
                'display_name' => 'required|string',
                'avatar_url' => 'nullable|string',
            ]);

            // Verify the connection belongs to the authenticated user
            $connection = JiraConnection::where('user_id', Auth::id())
                ->where('id', $request->connection_id)
                ->firstOrFail();

            Log::info('Creating monitored user', [
                'connection_id' => $connection->id,
                'jira_account_id' => $request->jira_account_id,
            ]);

            $monitoredUser = MonitoredUser::create([
                'jira_connection_id' => $connection->id,
                'jira_account_id' => $request->jira_account_id,
                'display_name' => $request->display_name,
                'avatar_url' => $request->avatar_url,
                'is_active' => true,
            ]);

            Log::info('Monitored user created successfully', ['user_id' => $monitoredUser->id]);

            return response()->json([
                'success' => true,
                'message' => 'User added to monitoring successfully',
                'user' => $monitoredUser,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed: ' . json_encode($e->errors()));
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to add monitored user: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to add user to monitoring: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove a user from monitoring
     */
    public function destroy(Request $request, int $id)
    {
        $monitoredUser = MonitoredUser::whereHas('jiraConnection', function ($query) {
            $query->where('user_id', Auth::id());
        })->findOrFail($id);

        $monitoredUser->delete();

        return response()->json([
            'success' => true,
            'message' => 'User removed from monitoring successfully',
        ]);
    }

    /**
     * Toggle user monitoring status
     */
    public function toggleStatus(Request $request, int $id)
    {
        $monitoredUser = MonitoredUser::whereHas('jiraConnection', function ($query) {
            $query->where('user_id', Auth::id());
        })->findOrFail($id);

        $monitoredUser->update([
            'is_active' => !$monitoredUser->is_active,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User monitoring status updated',
            'is_active' => $monitoredUser->is_active,
        ]);
    }

    /**
     * DataTable endpoint for available Jira users
     */
    public function datatable(Request $request)
    {
        $connectionId = $request->input('connection_id');

        try {
            // Verify the connection belongs to the authenticated user
            $connection = JiraConnection::where('user_id', Auth::id())
                ->where('id', $connectionId)
                ->firstOrFail();

            // Set connection (this will automatically refresh token if needed)
            $this->jiraClient->setConnection($connection);

            // Fetch users from Jira
            $result = $this->jiraClient->getUsers();
        } catch (\Exception $e) {
            Log::error('Error in datatable endpoint: ' . $e->getMessage());
            return response()->json([
                'draw' => intval($request->input('draw', 0)),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Failed to load users: ' . $e->getMessage()
            ]);
        }

        if (!$result['success']) {
            return response()->json([
                'draw' => intval($request->input('draw', 0)),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $result['message'] ?? 'Failed to fetch users from Jira'
            ]);
        }

        // Get monitored account IDs to filter them out
        $monitoredAccountIds = MonitoredUser::where('jira_connection_id', $connectionId)
            ->pluck('jira_account_id')
            ->toArray();

        // Filter and transform users - only show users not already monitored
        $users = collect($result['data'])
            ->filter(function ($user) use ($monitoredAccountIds) {
                // Show all active users that are not already monitored
                return ($user['active'] ?? true) && !in_array($user['accountId'], $monitoredAccountIds);
            })
            ->values();

        $totalRecords = $users->count();

        // Apply search filter if provided
        $searchValue = $request->input('search.value', '');
        if (!empty($searchValue)) {
            $users = $users->filter(function ($user) use ($searchValue) {
                $searchLower = strtolower($searchValue);
                return str_contains(strtolower($user['displayName'] ?? ''), $searchLower) ||
                       str_contains(strtolower($user['emailAddress'] ?? ''), $searchLower);
            })->values();
        }

        $filteredRecords = $users->count();

        // Apply sorting
        if ($request->has('order')) {
            $orderColumnIndex = $request->input('order.0.column', 0);
            $orderDir = $request->input('order.0.dir', 'asc');

            // Map column indexes to data fields
            $columnMap = [
                0 => 'displayName',  // Full name
                1 => null,           // Status column (not sortable)
                2 => null            // Action column (not sortable)
            ];

            if (isset($columnMap[$orderColumnIndex]) && $columnMap[$orderColumnIndex] !== null) {
                $sortField = $columnMap[$orderColumnIndex];
                $users = $orderDir === 'asc'
                    ? $users->sortBy($sortField)->values()
                    : $users->sortByDesc($sortField)->values();
            }
        }

        // Apply pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        $paginatedUsers = $users->slice($start, $length)->values();

        // Transform data for DataTable
        $data = [];
        foreach ($paginatedUsers as $user) {
            $avatarUrl = $user['avatarUrls']['48x48'] ?? $user['avatarUrls']['32x32'] ?? '';
            $displayName = htmlspecialchars($user['displayName'] ?? 'Unknown');
            $accountId = htmlspecialchars($user['accountId']);
            $isActive = $user['active'] ?? true;

            // Format full name with avatar
            $fullName = '<div class="user-info-cell">' .
                       ($avatarUrl ? '<img src="' . $avatarUrl . '" class="user-avatar-small" alt="' . $displayName . '">' : '') .
                       '<div class="user-details-cell">' .
                       '<strong>' . $displayName . '</strong>' .
                       '</div></div>';

            // Status badge
            $statusBadge = $isActive
                ? '<span class="badge-status badge-active">Active</span>'
                : '<span class="badge-status badge-inactive">Inactive</span>';

            // Add button
            $actionButton = '<button class="btn btn-sm btn-success add-user-btn" ' .
                          'data-account-id="' . $accountId . '" ' .
                          'data-display-name="' . $displayName . '" ' .
                          'data-avatar-url="' . $avatarUrl . '">' .
                          '<i class="fa fa-plus"></i> Add' .
                          '</button>';

            $row = [
                $fullName,
                $statusBadge,
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
     * DataTable endpoint for monitored users
     */
    public function monitoredDatatable(Request $request)
    {
        $connectionId = $request->input('connection_id');

        try {
            // Verify the connection belongs to the authenticated user
            $connection = JiraConnection::where('user_id', Auth::id())
                ->where('id', $connectionId)
                ->firstOrFail();

            // Build query for monitored users
            $query = MonitoredUser::where('jira_connection_id', $connectionId);
        } catch (\Exception $e) {
            Log::error('Error in monitored datatable endpoint: ' . $e->getMessage());
            return response()->json([
                'draw' => intval($request->input('draw', 0)),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Failed to load monitored users: ' . $e->getMessage()
            ]);
        }

        // Apply search filter if provided
        $searchValue = $request->input('search.value', '');
        if (!empty($searchValue)) {
            $query->where('display_name', 'LIKE', '%' . $searchValue . '%');
        }

        // Get total and filtered counts
        $totalRecords = MonitoredUser::where('jira_connection_id', $connectionId)->count();
        $filteredRecords = $query->count();

        // Apply sorting
        if ($request->has('order')) {
            $orderColumnIndex = $request->input('order.0.column', 0);
            $orderDir = $request->input('order.0.dir', 'asc');

            // Map column indexes to database fields
            $columnMap = [
                0 => 'display_name',  // Full name
                1 => 'is_active',     // Status
                2 => null             // Actions (not sortable)
            ];

            if (isset($columnMap[$orderColumnIndex]) && $columnMap[$orderColumnIndex] !== null) {
                $query->orderBy($columnMap[$orderColumnIndex], $orderDir);
            }
        } else {
            // Default sorting
            $query->orderBy('display_name', 'asc');
        }

        // Apply pagination
        if ($request->has('start') && $request->has('length')) {
            $query->offset($request->input('start'))
                  ->limit($request->input('length'));
        }

        $monitoredUsers = $query->get();

        // Transform data for DataTable
        $data = [];
        foreach ($monitoredUsers as $user) {
            $avatarUrl = $user->avatar_url ?? '';
            $displayName = htmlspecialchars($user->display_name);
            $userId = $user->id;
            $isActive = $user->is_active;

            // Format full name with avatar
            $fullName = '<div class="user-info-cell">' .
                       ($avatarUrl ? '<img src="' . $avatarUrl . '" class="user-avatar-small" alt="' . $displayName . '">' : '') .
                       '<div class="user-details-cell">' .
                       '<strong>' . $displayName . '</strong>' .
                       '</div></div>';

            // Status badge with toggle button
            $statusBadge = '<div class="status-cell">' .
                          ($isActive
                              ? '<span class="badge-status badge-active">Active</span>'
                              : '<span class="badge-status badge-inactive">Inactive</span>') .
                          '</div>';

            // Action buttons
            $actionButtons = '<div class="btn-group">' .
                           '<button class="btn btn-sm btn-warning toggle-status" data-id="' . $userId . '" title="Toggle Status">' .
                           '<i class="fa fa-power-off"></i>' .
                           '</button>' .
                           '<button class="btn btn-sm btn-danger remove-user" data-id="' . $userId . '" title="Remove User">' .
                           '<i class="fa fa-trash"></i>' .
                           '</button>' .
                           '</div>';

            $row = [
                $fullName,
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
    }
}
