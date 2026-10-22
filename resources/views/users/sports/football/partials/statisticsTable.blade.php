@php
    $periods = $event['statistics_periods'] ?? [];
    if ($periods === [] && ($event['live_stats'] ?? []) !== []) {
        foreach ($event['live_stats'] as $key => $values) {
            $percent = ($event['live_stats_meta'][$key]['percent'] ?? false) ? '%' : '';
            $periods['ALL']['Match statistics'][$key] = [
                'label' => $event['live_stats_meta'][$key]['label'] ?? \Illuminate\Support\Str::headline($key),
                'home' => isset($values['home']) ? $values['home'].$percent : null,
                'away' => isset($values['away']) ? $values['away'].$percent : null,
            ];
        }
    }
@endphp
@if ($periods === [])
    <p class="py-6 text-center text-sm text-slate-500 dark:text-slate-400">{{ __('Match statistics are not available from the provider yet.') }}</p>
@else
    <div class="space-y-4" data-statistics-periods>
        @foreach ($periods as $period => $groups)
            <details class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700" @if ($period === 'ALL') open @endif data-statistics-period="{{ $period }}">
                <summary class="cursor-pointer bg-slate-50 px-4 py-3 text-sm font-bold text-[#245f84] dark:bg-[#1b2d3b] dark:text-[#8ec8e9]">{{ __(['ALL' => 'Full match', '1ST' => 'First half', '2ND' => 'Second half'][$period] ?? $period) }}</summary>
                @foreach ($groups as $group => $items)
                    <section class="p-4">
                        <h4 class="mb-3 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __($group) }}</h4>
                        <div class="space-y-2">
                            @foreach ($items as $key => $item)
                                @php
                                    if ($period === 'ALL' && in_array($key, ['yellow_cards', 'red_cards', 'corners'], true) && isset($event['live_stats'][$key])) {
                                        $item = array_replace($item, $event['live_stats'][$key]);
                                    }
                                @endphp
                                <div class="grid grid-cols-[minmax(0,1fr)_minmax(0,2fr)_minmax(0,1fr)] items-center gap-2 text-xs" data-statistic="{{ $key }}">
                                    <strong class="break-words text-left text-[#245f84] dark:text-[#8ec8e9]">{{ $item['home'] ?? '—' }}</strong>
                                    <span class="text-center text-slate-600 dark:text-slate-300">{{ __($item['label']) }}</span>
                                    <strong class="break-words text-right text-[#245f84] dark:text-[#8ec8e9]">{{ $item['away'] ?? '—' }}</strong>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </details>
        @endforeach
    </div>
@endif
