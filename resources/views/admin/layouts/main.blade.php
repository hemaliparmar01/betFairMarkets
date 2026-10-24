<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Admin Panel' }}</title>

    @stack('css')

    @vite(['resources/admin/css/app.css', 'resources/admin/js/app.js'])
</head>
<body class="h-screen overflow-hidden bg-slate-100 text-slate-900 antialiased">
    <div class="flex h-full flex-col">

        <!-- Navbar -->
        @include('admin.layouts.navbar')

        <div class="flex flex-1 overflow-hidden">
            <!-- Sidebar -->
            @include('admin.layouts.sidebar')

            <!-- Main Content -->
            <main class="min-w-0 flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
                <div class="mx-auto max-w-7xl">
                    {{ $slot ?? '' }}

                    @yield('content')
                </div>
            </main>
        </div>

        <!-- Footer -->
        @include('admin.layouts.footer')
    </div>
    @stack('scripts')
</body>
</html>
