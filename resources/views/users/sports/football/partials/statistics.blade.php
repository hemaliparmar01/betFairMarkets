<div id="statistics-{{ $event['sportsApiPro_match_id'] }}" data-statistics-panel="{{ $event['sportsApiPro_match_id'] }}"
    role="dialog" aria-modal="true" aria-labelledby="statistics-title-{{ $event['sportsApiPro_match_id'] }}" tabindex="-1"
    class="fixed inset-0 z-[80] hidden overflow-y-auto bg-slate-950/60 p-3 backdrop-blur-sm sm:p-6">
    <div class="relative mx-auto my-4 w-full max-w-3xl overflow-hidden rounded-2xl border border-[#dbe7f2] bg-white shadow-2xl dark:border-[#2a3f50] dark:bg-[#162532] sm:my-8">
        <header class="relative bg-gradient-to-r from-[#153f5c] to-[#2478a8] px-5 py-5 pr-14 text-white">
            <h2 id="statistics-title-{{ $event['sportsApiPro_match_id'] }}" class="text-sm font-bold">{{ __('Statistics') }}</h2>
            <p class="mt-1 text-sm">{{ $event['team1'] }} <strong data-score-status="{{ $event['status'] }}" class="match-score match-score-on-dark px-2">{{ $event['score'] }}</strong> {{ $event['team2'] }}</p>
            <button type="button" data-close-statistics aria-label="{{ __('Close statistics') }}" class="absolute right-3 top-3 flex h-9 w-9 items-center justify-center rounded-full bg-white/15 hover:bg-white/25"><i class="fas fa-times" aria-hidden="true"></i></button>
        </header>
        <div class="px-5 pt-4 text-xs text-slate-500 dark:text-slate-400" data-statistics-loading role="status" hidden>{{ __('Loading statistics…') }}</div>
        <div class="px-5 pt-4 text-xs text-amber-600 dark:text-amber-400" data-statistics-error role="alert" hidden></div>
        <div class="p-5" data-statistics-body>
            @include('users.sports.football.partials.statisticsContent', ['event' => $event])
        </div>
    </div>
</div>
