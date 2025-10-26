<?php

namespace App\Http\Controllers;

use App\Models\JiraConnection;
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
            // Get all Jira connections for the authenticated user
            $query = JiraConnection::where('user_id', Auth::id());

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
            $totalRecords = JiraConnection::where('user_id', Auth::id())->count();
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

                // Add button (disabled for now as requested)
                $actionButton = '<button class="btn btn-sm btn-success add-instance-btn" ' .
                              'data-instance-id="' . $instanceId . '" ' .
                              'data-instance-name="' . $siteName . '" ' .
                              'data-site-url="' . $siteUrl . '" ' .
                              'data-cloud-id="' . $cloudId . '" ' .
                              'disabled>' .
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
}
