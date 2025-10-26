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
                        <li class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            <a class="withripple" href="{{ url('/dashboard') }}">
                                <span class="icon"><i class="material-icons">home</i></span>
                                <span>Dashboard</span>
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('manage-instances.*') ? 'active' : '' }}">
                            <a class="withripple" href="{{ route('manage-instances.index') }}">
                                <span class="icon"><i class="material-icons">dns</i></span>
                                <span>Manage Instances</span>
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('monitored-users.*') ? 'active' : '' }}">
                            <a class="withripple" href="{{ route('monitored-users.index') }}">
                                <span class="icon"><i class="material-icons">people</i></span>
                                <span>Manage Users</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>
