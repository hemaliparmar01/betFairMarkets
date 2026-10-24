<div class="prematch-drops__match">
    <span class="prematch-drops__sport-icon" aria-hidden="true">⚽</span>
    <div>
        <span class="prematch-drops__teams">{{ $event['team1'] }} vs {{ $event['team2'] }}</span>
        <div class="prematch-drops__match-meta">
            @include('users.layout.leagueHeading', ['heading' => $matches])
            <span class="prematch-drops__kickoff">◷ {{ $event['minutes'] }}</span>
            <span class="match-score" data-score-status="{{ $event['status'] }}">{{ $event['score'] ?? '—' }}</span>
            @include('users.sports.football.partials.alertFavorite')
            @if ($loop->first && $loop->parent->first)
                @include('users.sports.football.partials.alertActions')
            @endif
        </div>
    </div>
</div>
