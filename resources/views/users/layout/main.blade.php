<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'BF Markets · Data Terminal')</title>
    <meta name="author" content="Digital Analytics Group LLC">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Georgian:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" type="image/png" href="{{ url('favicon.png') }}">
    @vite(['resources/users/css/app.css', 'resources/users/js/app.js'])
    @stack('head')
</head>
<body class="min-w-0 max-[480px]:!px-2 max-[480px]:!pt-2 @if (request()->routeIs('cricket.*', 'icehockey.*', 'americanfootball.*', 'baseball.*', 'basketball.*', 'tennis.*', 'greyhounds.*', 'horseracing.*')) [&_a]:cursor-pointer [&_button:enabled]:cursor-pointer [&_select]:cursor-pointer [&_summary]:cursor-pointer @endif">
    <div class="app">
        @unless (request()->routeIs('login', 'register', 'customers.verification*', 'login.mobile.*', 'customers.password.*'))
            @include('users.layout.header')
        @endunless
        <main class="min-w-0 max-w-full [overflow-wrap:anywhere] [&_img]:max-w-full [&_img]:h-auto [&_input]:max-w-full [&_textarea]:max-w-full">@yield('content')</main>

        @include('users.layout.footer')
        <button type="button" class="fixed bottom-5 right-5 z-[9999] flex size-11 items-center justify-center rounded-full bg-[var(--color-primary)] text-2xl text-white" id="scroll-to-top" aria-label="Scroll to top">↑</button>
    </div>
    <script>
        document.getElementById('scroll-to-top')?.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
    </script>
    @stack('js')
</body>
</html>
