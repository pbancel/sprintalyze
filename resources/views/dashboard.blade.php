<x-member-layout title="Dashboard - Sprintalyze">
    <ol class="breadcrumb">
        <li class=""><a href="{{ url('/dashboard') }}">Home</a></li>
        <li class="active"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
    </ol>
    <div class="page-heading">
        <h1>Dashboard<small>Welcome to Sprintalyze</small></h1>
    </div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                <div class="info-tile info-tile-alt tile-indigo">
                    <div class="info">
                        <div class="tile-heading"><span>Sprints</span></div>
                        <div class="tile-body"><span>0</span></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                <div class="info-tile info-tile-alt tile-danger">
                    <div class="info">
                        <div class="tile-heading"><span>Tasks</span></div>
                        <div class="tile-body"><span>0</span></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                <div class="info-tile info-tile-alt tile-primary">
                    <div class="info">
                        <div class="tile-heading"><span>Completed</span></div>
                        <div class="tile-body"><span>0</span></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                <div class="info-tile info-tile-alt tile-success clearfix">
                    <div class="info">
                        <div class="tile-heading"><span>Team Members</span></div>
                        <div class="tile-body"><span>1</span></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h2>Welcome!</h2>
                    </div>
                    <div class="panel-body">
                        <p>You're logged in! Start managing your sprints and track your team's progress.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-member-layout>
