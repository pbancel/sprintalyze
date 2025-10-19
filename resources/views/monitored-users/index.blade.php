<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Monitored Users - Sprintalyze</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-touch-fullscreen" content="yes">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="shortcut icon" href="{{ asset('template/assets/img/logo-icon-dark.png') }}">

    <link type='text/css' href='http://fonts.googleapis.com/css?family=Roboto:300,400,400italic,500' rel='stylesheet'>
    <link type='text/css'  href="https://fonts.googleapis.com/icon?family=Material+Icons"  rel="stylesheet">

    <link href="{{ asset('template/assets/fonts/font-awesome/css/font-awesome.min.css') }}" type="text/css" rel="stylesheet">
    <link href="{{ asset('template/assets/css/styles.css') }}" type="text/css" rel="stylesheet">
    <link href="{{ asset('template/assets/plugins/progress-skylo/skylo.css') }}" type="text/css" rel="stylesheet">

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
    </style>
</head>

<body class="animated-content infobar-overlay">

<header id="topnav" class="navbar navbar-default navbar-fixed-top" role="banner">
    <div class="logo-area">
        <a class="navbar-brand navbar-brand-primary" href="{{ url('/dashboard') }}">
            <img class="show-on-collapse img-logo-white" alt="Sprintalyze" src="{{ asset('template/assets/img/logo-icon-white.png') }}">
            <img class="show-on-collapse img-logo-dark" alt="Sprintalyze" src="{{ asset('template/assets/img/logo-icon-dark.png') }}">
            <img class="img-white" alt="Sprintalyze" src="{{ asset('template/assets/img/logo-white.png') }}">
            <img class="img-dark" alt="Sprintalyze" src="{{ asset('template/assets/img/logo-dark.png') }}">
        </a>

        <span id="trigger-sidebar" class="toolbar-trigger toolbar-icon-bg stay-on-search">
            <a data-toggle="tooltips" data-placement="right" title="Toggle Sidebar">
                <span class="icon-bg">
                    <i class="material-icons">menu</i>
                </span>
            </a>
        </span>
    </div>

    <ul class="nav navbar-nav toolbar pull-right">
        <li class="dropdown toolbar-icon-bg">
            <a href="#" class="dropdown-toggle" data-toggle='dropdown'>
                <span class="icon-bg">
                    <i class="material-icons">person</i>
                </span>
            </a>
            <ul class="dropdown-menu">
                <li><a href="{{ route('profile.edit') }}">Profile</a></li>
                <li class="divider"></li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <a href="{{ route('logout') }}" onclick="event.preventDefault(); this.closest('form').submit();">
                            Logout
                        </a>
                    </form>
                </li>
            </ul>
        </li>
    </ul>
</header>

