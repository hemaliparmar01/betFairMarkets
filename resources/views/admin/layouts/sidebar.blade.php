<aside class="w-48 shrink-0 overflow-y-auto border-r border-slate-200 bg-white p-3 sm:w-60">
    <nav class="space-y-2" aria-label="Admin navigation">
        @foreach (['admin.dashboard' => 'Dashboard', 'admin.users' => 'Customers', 'admin.subscriptions.index' => 'Subscriptions', 'admin.contact_messages.index' => 'Contact Messages'] as $routeName => $label)
            @if (Route::has($routeName))
                <a href="{{ route($routeName) }}" class="block rounded-xl px-3 py-3 text-sm font-semibold {{ request()->routeIs($routeName, $routeName.'.*') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">{{ $label }}</a>
            @endif
        @endforeach
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit" class="w-full rounded-xl px-3 py-3 text-left text-sm font-semibold text-red-700 hover:bg-red-50">Logout</button>
        </form>
    </nav>
</aside>
