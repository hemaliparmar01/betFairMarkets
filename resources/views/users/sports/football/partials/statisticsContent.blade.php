<div class="space-y-6" data-statistics-content>
    @if ($event['status'] !== 'Prematch')
        <section>
            <h3 class="mb-3 text-sm font-bold text-[#12324a] dark:text-slate-100">{{ $event['status'] === 'Live' ? __('Live statistics') : __('Match statistics') }}</h3>
            @include('users.sports.football.partials.statisticsTable', ['event' => $event])
        </section>
    @endif
    @foreach (['pregame-form' => 'Pre-match form', 'h2h' => 'Head-to-head', 'streaks' => 'Team streaks'] as $section => $title)
        <section>
            <h3 class="mb-3 text-sm font-bold text-[#12324a] dark:text-slate-100">{{ $event['status'] === 'Prematch' && $section === 'pregame-form' ? __('Team Form') : __($title) }}</h3>
            @if (($event['prematch_statistics'][$section] ?? []) !== [])
                @include('users.sports.football.partials.'.['pregame-form' => 'statisticsForm', 'h2h' => 'statisticsH2h', 'streaks' => 'statisticsStreaks'][$section], ['values' => $event['prematch_statistics'][$section], 'event' => $event])
            @else
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('No data available') }}</p>
            @endif
            @if (isset($event['statistics_requests'][$section]) && ! $event['statistics_requests'][$section]['available'])
                <p class="mt-2 text-xs text-amber-600 dark:text-amber-400">{{ __('The latest request was unsuccessful. Any previously received data is retained.') }}</p>
            @endif
        </section>
    @endforeach
    @if ($event['status'] === 'Prematch')
        @include('users.sports.football.partials.statisticsOverview')
        @include('users.sports.football.partials.statisticsSeason')
    @endif
</div>
