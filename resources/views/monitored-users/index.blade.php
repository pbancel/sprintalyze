<x-member-layout title="Manage Users - Sprintalyze">
    @push('styles')
    <style>
        .user-card {
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.3s;
        }
        .user-card:hover {
            background-color: #f5f5f5;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .user-card.monitored {
            background-color: #e8f5e9;
            border-color: #4caf50;
        }
        .user-card.inactive {
            opacity: 0.6;
        }
        .user-info {
            display: flex;
            align-items: center;
            flex: 1;
        }
        .user-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            margin-right: 15px;
        }
        .user-details h4 {
            margin: 0 0 5px 0;
            font-size: 16px;
        }
        .user-details p {
            margin: 0;
            color: #666;
            font-size: 13px;
        }
        .user-actions {
            display: flex;
            gap: 10px;
        }
        .search-box {
            margin-bottom: 20px;
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
        .user-info-cell {
            display: flex;
            align-items: center;
        }
        .user-avatar-small {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            margin-right: 10px;
        }
        .user-details-cell {
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
        <li class="active"><a href="{{ route('monitored-users.index') }}">Manage Users</a></li>
    </ol>
    <div class="page-heading">
        <h1>Manage Users<small>Manage users to track activity</small></h1>
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
                        <h2>Currently Monitored Users</h2>
                        <div class="panel-ctrls">
                            <!-- DataTable controls will be inserted here -->
                        </div>
                    </div>
                    <div class="panel-body">
                        <table id="monitored-users-table" class="table table-striped table-bordered" cellspacing="0" width="100%">
                            <thead>
                                <tr>
                                    <th>Full Name</th>
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
                        <h2>Available Jira Users</h2>
                        <div class="panel-ctrls">
                            <!-- DataTable controls will be inserted here -->
                        </div>
                    </div>
                    <div class="panel-body">
                        <table id="available-users-table" class="table table-striped table-bordered" cellspacing="0" width="100%">
                            <thead>
                                <tr>
                                    <th>Full Name</th>
                                    <th>Status</th>
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

        // Initialize DataTable for monitored users (left panel)
        var monitoredUsersTable = makeTable('#monitored-users-table', {
            'language': {
                'lengthMenu': '_MENU_'
            },
            'processing': true,
            'serverSide': true,
            'stateSave': false,
            'columnDefs': [
                { orderable: true, targets: [0] },      // Full name column sortable
                { orderable: false, targets: [1, 2] }   // Status and Actions columns not sortable
            ],
            'order': [[0, 'asc']], // Sort by full name by default
            'ajax': {
                url: datatableUrl('/monitored-users.json'),
                dataSrc: 'data',
                data: function (d) {
                    d.connection_id = connectionId;
                }
            }
        });

        // Initialize DataTable for available users (right panel)
        var availableUsersTable = makeTable('#available-users-table', {
            'language': {
                'lengthMenu': '_MENU_'
            },
            'processing': true,
            'serverSide': true,
            'stateSave': false,
            'columnDefs': [
                { orderable: true, targets: [0] },      // Full name column sortable
                { orderable: false, targets: [1, 2] }   // Status and Action columns not sortable
            ],
            'order': [[0, 'asc']], // Sort by full name by default
            'ajax': {
                url: datatableUrl('/available-users.json'),
                dataSrc: 'data',
                data: function (d) {
                    d.connection_id = connectionId;
                }
            }
        });

        // Add user to monitoring (using event delegation for dynamically created buttons)
        $(document).on('click', '.add-user-btn', function() {
            const btn = $(this);
            const originalHtml = btn.html();
            btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

            $.ajax({
                url: '{{ route("monitored-users.store") }}',
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                data: {
                    connection_id: connectionId,
                    jira_account_id: btn.data('account-id'),
                    display_name: btn.data('display-name'),
                    avatar_url: btn.data('avatar-url')
                },
                success: function(response) {
                    if (response.success) {
                        // Reload both datatables
                        monitoredUsersTable.ajax.reload();
                        availableUsersTable.ajax.reload();
                    } else {
                        alert('Failed to add user: ' + (response.message || 'Unknown error'));
                        btn.prop('disabled', false).html(originalHtml);
                    }
                },
                error: function(xhr, status, error) {
                    let errorMessage = 'Failed to add user. Please try again.';

                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                        // Laravel validation errors
                        errorMessage = Object.values(xhr.responseJSON.errors).flat().join('\n');
                    } else if (xhr.responseText) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            errorMessage = response.message || errorMessage;
                        } catch(e) {
                            // Not JSON, use default message
                        }
                    }

                    console.error('Add user error:', xhr.responseText);
                    alert(errorMessage);
                    btn.prop('disabled', false).html(originalHtml);
                }
            });
        });

        // Remove user from monitoring
        $(document).on('click', '.remove-user', function() {
            if (!confirm('Are you sure you want to stop monitoring this user?')) {
                return;
            }

            const userId = $(this).data('id');

            $.ajax({
                url: '/monitored-users/' + userId,
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                success: function(response) {
                    if (response.success) {
                        // Reload both datatables
                        monitoredUsersTable.ajax.reload();
                        availableUsersTable.ajax.reload();
                    }
                },
                error: function() {
                    alert('Failed to remove user. Please try again.');
                }
            });
        });

        // Toggle user status
        $(document).on('click', '.toggle-status', function() {
            const userId = $(this).data('id');

            $.ajax({
                url: '/monitored-users/' + userId + '/toggle',
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                success: function(response) {
                    if (response.success) {
                        // Reload monitored users datatable to reflect status change
                        monitoredUsersTable.ajax.reload();
                    }
                },
                error: function() {
                    alert('Failed to update user status. Please try again.');
                }
            });
        });
    });
    </script>
    @endpush
</x-member-layout>
