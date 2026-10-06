@php
    $odds = array_map(static fn (array $point): float => (float) $point['odds'], $history);
    $minimum = min($odds);
    $maximum = max($odds);
    $range = max(0.01, $maximum - $minimum);
    $pointCount = count($odds);
    $coordinates = [];

    foreach ($odds as $index => $odd) {
        $x = $pointCount === 1 ? 140 : 8 + (($index / ($pointCount - 1)) * 264);
        $y = $maximum === $minimum ? 40 : 70 - ((($odd - $minimum) / $range) * 60);
        $coordinates[] = round($x, 2).','.round($y, 2);
    }
@endphp

<div class="rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-2 dark:border-[#30495c] dark:bg-[#172936]">
    <svg viewBox="0 0 280 80" class="h-16 w-full" role="img" aria-label="Back odds movement chart">
        <line x1="8" y1="70" x2="272" y2="70" stroke="currentColor" class="text-slate-200 dark:text-slate-700" />
        <line x1="8" y1="10" x2="8" y2="70" stroke="currentColor" class="text-slate-200 dark:text-slate-700" />
        <polyline points="{{ implode(' ', $coordinates) }}" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="text-[#1a6b9c]" />
        @foreach ($coordinates as $coordinate)
            @php [$circleX, $circleY] = explode(',', $coordinate); @endphp
            <circle cx="{{ $circleX }}" cy="{{ $circleY }}" r="3" fill="currentColor" class="text-[#1a6b9c]" />
        @endforeach
    </svg>
    <div class="flex items-center justify-between text-[10px] font-semibold text-slate-400">
        <span>{{ number_format($odds[0], 2) }}</span>
        <span>Initial → Current</span>
        <span>{{ number_format($odds[array_key_last($odds)], 2) }}</span>
    </div>
</div>
