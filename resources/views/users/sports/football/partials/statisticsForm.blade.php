<div class="statistics-grid">
@foreach (['homeTeam' => $event['team1'], 'awayTeam' => $event['team2']] as $side => $teamName)
    @php
        $team = is_array($values[$side] ?? null) ? $values[$side] : [];
        $form = $team['form'] ?? [];
        if (is_string($form)) {
            $form = preg_match('/^[WDL]+$/i', $form) ? str_split($form) : preg_split('/[\s·,|]+/u', $form, -1, PREG_SPLIT_NO_EMPTY);
        }
        if (is_array($form) && array_is_list($form)) $form = array_slice($form, 0, 5);
        $simpleForm = is_array($form) && !array_any($form, static fn ($item): bool => is_array($item));
        $extra = array_diff_key($team, array_flip(['avgRating', 'position', 'leaguePosition', 'points', 'value', ...($simpleForm ? ['form'] : [])]));
    @endphp
    <article class="statistics-card">
        <div class="statistics-team-header">
            <h4>{{ $teamName }}</h4>
            <div class="statistics-team-rating">
                <span class="statistics-label">{{ __('Rating') }}</span>
                <strong class="statistics-rating">{{ is_scalar($team['avgRating'] ?? null) ? $team['avgRating'] : '—' }}</strong>
                <details class="statistics-rating-info"><summary aria-label="{{ __('About average rating') }}">i</summary><span role="tooltip"><strong>Average Rating (1-10):</strong> A composite performance score derived from goals, xG, shooting, passing, duels, and defensive actions. Above 7.8 is excellent.</span></details>
            </div>
        </div>
        <div class="statistics-metrics">
            @foreach ([__('Position') => $team['position'] ?? $team['leaguePosition'] ?? null, $values['label'] ?? __('Points') => $team['points'] ?? $team['value'] ?? null] as $label => $metric)
                <div><span class="statistics-label">{{ $label }}</span><span class="statistics-value">{{ is_scalar($metric) ? $metric : '—' }}</span>@if (is_array($metric) && $metric !== []) @include('users.sports.football.partials.statisticsValues', ['values' => $metric]) @endif</div>
            @endforeach
        </div>
        @if ($simpleForm && $form !== [])
            <span class="statistics-label mt-3">{{ __('Recent form') }}</span>
            <div class="statistics-form">
            @foreach ($form as $result)
                @php $outcome = strtoupper((string) $result); @endphp
                <span class="{{ ['W' => 'form-win', 'D' => 'form-draw', 'L' => 'form-loss'][$outcome] ?? '' }}" title="{{ ['W' => __('Win'), 'D' => __('Draw'), 'L' => __('Loss')][$outcome] ?? $outcome }}">{{ $outcome }}</span>
            @endforeach
            </div>
        @else
            <p class="statistics-label mt-3">{{ __('Recent form unavailable') }}</p>
        @endif
        @if ($extra !== []) @include('users.sports.football.partials.statisticsValues', ['values' => $extra]) @endif
    </article>
@endforeach
</div>
@php $extra = array_diff_key($values, array_flip(['homeTeam', 'awayTeam', 'label'])); @endphp
@if ($extra !== []) @include('users.sports.football.partials.statisticsValues', ['values' => $extra]) @endif
