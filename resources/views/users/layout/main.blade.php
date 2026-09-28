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
    @vite(['resources/users/css/app.css', 'resources/users/js/app.js'])
    @stack('head')
</head>
<body class="min-w-0 max-[480px]:!px-2 max-[480px]:!pt-2">
    <div class="app mx-auto w-full rounded-3xl bg-white px-6 pb-6 pt-[18px] shadow-[0_16px_48px_rgba(0,20,40,0.06)] max-[800px]:p-3.5 dark:[background:#1a2a38] dark:[border-color:#2a3f50] [max-width:1480px] [margin:0_auto] [background:white] [border-radius:32px] [box-shadow:0_16px_48px_rgba(0,_20,_40,_0.06)] [padding:20px_28px_32px] [transition:background_0.3s] max-[800px]:[padding:14px] [width:100%] [max-width:100%] [border-radius:24px] [padding:18px_24px_24px] max-[768px]:[padding:14px_14px_20px] max-[768px]:[border-radius:16px]">
        @include('users.layout.header')
        <main class="min-w-0 max-w-full [overflow-wrap:anywhere] [&_img]:max-w-full [&_img]:h-auto [&_input]:max-w-full [&_textarea]:max-w-full">@yield('content')</main>

        @include('users.layout.footer')
        <button type="button" class="fixed bottom-5 right-5 z-[9999] flex size-11 items-center justify-center rounded-full bg-[#0b2a40] text-2xl text-white" id="scroll-to-top" aria-label="Scroll to top">↑</button>
    </div>
    <script>
        document.getElementById('scroll-to-top')?.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
    </script>
    @stack('js')
</body>
</html>
