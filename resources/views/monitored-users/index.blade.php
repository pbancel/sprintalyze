<x-member-layout title="Monitored Users - Sprintalyze">
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
        <li class="active"><a href="{{ route('monitored-users.index') }}">Monitored Users</a></li>
    </ol>
    <div class="page-heading">
        <h1>Monitored Users<small>Manage users to track activity</small></h1>
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
                    </div>
                    <div class="panel-body">
                        @if($monitoredUsers->isEmpty())
                            <p class="text-muted">No users are currently being monitored. Add users from the available list.</p>
                        @else
                            <div id="monitored-users-list">
                                @foreach($monitoredUsers as $user)
                                    <div class="user-card monitored {{ $user->is_active ? '' : 'inactive' }}" data-user-id="{{ $user->id }}">
                                        <div class="user-info">
                                            @if($user->avatar_url)
                                                <img src="{{ $user->avatar_url }}" alt="{{ $user->display_name }}" class="user-avatar">
                                            @else
                                                <img src="{{ asset('template/assets/demo/avatar/avatar_15.png') }}" alt="{{ $user->display_name }}" class="user-avatar">
                                            @endif
                                            <div class="user-details">
                                                <h4>{{ $user->display_name }}</h4>
                                                <p>{{ $user->email }}</p>
                                            </div>
                                        </div>
                                        <div class="user-actions">
                                            <span class="badge-status {{ $user->is_active ? 'badge-active' : 'badge-inactive' }}">
                                                {{ $user->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                            <button class="btn btn-sm btn-warning toggle-status" data-id="{{ $user->id }}">
                                                <i class="fa fa-power-off"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger remove-user" data-id="{{ $user->id }}">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
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
                                    <th>Creation Date</th>
                                    <th>Full Name</th>
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

        // Initialize DataTable for available users
        var availableUsersTable = makeTable('#available-users-table', {
            'language': {
                'lengthMenu': '_MENU_'
            },
            'processing': true,
            'serverSide': true,
            'stateSave': false,
            'columnDefs': [
                { orderable: false, targets: [0, 2] }, // Creation date and Action columns not sortable
                { orderable: true, targets: [1] }       // Full name column sortable
            ],
            'order': [[1, 'asc']], // Sort by full name by default
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
                    email: btn.data('email'),
                    avatar_url: btn.data('avatar-url')
                },
                success: function(response) {
                    if (response.success) {
                        location.reload(); // Reload to update both lists
                    }
                },
                error: function() {
                    alert('Failed to add user. Please try again.');
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
            const card = $(this).closest('.user-card');

            $.ajax({
                url: '/monitored-users/' + userId,
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                success: function(response) {
                    if (response.success) {
                        card.fadeOut(300, function() {
                            $(this).remove();
                            if ($('#monitored-users-list .user-card').length === 0) {
                                $('#monitored-users-list').html('<p class="text-muted">No users are currently being monitored.</p>');
                            }
                        });
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
            const card = $(this).closest('.user-card');

            $.ajax({
                url: '/monitored-users/' + userId + '/toggle',
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
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
