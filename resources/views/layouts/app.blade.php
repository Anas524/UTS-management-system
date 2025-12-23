<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Universal Trade Services')</title>

    <link rel="icon" type="image/png" href="{{ asset('images/UTS.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/UTS.png') }}">

    {{-- Your main site CSS --}}
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">

    {{-- Google font --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300..700&display=swap" rel="stylesheet">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    @stack('styles')

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    {{-- Tailwind via CDN with config – but NOT on the home route (Bootstrap page) --}}
    @if (!request()->routeIs('home'))
    {{-- 1) Load Tailwind CDN first --}}
    <script src="https://cdn.tailwindcss.com"></script>

    {{-- 2) Then configure it --}}
    <script>
        tailwind.config = {
            corePlugins: {
                preflight: false, // don’t reset Bootstrap / your CSS
                container: false, // don’t take over .container
            },
            theme: {
                extend: {
                    colors: {
                        utsBlue: '#0f172a',
                        utsGold: '#f5b91f',
                    },
                    fontFamily: {
                        plus: ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
                    },
                },
            },
        };
    </script>
    @endif

    {{-- Page-specific head scripts (this is where Tailwind will be pushed) --}}
    @stack('head-scripts')
</head>

<body data-readonly="{{ auth()->user()?->role === 'consultant' ? 'true' : 'false' }}" class="@if(request()->routeIs('login') || request()->routeIs('register')) login-page @endif">

    @yield('content')

    <script>
        window.READ_ONLY = document.body.getAttribute('data-readonly') === 'true';
    </script>

    {{-- Page-specific libs BEFORE main scripts --}}
    @stack('before-scripts')

    <script src="{{ asset('js/po.js') }}"></script>
    <script src="{{ asset('js/payslip.js') }}"></script>
    <script src="{{ asset('js/sl.js') }}"></script>

    @stack('scripts')
</body>

</html>