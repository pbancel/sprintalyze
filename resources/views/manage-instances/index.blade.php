<x-member-layout title="Manage Instances - Sprintalyze">
    @push('styles')
    <style>
        .instance-card {
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.3s;
        }
        .instance-card:hover {
            background-color: #f5f5f5;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .instance-card.monitored {
            background-color: #e8f5e9;
            border-color: #4caf50;
        }
        .instance-card.inactive {
            opacity: 0.6;
        }
        .instance-info {
            display: flex;
            align-items: center;
            flex: 1;
        }
        .instance-icon {
            width: 48px;
            height: 48px;
            border-radius: 4px;
            margin-right: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #0052CC;
            color: white;
            font-size: 24px;
            font-weight: bold;
        }
        .instance-details h4 {
            margin: 0 0 5px 0;
            font-size: 16px;
        }
        .instance-details p {
            margin: 0;
            color: #666;
            font-size: 13px;
        }
        .instance-actions {
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
        .instance-info-cell {
            display: flex;
            align-items: center;
        }
        .instance-icon-small {
            width: 32px;
            height: 32px;
            border-radius: 4px;
            margin-right: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #0052CC;
            color: white;
            font-size: 16px;
            font-weight: bold;
        }
        .instance-details-cell {
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
        <li class="active"><a href="{{ route('manage-instances.index') }}">Manage Instances</a></li>
    </ol>
    <div class="page-heading">
        <h1>Manage Instances<small>Manage Jira instances to monitor</small></h1>
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
                        <h2>Monitored Jira Instances</h2>
                        <div class="panel-ctrls">
                            <!-- DataTable controls will be inserted here -->
                        </div>
                    </div>
                    <div class="panel-body">
                        <table id="monitored-instances-table" class="table table-striped table-bordered" cellspacing="0" width="100%">
                            <thead>
                                <tr>
                                    <th>Instance Name</th>
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
                        <h2>Available Jira Instances</h2>
                        <div class="panel-ctrls">
                            <!-- DataTable controls will be inserted here -->
                        </div>
                    </div>
                    <div class="panel-body">
                        <table id="available-instances-table" class="table table-striped table-bordered" cellspacing="0" width="100%">
                            <thead>
                                <tr>
                                    <th>Instance Name</th>
                                    <th>URL</th>
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
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        // Initialize DataTable for available instances (right panel)
        var availableInstancesTable = makeTable('#available-instances-table', {
            'language': {
                'lengthMenu': '_MENU_'
            },
            'processing': true,
            'serverSide': true,
            'stateSave': false,
            'columnDefs': [
                { orderable: true, targets: [0, 1] },   // Instance Name and URL sortable
                { orderable: false, targets: [2] }      // Action not sortable
            ],
            'order': [[0, 'asc']], // Sort by instance name by default
            'ajax': {
                url: datatableUrl('/available-instances.json'),
                dataSrc: 'data'
            }
        });
    });
    </script>
    @endpush
</x-member-layout>
