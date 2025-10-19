<meta charset="utf-8">
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

@stack('styles')
