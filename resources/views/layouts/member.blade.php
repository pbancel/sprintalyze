<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @component('layoutcomponents.head')
    @endcomponent

    <title>{{ $title ?? 'Sprintalyze' }}</title>
</head>

<body class="animated-content infobar-overlay">
    @component('layoutcomponents.topnav')
    @endcomponent

    <div id="wrapper">
        <div id="layout-static">
            @component('layoutcomponents.menu')
            @endcomponent

            <div class="static-content-wrapper">
                <div class="static-content">
                    <div class="page-content">
                        {{ $slot }}
                    </div>
                </div>

                @component('layoutcomponents.footer')
                @endcomponent

            </div>
        </div>
    </div>

    @component('layoutcomponents.js')
    @endcomponent

</body>
</html>
