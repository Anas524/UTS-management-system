<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Universal Trade Services')</title>
    <link rel="icon" href="{{ asset('images/UTS.png') }}">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <!-- <link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;500;700&display=swap" rel="stylesheet"> -->

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300..700&display=swap" rel="stylesheet">
    @stack('styles')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<body>

    @yield('content')

    {{-- load page-specific libs BEFORE your main site script --}}
    @stack('before-scripts')

    <script src="{{ asset('js/po.js') }}"></script>
    @stack('scripts')
</body>

</html>