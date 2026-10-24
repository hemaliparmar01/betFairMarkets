@foreach ($values as $groupName => $streaks)
    @if (is_array($streaks) && array_is_list($streaks))
        <h4 class="statistics-label mb-2">{{ \Illuminate\Support\Str::headline((string) $groupName) }}</h4>
        <div class="statistics-streaks">
        @foreach ($streaks as $streak)
            @if (is_array($streak))
                @php
                    $title = $streak['name'] ?? $streak['title'] ?? '';
                    $accent = footballStreakClassification((string) $title);
                    $tag = $streak['team'] ?? null;
                    $tag = is_array($tag) ? ($tag['name'] ?? null) : ($tag === 'home' ? $event['team1'] : ($tag === 'away' ? $event['team2'] : $tag));
                    $remaining = array_diff_key($streak, array_flip(['name', 'title', 'value', 'team']));
                @endphp
                <article class="statistics-card statistics-streak-{{ $accent }}">
                    <span class="statistics-label">{{ $title }}</span>
                    <strong class="statistics-value">{{ is_scalar($streak['value'] ?? null) ? $streak['value'] : '—' }}</strong>
                    @if (is_scalar($tag))<span class="statistics-label">{{ $tag }}</span>@endif
                    @if ($remaining !== []) @include('users.sports.football.partials.statisticsValues', ['values' => $remaining]) @endif
                </article>
            @else
                <div class="statistics-card">{{ is_scalar($streak) ? $streak : '—' }}</div>
            @endif
        @endforeach
        </div>
    @else
        @include('users.sports.football.partials.statisticsValues', ['values' => [$groupName => $streaks]])
    @endif
@endforeach
