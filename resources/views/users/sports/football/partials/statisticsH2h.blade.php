@php $duel = $values['teamDuel'] ?? []; @endphp
@if (is_array($duel) && array_intersect(['homeWins', 'awayWins', 'draws'], array_keys($duel)) !== [])
    <div class="statistics-card statistics-h2h">
        @foreach (['homeWins' => __('Home wins'), 'draws' => __('Draws'), 'awayWins' => __('Away wins')] as $key => $label)
            <div><span class="statistics-value">{{ is_scalar($duel[$key] ?? null) ? $duel[$key] : '—' }}</span><span class="statistics-label">{{ $label }}</span></div>
        @endforeach
    </div>
    @php
        $remaining = $values;
        $remaining['teamDuel'] = array_diff_key($duel, array_flip(['homeWins', 'draws', 'awayWins']));
        if ($remaining['teamDuel'] === []) unset($remaining['teamDuel']);
    @endphp
    @if ($remaining !== []) @include('users.sports.football.partials.statisticsValues', ['values' => $remaining]) @endif
@else
    @include('users.sports.football.partials.statisticsValues', ['values' => $values])
@endif
