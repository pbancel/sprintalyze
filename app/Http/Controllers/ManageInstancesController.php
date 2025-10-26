<?php

namespace App\Http\Controllers;

use App\Models\JiraConnection;
use App\Models\JiraInstance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ManageInstancesController extends Controller
{
    /**
     * Display the manage instances page
     */
    public function index()
    {
        $user = Auth::user();

        return view('manage-instances.index');
    }

    /**
     * DataTable endpoint for available Jira instances
     */
    public function availableDatatable(Request $request)
    {
        try {
            // Get IDs of already monitored instances
            $monitoredConnectionIds = JiraInstance::where('user_id', Auth::id())
                ->pluck('jira_connection_id')
                ->toArray();

            // Get all Jira connections for the authenticated user, excluding monitored ones
            $query = JiraConnection::where('user_id', Auth::id())
                ->whereNotIn('id', $monitoredConnectionIds);

            // Apply search filter if provided
            $searchValue = $request->input('search.value', '');
            if (!empty($searchValue)) {
                $query->where(function ($q) use ($searchValue) {
                    $q->where('site_name', 'LIKE', '%' . $searchValue . '%')
                      ->orWhere('site_url', 'LIKE', '%' . $searchValue . '%')
                      ->orWhere('cloud_id', 'LIKE', '%' . $searchValue . '%');
                });
            }

            // Get total and filtered counts
            $totalRecords = JiraConnection::where('user_id', Auth::id())
                ->whereNotIn('id', $monitoredConnectionIds)
                ->count();
            $filteredRecords = $query->count();

            // Apply sorting
            if ($request->has('order')) {
                $orderColumnIndex = $request->input('order.0.column', 0);
                $orderDir = $request->input('order.0.dir', 'asc');

                // Map column indexes to database fields
                $columnMap = [
                    0 => 'site_name',  // Instance Name
                    1 => 'site_url',   // URL
                    2 => null          // Action (not sortable)
                ];

                if (isset($columnMap[$orderColumnIndex]) && $columnMap[$orderColumnIndex] !== null) {
                    $query->orderBy($columnMap[$orderColumnIndex], $orderDir);
                }
            } else {
                // Default sorting
                $query->orderBy('site_name', 'asc');
            }

            // Apply pagination
            if ($request->has('start') && $request->has('length')) {
                $query->offset($request->input('start'))
                      ->limit($request->input('length'));
            }

            $instances = $query->get();

            // Transform data for DataTable
            $data = [];
            foreach ($instances as $instance) {
                $siteName = htmlspecialchars($instance->site_name ?? 'Unknown');
                $siteUrl = htmlspecialchars($instance->site_url);
                $cloudId = htmlspecialchars($instance->cloud_id);
                $instanceId = $instance->id;

                // Get first letter for icon
                $firstLetter = strtoupper(substr($siteName, 0, 1));

                // Format instance name with icon
                $instanceName = '<div class="instance-info-cell">' .
                              '<div class="instance-icon-small">' . $firstLetter . '</div>' .
                              '<div class="instance-details-cell">' .
                              '<strong>' . $siteName . '</strong>' .
                              '</div></div>';

                // Format URL
                $urlDisplay = '<a href="' . $siteUrl . '" target="_blank" class="text-muted">' .
                             '<small>' . $siteUrl . '</small>' .
                             '</a>';

                // Add button
                $actionButton = '<button class="btn btn-sm btn-success add-instance-btn" ' .
                              'data-instance-id="' . $instanceId . '" ' .
                              'data-instance-name="' . $siteName . '" ' .
                              'data-site-url="' . $siteUrl . '" ' .
                              'data-cloud-id="' . $cloudId . '">' .
                              '<i class="fa fa-plus"></i> Add' .
                              '</button>';

                $row = [
                    $instanceName,
                    $urlDisplay,
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
        } catch (\Exception $e) {
            Log::error('Error in available instances datatable: ' . $e->getMessage());
            return response()->json([
                'draw' => intval($request->input('draw', 0)),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Failed to load instances: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * DataTable endpoint for monitored instances
     */
    public function monitoredDatatable(Request $request)
    {
        try {
            // Build query for monitored instances
            $query = JiraInstance::where('user_id', Auth::id());

            // Apply search filter if provided
            $searchValue = $request->input('search.value', '');
            if (!empty($searchValue)) {
                $query->where(function ($q) use ($searchValue) {
                    $q->where('site_name', 'LIKE', '%' . $searchValue . '%')
                      ->orWhere('site_url', 'LIKE', '%' . $searchValue . '%')
                      ->orWhere('cloud_id', 'LIKE', '%' . $searchValue . '%');
                });
            }

            // Get total and filtered counts
            $totalRecords = JiraInstance::where('user_id', Auth::id())->count();
            $filteredRecords = $query->count();

            // Apply sorting
            if ($request->has('order')) {
                $orderColumnIndex = $request->input('order.0.column', 0);
                $orderDir = $request->input('order.0.dir', 'asc');

                // Map column indexes to database fields
                $columnMap = [
                    0 => 'site_name',  // Instance Name
                    1 => 'is_active',  // Status
                    2 => null          // Actions (not sortable)
                ];

                if (isset($columnMap[$orderColumnIndex]) && $columnMap[$orderColumnIndex] !== null) {
                    $query->orderBy($columnMap[$orderColumnIndex], $orderDir);
                }
            } else {
                // Default sorting
                $query->orderBy('site_name', 'asc');
            }

            // Apply pagination
            if ($request->has('start') && $request->has('length')) {
                $query->offset($request->input('start'))
                      ->limit($request->input('length'));
            }

            $monitoredInstances = $query->get();

            // Transform data for DataTable
            $data = [];
            foreach ($monitoredInstances as $instance) {
                $siteName = htmlspecialchars($instance->site_name ?? 'Unknown');
                $instanceId = $instance->id;
                $isActive = $instance->is_active;

                // Get first letter for icon
                $firstLetter = strtoupper(substr($siteName, 0, 1));

                // Format instance name with icon
                $instanceName = '<div class="instance-info-cell">' .
                              '<div class="instance-icon-small">' . $firstLetter . '</div>' .
                              '<div class="instance-details-cell">' .
                              '<strong>' . $siteName . '</strong>' .
                              '</div></div>';

                // Status badge
                $statusBadge = '<div class="status-cell">' .
                              ($isActive
                                  ? '<span class="badge-status badge-active">Active</span>'
                                  : '<span class="badge-status badge-inactive">Inactive</span>') .
                              '</div>';

                // Action buttons
                $actionButtons = '<div class="btn-group">' .
                               '<button class="btn btn-sm btn-warning toggle-instance-status" data-id="' . $instanceId . '" title="Toggle Status">' .
                               '<i class="fa fa-power-off"></i>' .
                               '</button>' .
                               '<button class="btn btn-sm btn-danger remove-instance" data-id="' . $instanceId . '" title="Remove">' .
                               '<i class="fa fa-trash"></i>' .
                               '</button>' .
                               '</div>';

                $row = [
                    $instanceName,
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
            Log::error('Error in monitored instances datatable: ' . $e->getMessage());
            return response()->json([
                'draw' => intval($request->input('draw', 0)),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Failed to load monitored instances: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Add an instance to monitoring
     */
    public function store(Request $request)
    {
        Log::info('Store instance request received', ['data' => $request->all()]);

        try {
            $request->validate([
                'jira_connection_id' => 'required|exists:jira_connections,id',
                'cloud_id' => 'required|string',
                'site_name' => 'required|string',
                'site_url' => 'required|string',
            ]);

            // Verify the connection belongs to the authenticated user
            $connection = JiraConnection::where('user_id', Auth::id())
                ->where('id', $request->jira_connection_id)
                ->firstOrFail();

            // Check if instance is already being monitored
            $existing = JiraInstance::where('user_id', Auth::id())
                ->where('jira_connection_id', $connection->id)
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'This instance is already being monitored',
                ], 422);
            }

            Log::info('Creating monitored instance', [
                'user_id' => Auth::id(),
                'connection_id' => $connection->id,
                'cloud_id' => $request->cloud_id,
            ]);

            $jiraInstance = JiraInstance::create([
                'user_id' => Auth::id(),
                'jira_connection_id' => $connection->id,
                'cloud_id' => $request->cloud_id,
                'site_name' => $request->site_name,
                'site_url' => $request->site_url,
                'is_active' => true,
            ]);

            Log::info('Monitored instance created successfully', ['instance_id' => $jiraInstance->id]);

            return response()->json([
                'success' => true,
                'message' => 'Instance added to monitoring successfully',
                'instance' => $jiraInstance,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed: ' . json_encode($e->errors()));
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to add monitored instance: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to add instance to monitoring: ' . $e->getMessage(),
            ], 500);
        }
    }
}