<div id="wrapper">
    <div id="layout-static">
        <div class="static-sidebar-wrapper sidebar-blue">
            <div class="static-sidebar">
                <div class="sidebar">
                    <div class="widget" id="widget-profileinfo">
                        <div class="widget-body">
                            <div class="userinfo">
                                <div class="avatar pull-left">
                                    <img src="{{ asset('template/assets/demo/avatar/avatar_15.png') }}" class="img-responsive img-circle">
                                </div>
                                <div class="info">
                                    <span class="username">{{ Auth::user()->name }}</span>
                                    <span class="useremail">{{ Auth::user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="widget stay-on-collapse" id="widget-sidebar">
                        <nav role="navigation" class="widget-body">
                            <ul class="acc-menu">
                                <li class="nav-separator"><span>Navigation</span></li>
                                <li><a class="withripple" href="{{ url('/dashboard') }}"><span class="icon">
                                <i class="material-icons">home</i></span><span>Dashboard</span></a></li>
                                <li><a class="withripple" href="{{ route('jira.connect') }}"><span class="icon">
                                <i class="material-icons">link</i></span><span>Jira Connect</span></a></li>
                                <li class="active"><a class="withripple" href="{{ route('monitored-users.index') }}"><span class="icon">
                                <i class="material-icons">people</i></span><span>Monitored Users</span></a></li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <div class="static-content-wrapper">
            <div class="static-content">
                <div class="page-content">
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
                                    </div>
                                    <div class="panel-body">
                                        <div class="search-box">
                                            <div class="input-group">
                                                <input type="text" id="user-search" class="form-control" placeholder="Search users...">
                                                <span class="input-group-btn">
                                                    <button class="btn btn-primary" id="search-btn">
                                                        <i class="fa fa-search"></i> Search
                                                    </button>
                                                </span>
                                            </div>
                                        </div>

                                        <div id="available-users-list">
                                            <p class="text-muted">Click "Search" to load available users from Jira.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <footer role="contentinfo">
                <div class="clearfix">
                    <ul class="list-unstyled list-inline pull-left">
                        <li><h6 style="margin: 0;">&copy; {{ date('Y') }} Sprintalyze</h6></li>
                    </ul>
                </div>
            </footer>
        </div>
    </div>
</div>

<!-- Load site level scripts -->
<script src="{{ asset('template/assets/js/jquery-1.10.2.min.js') }}"></script>
<script src="{{ asset('template/assets/js/jqueryui-1.10.3.min.js') }}"></script>
<script src="{{ asset('template/assets/js/bootstrap.min.js') }}"></script>
<script src="{{ asset('template/assets/js/enquire.min.js') }}"></script>

<script src="{{ asset('template/assets/plugins/velocityjs/velocity.min.js') }}"></script>
<script src="{{ asset('template/assets/plugins/velocityjs/velocity.ui.min.js') }}"></script>

<script src="{{ asset('template/assets/plugins/progress-skylo/skylo.js') }}"></script>
<script src="{{ asset('template/assets/plugins/wijets/wijets.js') }}"></script>
<script src="{{ asset('template/assets/plugins/sparklines/jquery.sparklines.min.js') }}"></script>
<script src="{{ asset('template/assets/plugins/codeprettifier/prettify.js') }}"></script>
<script src="{{ asset('template/assets/plugins/bootstrap-tabdrop/js/bootstrap-tabdrop.js') }}"></script>
<script src="{{ asset('template/assets/plugins/nanoScroller/js/jquery.nanoscroller.min.js') }}"></script>
<script src="{{ asset('template/assets/plugins/dropdown.js/jquery.dropdown.js') }}"></script>
<script src="{{ asset('template/assets/plugins/bootstrap-material-design/js/material.min.js') }}"></script>
<script src="{{ asset('template/assets/plugins/bootstrap-material-design/js/ripples.min.js') }}"></script>

<script src="{{ asset('template/assets/js/application.js') }}"></script>
<script src="{{ asset('template/assets/demo/demo.js') }}"></script>
<script src="{{ asset('template/assets/demo/demo-switcher.js') }}"></script>

<script>
$(document).ready(function() {
    const connectionId = {{ $activeConnection->id }};
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    // Load users from Jira
    function loadUsers(query = '') {
        $('#available-users-list').html('<div class="loading"><i class="fa fa-spinner fa-spin fa-2x"></i><p>Loading users from Jira...</p></div>');

        $.ajax({
            url: '{{ route("monitored-users.fetch") }}',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            data: {
                connection_id: connectionId,
                query: query
            },
            success: function(response) {
                if (response.success) {
                    displayUsers(response.users);
                } else {
                    $('#available-users-list').html('<div class="alert alert-danger">' + response.message + '</div>');
                }
            },
            error: function(xhr) {
                $('#available-users-list').html('<div class="alert alert-danger">Failed to load users. Please try again.</div>');
            }
        });
    }

    // Display users in the available list
    function displayUsers(users) {
        if (users.length === 0) {
            $('#available-users-list').html('<p class="text-muted">No users found.</p>');
            return;
        }

        let html = '';
        users.forEach(function(user) {
            if (!user.isMonitored) {
                const avatarUrl = user.avatarUrls && user.avatarUrls['48x48']
                    ? user.avatarUrls['48x48']
                    : '{{ asset("template/assets/demo/avatar/avatar_15.png") }}';

                html += `
                    <div class="user-card">
                        <div class="user-info">
                            <img src="${avatarUrl}" alt="${user.displayName}" class="user-avatar">
                            <div class="user-details">
                                <h4>${user.displayName}</h4>
                                <p>${user.emailAddress || 'No email'}</p>
                            </div>
                        </div>
                        <div class="user-actions">
                            <button class="btn btn-sm btn-success add-user"
                                data-account-id="${user.accountId}"
                                data-display-name="${user.displayName}"
                                data-email="${user.emailAddress || ''}"
                                data-avatar="${avatarUrl}">
                                <i class="fa fa-plus"></i> Monitor
                            </button>
                        </div>
                    </div>
                `;
            }
        });

        if (html === '') {
            html = '<p class="text-muted">All available users are already being monitored.</p>';
        }

        $('#available-users-list').html(html);
    }

    // Search button click
    $('#search-btn').on('click', function() {
        const query = $('#user-search').val();
        loadUsers(query);
    });

    // Search on Enter key
    $('#user-search').on('keypress', function(e) {
        if (e.which === 13) {
            const query = $(this).val();
            loadUsers(query);
        }
    });

    // Add user to monitoring
    $(document).on('click', '.add-user', function() {
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
                avatar_url: btn.data('avatar')
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

</body>
</html>
