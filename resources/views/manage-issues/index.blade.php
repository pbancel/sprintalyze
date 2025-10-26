<x-member-layout title="Manage Issues - Sprintalyze">
    @push('styles')
    <style>
        .issue-card {
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.3s;
        }
        .issue-card:hover {
            background-color: #f5f5f5;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .issue-card.monitored {
            background-color: #e8f5e9;
            border-color: #4caf50;
        }
        .issue-card.inactive {
            opacity: 0.6;
        }
        .issue-info {
            display: flex;
            align-items: center;
            flex: 1;
        }
        .issue-icon {
            width: 48px;
            height: 48px;
            border-radius: 4px;
            margin-right: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #2196F3;
            color: white;
            font-size: 20px;
            font-weight: bold;
        }
        .issue-details h4 {
            margin: 0 0 5px 0;
            font-size: 16px;
        }
        .issue-details p {
            margin: 0;
            color: #666;
            font-size: 13px;
        }
        .issue-actions {
            display: flex;
            gap: 10px;
        }
        .loading {
            text-align: center;
            padding: 40px;
        }
        .badge-status {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }
        .badge-active {
            background-color: #4caf50;
            color: white;
        }
        .badge-inactive {
            background-color: #9e9e9e;
            color: white;
        }
        /* DataTable specific styles */
        .issue-info-cell {
            display: flex;
            align-items: center;
        }
        .issue-icon-small {
            width: 32px;
            height: 32px;
            border-radius: 4px;
            margin-right: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #2196F3;
            color: white;
            font-size: 14px;
            font-weight: bold;
        }
        .issue-details-cell {
            display: flex;
            flex-direction: column;
        }
    </style>
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="{{ asset('template/assets/plugins/datatables/dataTables.bootstrap.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('template/assets/plugins/datatables/dataTables.css') }}">
    @endpush

    <ol class="breadcrumb">
        <li><a href="{{ url('/dashboard') }}">Home</a></li>
        <li class="active"><a href="{{ route('manage-issues.index') }}">Manage Issues</a></li>
    </ol>
    <div class="page-heading">
        <h1>Manage Issues<small>Manage Jira issues to track</small></h1>
    </div>
    <div class="container-fluid">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <strong>Success!</strong> {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <strong>Error!</strong> {{ session('error') }}
            </div>
        @endif

        <div class="row">
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h2>Monitored Issues</h2>
                        <div class="panel-ctrls">
                            <!-- DataTable controls will be inserted here -->
                        </div>
                    </div>
                    <div class="panel-body">
                        <table id="monitored-issues-table" class="table table-striped table-bordered" cellspacing="0" width="100%">
                            <thead>
                                <tr>
                                    <th>Issue Key</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data loaded dynamically via DataTables -->
                            </tbody>
                        </table>
                    </div>
                    <div class="panel-footer">
                        <!-- Pagination will be inserted here -->
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h2>Available Jira Issues</h2>
                        <div class="panel-ctrls">
                            <!-- DataTable controls will be inserted here -->
                        </div>
                    </div>
                    <div class="panel-body">
                        <table id="available-issues-table" class="table table-striped table-bordered" cellspacing="0" width="100%">
                            <thead>
                                <tr>
                                    <th>Issue Key</th>
                                    <th>Summary</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data loaded dynamically via DataTables -->
                            </tbody>
                        </table>
                    </div>
                    <div class="panel-footer">
                        <!-- Pagination will be inserted here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <!-- DataTables JS -->
    <script type="text/javascript" src="{{ asset('template/assets/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('template/assets/plugins/datatables/dataTables.bootstrap.js') }}"></script>
    <script type="text/javascript" src="{{ asset('template/assets/js/datatable-common.js') }}"></script>

    <script>
    $(document).ready(function() {
        const connectionId = {{ $activeConnection->id }};
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        // Initialize DataTable for available issues (right panel)
        var availableIssuesTable = makeTable('#available-issues-table', {
            'language': {
                'lengthMenu': '_MENU_'
            },
            'processing': true,
            'serverSide': true, // Server-side processing
            'stateSave': false,
            'columnDefs': [
                { orderable: true, targets: [0, 1] },   // Issue Key and Summary sortable
                { orderable: false, targets: [2] }      // Action not sortable
            ],
            'order': [[0, 'asc']], // Sort by issue key by default
            'ajax': {
                url: datatableUrl('/available-issues.json'),
                dataSrc: 'data',
                data: function (d) {
                    d.connection_id = connectionId;
                },
                error: function(xhr, error, code) {
                    console.error('DataTable error:', xhr.responseText);
                    alert('Failed to load issues: ' + (xhr.responseJSON?.error || error));
                }
            }
        });
    });
    </script>
    @endpush
</x-member-layout>
