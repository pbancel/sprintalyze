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
}
