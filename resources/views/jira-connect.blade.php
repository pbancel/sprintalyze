<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Jira Connect - Sprintalyze</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-touch-fullscreen" content="yes">

    <link rel="shortcut icon" href="{{ asset('template/assets/img/logo-icon-dark.png') }}">

    <link type='text/css' href='http://fonts.googleapis.com/css?family=Roboto:300,400,400italic,500' rel='stylesheet'>
    <link type='text/css'  href="https://fonts.googleapis.com/icon?family=Material+Icons"  rel="stylesheet">

    <link href="{{ asset('template/assets/fonts/font-awesome/css/font-awesome.min.css') }}" type="text/css" rel="stylesheet">
    <link href="{{ asset('template/assets/css/styles.css') }}" type="text/css" rel="stylesheet">
    <link href="{{ asset('template/assets/plugins/progress-skylo/skylo.css') }}" type="text/css" rel="stylesheet">
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
                        <li class=""><a href="{{ url('/dashboard') }}">Home</a></li>
                        <li class="active"><a href="{{ route('jira.connect') }}">Jira Connect</a></li>
                    </ol>
                    <div class="page-heading">
                        <h1>Jira Connection<small>Connect your Jira account</small></h1>
                    </div>
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        <h2>Jira Connection</h2>
                                    </div>
                                    <div class="panel-body">
                                        <!-- Success/Error Messages -->
                                        @if(session('success'))
                                            <div class="alert alert-success">
                                                <strong>Success!</strong> {{ session('success') }}
                                            </div>
                                        @endif

                                        @if(session('error'))
                                            <div class="alert alert-danger">
                                                <strong>Error!</strong> {{ session('error') }}
                                            </div>
                                        @endif

                                        <p>Connection to your Jira account</p>

                                        <div class="mt-lg">
                                            <a href="{{ route('jira.authorize') }}" class="btn btn-success btn-raised">
                                                <i class="fa fa-link"></i> Connect to Jira (OAuth)
                                            </a>
                                            <button id="testConnection" class="btn btn-primary btn-raised">
                                                <i class="fa fa-check"></i> Test API Connection
                                            </button>
                                        </div>

                                        <div id="connectionResult" class="mt-lg" style="display: none;">
                                            <div class="alert" id="resultAlert"></div>
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
    $('#testConnection').click(function() {
        var btn = $(this);
        btn.prop('disabled', true);
        btn.html('<i class="fa fa-spinner fa-spin"></i> Testing...');

        $.ajax({
            url: '{{ route("jira.test") }}',
            method: 'GET',
            success: function(response) {
                $('#connectionResult').show();
                var alertDiv = $('#resultAlert');

                if (response.success) {
                    alertDiv.removeClass('alert-danger').addClass('alert-success');
                    alertDiv.html('<strong>Success!</strong> ' + response.message);

                    if (response.data) {
                        alertDiv.append('<br><br><strong>User:</strong> ' + response.data.displayName + '<br><strong>Email:</strong> ' + response.data.emailAddress);
                    }
                } else {
                    alertDiv.removeClass('alert-success').addClass('alert-danger');
                    alertDiv.html('<strong>Error!</strong> ' + response.message);
                }

                btn.prop('disabled', false);
                btn.html('Test Connection');
            },
            error: function(xhr) {
                $('#connectionResult').show();
                var alertDiv = $('#resultAlert');
                alertDiv.removeClass('alert-success').addClass('alert-danger');
                alertDiv.html('<strong>Error!</strong> Failed to connect to Jira. Please check your configuration.');

                btn.prop('disabled', false);
                btn.html('Test Connection');
            }
        });
    });
});
</script>

</body>
</html>
