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
        @auth
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
        @endauth
    </ul>
</header>
