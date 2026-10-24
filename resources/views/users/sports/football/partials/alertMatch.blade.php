@if (request()->input('type') === 'prematch_drops')
    <div class="prematch-drops__layer prematch-drops__layer--top football-alert-top">
        <div class="prematch-drops__match">
            <span class="prematch-drops__sport-icon" aria-hidden="true">⚽</span>
            <div>
                <span class="prematch-drops__teams"><span data-goal-team="home">{{ $event['team1'] }}</span> vs <span data-goal-team="away">{{ $event['team2'] }}</span></span>
                <div class="prematch-drops__match-meta">
            @include('users.layout.leagueHeading', ['heading' => $matches])
                    <span class="prematch-drops__kickoff">◷ {{ $event['minutes'] }}</span>
                    <span class="match-score" data-score-status="{{ $event['status'] }}">{{ $event['score'] ?? '—' }}</span>
                </div>
            </div>
        </div>
        @if ($showFavorite ?? true)@include('users.sports.football.partials.alertFavorite')@endif
    </div>
@else
    <div class="football-alert-shock-heading">
        <div class="market-shock-card__badge">✹ Shock Detected</div>
        @if ($showFavorite ?? true)@include('users.sports.football.partials.alertFavorite')@endif
    </div>
    <div class="market-shock-card__match"><span class="market-shock-card__sport-icon" aria-hidden="true">⚽</span><span><span data-goal-team="home">{{ $event['team1'] }}</span> vs <span data-goal-team="away">{{ $event['team2'] }}</span></span></div>
    <div class="market-shock-card__league">
            @include('users.layout.leagueHeading', ['heading' => $matches])
    </div>
    <div class="market-shock-card__meta"><span>◷ {{ $event['minutes'] }}</span><span class="market-shock-card__detected">{{ __('Detected') }}: —</span></div>
@endif
