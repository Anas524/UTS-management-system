<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Universal Trade Services')</title>
    <link rel="icon" href="{{ asset('images/UTS.png') }}">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;500;700&display=swap" rel="stylesheet">
    @stack('styles')
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<body>

    @yield('content')

    {{-- load page-specific libs BEFORE your main site script --}}
    @stack('before-scripts')

    @stack('scripts')
</body>

</html>