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
        $connections = $user->jiraConnections()->with('monitoredUsers')->get();

        // Get the first connection by default (you can add UI to switch between connections later)
        $activeConnection = $connections->first();

        if (!$activeConnection) {
            return redirect()->route('jira.connect')
                ->with('error', 'Please connect to Jira first.');
        }

        // Get monitored users for the active connection
        $monitoredUsers = $activeConnection->monitoredUsers;

        return view('monitored-users.index', compact('connections', 'activeConnection', 'monitoredUsers'));
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
        $request->validate([
            'connection_id' => 'required|exists:jira_connections,id',
            'jira_account_id' => 'required|string',
            'email' => 'nullable|email',
            'display_name' => 'required|string',
            'avatar_url' => 'nullable|url',
        ]);

        // Verify the connection belongs to the authenticated user
        $connection = JiraConnection::where('user_id', Auth::id())
            ->where('id', $request->connection_id)
            ->firstOrFail();

        try {
            $monitoredUser = MonitoredUser::create([
                'jira_connection_id' => $connection->id,
                'jira_account_id' => $request->jira_account_id,
                'email' => $request->email,
                'display_name' => $request->display_name,
                'avatar_url' => $request->avatar_url,
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User added to monitoring successfully',
                'user' => $monitoredUser,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to add monitored user: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to add user to monitoring',
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

        // Verify the connection belongs to the authenticated user
        $connection = JiraConnection::where('user_id', Auth::id())
            ->where('id', $connectionId)
            ->firstOrFail();

        $this->jiraClient->setConnection($connection);

        // Fetch users from Jira
        $result = $this->jiraClient->getUsers();

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

        // Filter and transform users
        $users = collect($result['data'])
            ->filter(function ($user) use ($monitoredAccountIds) {
                // Only show active users that are not already monitored
                return ($user['active'] ?? true) && !in_array($user['accountId'], $monitoredAccountIds);
            })
            ->values();

        // Apply search filter if provided
        $searchValue = $request->input('search.value', '');
        if (!empty($searchValue)) {
            $users = $users->filter(function ($user) use ($searchValue) {
                $searchLower = strtolower($searchValue);
                return str_contains(strtolower($user['displayName'] ?? ''), $searchLower) ||
                       str_contains(strtolower($user['emailAddress'] ?? ''), $searchLower);
            })->values();
        }

        $totalRecords = $users->count();

        // Apply sorting
        if ($request->has('order')) {
            $orderColumnIndex = $request->input('order.0.column', 0);
            $orderDir = $request->input('order.0.dir', 'asc');

            // Map column indexes to data fields
            $columnMap = [
                0 => 'created',      // Creation date (we'll use a placeholder for now)
                1 => 'displayName',  // Full name
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
            $email = htmlspecialchars($user['emailAddress'] ?? 'N/A');
            $accountId = htmlspecialchars($user['accountId']);

            // Creation date placeholder (Jira API doesn't provide this for users)
            $createdDate = 'N/A';

            // Format full name with email
            $fullName = '<div class="user-info-cell">' .
                       ($avatarUrl ? '<img src="' . $avatarUrl . '" class="user-avatar-small" alt="' . $displayName . '">' : '') .
                       '<div class="user-details-cell">' .
                       '<strong>' . $displayName . '</strong><br>' .
                       '<small class="text-muted">' . $email . '</small>' .
                       '</div></div>';

            // Add button (inactive for now)
            $actionButton = '<button class="btn btn-sm btn-success add-user-btn" ' .
                          'data-account-id="' . $accountId . '" ' .
                          'data-display-name="' . $displayName . '" ' .
                          'data-email="' . $email . '" ' .
                          'data-avatar-url="' . $avatarUrl . '" ' .
                          'disabled>' .
                          '<i class="fa fa-plus"></i> Add' .
                          '</button>';

            $row = [
                $createdDate,
                $fullName,
                $actionButton
            ];

            $data[] = $row;
        }

        // Return standard DataTable JSON response
        return response()->json([
            'draw' => intval($request->input('draw', 0)),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords, // Same as total since filtering is done in memory
            'data' => $data
        ]);
    }
}
