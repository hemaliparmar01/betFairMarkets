@php
    $details = $event['prematch_statistics']['overview'] ?? [];
    $overviewText = static function (mixed $value): string {
        if (is_array($value)) $value = $value['name'] ?? $value['round'] ?? null;
        return is_scalar($value) && (string) $value !== '' ? (string) $value : '—';
    };
    $start = $details['startTimestamp'] ?? $event['timestamp'] ?? null;
    $start = is_numeric($start) && (float) $start > 0 ? (int) $start : null;
    if ($start !== null && $start >= 100000000000) $start = intdiv($start, 1000);
    $overview = [
        __('Venue') => $overviewText($details['venue'] ?? null),
        __('Referee') => $overviewText($details['referee'] ?? null),
        __('Date & Time') => $start !== null ? \Carbon\Carbon::createFromTimestamp($start, getCurrentUserTimezone())->format('d M Y · H:i') : '—',
        __('Round') => $overviewText($details['Info']['round'] ?? $details['roundInfo']['round'] ?? null),
    ];
@endphp
<section>
    <h3 class="statistics-section-title">{{ __('Match Overview') }}</h3>
    <div class="statistics-grid">
        @foreach ($overview as $label => $value)
            <div class="statistics-card"><span class="statistics-label">{{ $label }}</span><strong class="statistics-value">{{ $value }}</strong></div>
        @endforeach
    </div>
</section>
