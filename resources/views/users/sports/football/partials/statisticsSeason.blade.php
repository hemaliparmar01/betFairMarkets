<section>
    <h3 class="statistics-section-title">{{ __('Team Season Statistics') }}</h3>
    <div class="statistics-grid">
        @foreach (['home-season' => $event['team1'], 'away-season' => $event['team2']] as $section => $team)
            @php $season = $event['prematch_statistics'][$section] ?? []; @endphp
            <article class="statistics-card">
                <h4>{{ $team }}</h4>
                @if ($season === [])<p class="statistics-label">{{ __('No data available') }}</p>@endif
                <div class="statistics-metrics">
                    @foreach (['avgRating' => __('Average rating'), 'matches' => __('Matches'), 'awardedMatches' => __('Awarded matches')] as $key => $label)
                        <div><span class="statistics-label">{{ $label }}</span><strong class="statistics-value">{{ is_scalar($season[$key] ?? null) ? $season[$key] : '—' }}</strong></div>
                    @endforeach
                </div>
            </article>
        @endforeach
    </div>
</section>
