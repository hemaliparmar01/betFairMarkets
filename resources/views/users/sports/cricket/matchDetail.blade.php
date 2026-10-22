@extends('users.layout.main')

@section('title', $match['team1'].' vs '.$match['team2'].' · BF Markets')

@section('content')
    @include('users.layout.sports')

    <div class="my-5">
        <a href="{{ route('cricket.home') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-[#1a6b9c] hover:text-[#0b2a40] dark:text-[#6aafdf]">
            <i class="fas fa-arrow-left"></i> Back to matches
        </a>
    </div>

    <section class="overflow-hidden rounded-2xl border border-[#dbe7f2] bg-white shadow-sm dark:border-[#2a3f50] dark:bg-[#162532]">
        <div class="bg-gradient-to-r from-[#0b2a40] to-[#1a6b9c] px-4 py-6 text-white sm:px-8">
            <div class="mb-3 text-center text-xs font-bold uppercase tracking-[0.14em] text-[#b9def3]">{{ $match['tournament'] }}</div>
            <div class="grid grid-cols-[minmax(0,1fr)_minmax(130px,200px)_minmax(0,1fr)] items-center gap-2 text-center sm:grid-cols-[minmax(0,1fr)_260px_minmax(0,1fr)] sm:gap-4">
                <div class="min-w-0">
                    <h1 class="flex items-center justify-center gap-2 text-base font-bold sm:text-2xl">
                        @if ($match['home_team_logo'] ?? null)<img src="{{ $match['home_team_logo'] }}" alt="" class="size-7 shrink-0 object-contain" onerror="this.remove()">@endif
                        <span class="min-w-0 break-words">{{ $match['team1'] }}</span>
                    </h1>
                </div>
                <div class="rounded-xl bg-white px-2 py-3 dark:bg-[#162532]">
                    @include('users.sports.cricket.partials.score', ['scoreDisplay' => $match['score_display'], 'status' => $match['status']])
                </div>
                <div class="min-w-0">
                    <h1 class="flex items-center justify-center gap-2 text-base font-bold sm:text-2xl">
                        <span class="min-w-0 break-words">{{ $match['team2'] }}</span>
                        @if ($match['away_team_logo'] ?? null)<img src="{{ $match['away_team_logo'] }}" alt="" class="size-7 shrink-0 object-contain" onerror="this.remove()">@endif
                    </h1>
                </div>
            </div>
            <div class="mt-4 text-center text-xs font-bold uppercase tracking-wide text-[#b9def3]">{{ $match['status'] }} · {{ $match['minutes'] }}</div>
            @if ($match['match_status_description'] ?? null)<p class="mt-3 text-center text-sm text-[#b9def3]">{{ $match['match_status_description'] }}</p>@endif
            @if ($match['score_display']['toss'])<p class="mt-3 text-center text-xs text-[#b9def3]" data-cricket-toss>{{ $match['score_display']['toss'] }}</p>@endif
        </div>

        <div class="p-4 sm:p-7">
            <div class="mb-5 flex flex-wrap items-end justify-between gap-2">
                <div>
                    <h2 class="text-xl font-extrabold text-[#0b2a40] dark:text-white">Cricket Markets</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Back odds only · Initial price compared with current price</p>
                </div>
                <span class="rounded-full bg-[#e8f2fc] px-3 py-1 text-xs font-bold text-[#1a6b9c] dark:bg-[#1a2e44] dark:text-[#6aafdf]">{{ count($match['markets']) }} markets</span>
            </div>

            @forelse ($match['markets'] as $market)
                <section class="mb-5 overflow-hidden rounded-2xl border border-[#dbe7f2] bg-white last:mb-0 dark:border-[#2a3f50] dark:bg-[#1a2a38]">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[#dbe7f2] bg-[#f3f8fd] px-4 py-3 dark:border-[#2a3f50] dark:bg-[#203746] sm:px-5">
                        <h3 class="text-sm font-extrabold uppercase tracking-wide text-[#1e4763] dark:text-[#b0c8dd]">{{ $market['label'] }}</h3>
                        <span class="text-xs font-semibold text-slate-400">{{ count($market['runners']) }} selections</span>
                    </div>

                    <div class="hidden grid-cols-[minmax(150px,1.25fr)_90px_100px_115px_minmax(220px,1fr)] items-center gap-4 border-b border-[#e8eef5] px-5 py-2 text-[10px] font-extrabold uppercase tracking-wider text-slate-400 dark:border-[#2a3f50] md:grid">
                        <span>Selection</span>
                        <span>Initial</span>
                        <span>Current</span>
                        <span>Movement</span>
                        <span>Back odds trend</span>
                    </div>

                    <div class="divide-y divide-[#e8eef5] dark:divide-[#2a3f50]">
                        @foreach ($market['runners'] as $runner)
                            <article class="grid grid-cols-2 items-center gap-x-4 gap-y-3 px-4 py-4 md:grid-cols-[minmax(150px,1.25fr)_90px_100px_115px_minmax(220px,1fr)] md:px-5">
                                <div class="col-span-2 min-w-0 md:col-span-1">
                                    <span class="mb-1 block text-[10px] font-extrabold uppercase tracking-wider text-slate-400 md:hidden">Selection</span>
                                    <div class="truncate text-sm font-extrabold text-[#0b2a40] dark:text-white" title="{{ $runner['name'] }}">{{ $runner['name'] }}</div>
                                </div>

                                <div>
                                    <span class="mb-1 block text-[10px] font-extrabold uppercase tracking-wider text-slate-400 md:hidden">Initial</span>
                                    <span class="font-mono text-base font-bold tabular-nums text-slate-500 dark:text-slate-300">
                                        @if ($checkAuthAndSubscription['display_odds'])
                                            {{ $runner['initial_back_odds'] > 1 ? number_format($runner['initial_back_odds'], 2) : '—' }}
                                        @else
                                            @include('lock_icon')
                                        @endif
                                    </span>
                                </div>

                                <div class="text-right md:text-left">
                                    <span class="mb-1 block text-[10px] font-extrabold uppercase tracking-wider text-slate-400 md:hidden">Current</span>
                                    <span @if ($checkAuthAndSubscription['display_odds'] && $runner['current_back_odds'] > 0) tabindex="0" data-cricket-odds-tooltip="{{ $runner['initial_back_odds'] > 1 ? number_format($runner['initial_back_odds'], 2) : '—' }} {{ $runner['direction'] > 0 ? '<<' : '>>' }} {{ number_format($runner['current_back_odds'], 2) }}" @endif class="inline-flex min-w-[72px] items-center justify-center gap-1 rounded-lg border border-[#b9d8ee] bg-[#e8f5ff] px-2.5 py-1.5 font-mono text-base font-extrabold tabular-nums text-[#0b2a40] dark:border-[#3d6d8d] dark:bg-[#17364b] dark:text-white">
                                        @if ($checkAuthAndSubscription['display_odds'])
                                            {{ number_format($runner['current_back_odds'], 2) }}
                                        @else
                                            @include('lock_icon')
                                        @endif
                                    </span>
                                </div>

                                <div class="col-span-2 md:col-span-1">
                                    <span class="mb-1 block text-[10px] font-extrabold uppercase tracking-wider text-slate-400 md:hidden">Movement</span>
                                    @if ($checkAuthAndSubscription['display_odds'])
                                        @if ($runner['initial_back_odds'] > 1)
                                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-extrabold {{ $runner['direction'] < 0 ? 'bg-red-50 text-[#c74e4e] dark:bg-red-950/30' : ($runner['direction'] > 0 ? 'bg-emerald-50 text-[#1f9a6e] dark:bg-emerald-950/30' : 'bg-slate-100 text-slate-500 dark:bg-slate-700') }}">
                                            @if ($runner['direction'] < 0)
                                                <i class="fas fa-arrow-down"></i>
                                            @elseif ($runner['direction'] > 0)
                                                <i class="fas fa-arrow-up"></i>
                                            @else
                                                <i class="fas fa-minus"></i>
                                            @endif
                                            {{ $runner['change_percent'] > 0 ? '+' : '' }}{{ number_format($runner['change_percent'], 1) }}%
                                        </span>
                                        @else
                                            <span class="text-xs text-slate-400">{{ __('Waiting for opening odds') }}</span>
                                        @endif
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-extrabold">
                                            @include('lock_icon')
                                        </span>
                                    @endif
                                </div>

                                <div class="col-span-2 md:col-span-1">
                                    @if ($checkAuthAndSubscription['display_odds'])
                                        @include('users.sports.cricket.partials.marketChart', ['history' => $runner['history']])
                                    @else
                                        @include('lock_icon')
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @empty
                <div class="rounded-xl border border-dashed border-slate-300 px-4 py-12 text-center text-sm text-slate-500 dark:border-slate-600 dark:text-slate-400">
                    Market data is not available for this match yet.
                </div>
            @endforelse
        </div>
    </section>
    @push('js')
        @vite('resources/users/js/sports/cricket.js')
        <script>
            (function() {
                let matchRefreshTimer = null;
                let matchRefreshInProgress = false;
                let matchRefreshQueued = false;

                async function refreshMatchDetail() {
                    if (matchRefreshInProgress || document.visibilityState !== 'visible') {
                        matchRefreshQueued = true;
                        return;
                    }

                    matchRefreshInProgress = true;

                    try {
                        const response = await fetch(window.location.href, {
                            headers: { 'Accept': 'text/html' },
                            cache: 'no-store',
                        });
                        if (!response.ok) throw new Error(`HTTP error: ${response.status}`);

                        const html = await response.text();
                        const nextDocument = new DOMParser().parseFromString(html, 'text/html');
                        const nextMain = nextDocument.querySelector('main');
                        const currentMain = document.querySelector('main');

                        if (nextMain && currentMain) {
                            document.querySelector('[data-cricket-tooltip]')?.remove();
                            currentMain.innerHTML = nextMain.innerHTML;
                        }
                    } catch (error) {
                        console.error('Match detail refresh failed:', error);
                    } finally {
                        matchRefreshInProgress = false;

                        if (matchRefreshQueued) {
                            matchRefreshQueued = false;
                            scheduleMatchDetailRefresh();
                        }
                    }
                }

                function scheduleMatchDetailRefresh() {
                    if (matchRefreshTimer !== null) return;

                    matchRefreshTimer = window.setTimeout(function() {
                        matchRefreshTimer = null;
                        refreshMatchDetail();
                    }, 500);
                }

                window.addEventListener('load', function() {
                    if (!window.Echo) return;

                    window.Echo.channel('sports.cricket.live')
                        .listen('.score.updated', scheduleMatchDetailRefresh);
                }, { once: true });

                document.addEventListener('visibilitychange', function() {
                    if (document.visibilityState === 'visible') {
                        scheduleMatchDetailRefresh();
                    }
                });
            })();
        </script>
    @endpush
@endsection
