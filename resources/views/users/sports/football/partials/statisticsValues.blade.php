<dl class="space-y-3 text-sm">
    @foreach ($values as $key => $value)
        @php
            $label = match ((string) $key) {
                'homeTeam', 'homeWins' => $event['team1'],
                'awayTeam', 'awayWins' => $event['team2'],
                'avgRating' => __('Average rating'),
                'value' => ($section ?? '') === 'pregame-form' ? ($event['prematch_statistics']['pregame-form']['label'] ?? __('Points')) : __('Value'),
                'teamDuel' => __('Team head-to-head'),
                'managerDuel' => __('Manager head-to-head'),
                'head2head' => __('Head-to-head streaks'),
                default => is_int($key) ? '' : __(\Illuminate\Support\Str::headline($key)),
            };
        @endphp
        <div class="rounded-lg bg-slate-50 p-3 dark:bg-[#1b2d3b]">
            @if ($label !== '') <dt class="mb-1 text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $label }}</dt> @endif
            <dd class="break-words text-[#12324a] dark:text-slate-200">
                @if (is_array($value) && $value !== [])
                    @if (array_is_list($value) && ! array_any($value, static fn ($item): bool => is_array($item)))
                        {{ implode(' · ', array_map(static fn ($item) => $item === null ? '—' : (string) $item, $value)) }}
                    @else
                        @include('users.sports.football.partials.statisticsValues', ['values' => $value, 'event' => $event])
                    @endif
                @elseif ($value === null || $value === [])
                    <span class="text-slate-400">{{ __('Not available') }}</span>
                @elseif (is_bool($value))
                    {{ $value ? __('Yes') : __('No') }}
                @elseif ($key === 'team' && in_array($value, ['home', 'away'], true))
                    {{ $value === 'home' ? $event['team1'] : $event['team2'] }}
                @else
                    {{ $value }}
                @endif
            </dd>
        </div>
    @endforeach
</dl>
