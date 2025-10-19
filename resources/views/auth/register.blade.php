<!DOCTYPE html>
<html lang="en" class="coming-soon">
<head>
    <meta charset="utf-8">
    <title>Registration - Sprintalyze</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-touch-fullscreen" content="yes">

    <link type='text/css' href='http://fonts.googleapis.com/css?family=Roboto:300,400,400italic,500' rel='stylesheet'>
    <link type='text/css'  href="https://fonts.googleapis.com/icon?family=Material+Icons"  rel="stylesheet">
    <link href="{{ asset('template/assets/plugins/progress-skylo/skylo.css') }}" type="text/css" rel="stylesheet">

    <link href="{{ asset('template/assets/fonts/font-awesome/css/font-awesome.min.css') }}" type="text/css" rel="stylesheet">
    <link href="{{ asset('template/assets/css/styles.css') }}" type="text/css" rel="stylesheet">
</head>

<body class="focused-form animated-content">

<div class="container" id="registration-form">
    <a href="{{ url('/') }}" class="login-logo"><img src="{{ asset('template/assets/img/logo-dark.png') }}"></a>
    <div class="row">
        <div class="col-md-6 col-md-offset-3">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h2>Registration Form</h2>
                </div>
                <div class="panel-body">
                    <form method="POST" action="{{ route('register') }}" class="form-horizontal">
                        @csrf

                        <div class="form-group mb-md">
                            <div class="col-xs-8 col-xs-offset-2">
                                <input type="text" name="name" value="{{ old('name') }}" class="form-control" placeholder="Full Name" required autofocus autocomplete="name">
                                @error('name')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group mb-md">
                            <div class="col-xs-8 col-xs-offset-2">
                                <input type="email" name="email" value="{{ old('email') }}" class="form-control" placeholder="Email" required autocomplete="username">
                                @error('email')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group mb-md">
                            <div class="col-xs-8 col-xs-offset-2">
                                <input type="password" name="password" class="form-control" placeholder="Password" required autocomplete="new-password">
                                @error('password')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group mb-md">
                            <div class="col-xs-8 col-xs-offset-2">
                                <input type="password" name="password_confirmation" class="form-control" placeholder="Confirm Password" required autocomplete="new-password">
                                @error('password_confirmation')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group mb-n">
                            <div class="col-xs-offset-2 col-xs-8">
                                <div class="checkbox">
                                    <label><input type="checkbox" required>&nbsp;&nbsp;I accept the user agreement</label>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="panel-footer">
                    <div class="clearfix">
                        <a href="{{ route('login') }}" class="btn btn-default pull-left">Already Registered? Login</a>
                        <button type="submit" onclick="document.querySelector('form').submit();" class="btn btn-primary btn-raised pull-right">Register</button>
                    </div>
                </div>
            </div>
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

</body>
</html>
