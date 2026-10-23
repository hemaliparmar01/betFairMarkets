<div class="flex flex-col gap-4 border-t px-5 py-4 sm:flex-row sm:items-center sm:justify-between theme-border">

    {{-- LEFT: Total records --}}
    <div class="flex flex-col gap-4 border-t px-5 py-4 sm:flex-row sm:items-center sm:justify-between theme-border">
        <p class="text-sm theme-text-muted">
            Showing
            <span class="font-semibold">{{ $paginator->firstItem() ?? 0 }}</span>
            to
            <span class="font-semibold">{{ $paginator->lastItem() ?? 0 }}</span>
            of
            <span class="font-semibold">{{ $paginator->total() }}</span>
            records
        </p>

    </div>

    {{-- RIGHT: Controls --}}
    <div class="flex items-center gap-3">

        {{-- Prev --}}
        <a href="{{ $paginator->previousPageUrl() }}"
            class="rounded-xl px-3 py-2 text-sm font-semibold theme-panel-subtle {{ $paginator->onFirstPage() ? 'pointer-events-none opacity-50' : '' }}">
            Prev
        </a>

        {{-- Page dropdown --}}
        <select class="betguru-select min-w-[130px]"
            onchange=" const url = new window.URL(window.location.href); url.searchParams.set('per_page', this.value); window.location.href = url.toString();">
            <option value="5" {{ request('per_page', 10) == 5 ? 'selected' : '' }}>5</option>
            <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10</option>
            <option value="15" {{ request('per_page', 10) == 15 ? 'selected' : '' }}>15</option>
            <option value="25" {{ request('per_page', 10) == 25 ? 'selected' : '' }}>25</option>
            <option value="50" {{ request('per_page', 10) == 50 ? 'selected' : '' }}>50</option>
        </select>

        {{-- Next --}}
        <a href="{{ $paginator->nextPageUrl() }}"
            class="rounded-xl px-3 py-2 text-sm font-semibold theme-panel-subtle {{ $paginator->hasMorePages() ? '' : 'pointer-events-none opacity-50' }}">
            Next
        </a>

    </div>
</div>
