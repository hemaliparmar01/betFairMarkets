<header class="sticky top-0 z-40 border-b border-slate-200 bg-white/90 backdrop-blur">
    <div class="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-900 text-white">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3l7 4v5c0 5-3.5 8-7 9-3.5-1-7-4-7-9V7l7-4z" />
                </svg>
            </div>

            <div>
                <h1 class="text-sm font-semibold text-slate-900">Admin Panel</h1>
                <p class="text-xs text-slate-500">Management Dashboard</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button
                type="button"
                class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 transition hover:bg-slate-50"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2.03 2.03 0 0118 14.16V11a6 6 0 10-12 0v3.16c0 .54-.21 1.06-.6 1.44L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
            </button>

            <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2">
                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-900 text-sm font-semibold text-white">
                    A
                </div>

                <div class="hidden sm:block">
                    <p class="text-sm font-medium text-slate-900">{{ auth('web')->user()->name }}</p>
                    <p class="text-xs text-slate-500">{{ auth('web')->user()->email }}</p>
                </div>
            </div>
        </div>
    </div>
</header>
