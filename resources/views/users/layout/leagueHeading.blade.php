@php
    $leagueCountry = $heading['country_name'] ?? null;
    $leagueCode = $heading['country_code'] ?? null;
    $leagueFlag = $heading['country_flag'] ?? null;
    $leagueLogo = $heading['tournament_logo'] ?? null;
    $leagueName = $heading['tournament_name'] ?? $heading['tournament'] ?? '';
@endphp
<span class="league-heading-content">
    @if ($leagueFlag)
        <img src="{{ $leagueFlag }}" alt="" class="league-heading-flag" width="18" height="14" loading="lazy" decoding="async" onerror="this.remove()">
    @elseif (is_string($leagueCode) && preg_match('/^[A-Z]{2}$/D', $leagueCode))
        <span class="league-heading-flag" aria-hidden="true">{{ mb_chr(127397 + ord($leagueCode[0])).mb_chr(127397 + ord($leagueCode[1])) }}</span>
    @endif
    <span class="league-heading-name">{{ $leagueCountry }}{{ $leagueCountry && $leagueName && $leagueName !== $leagueCountry ? ' - ' : '' }}{{ $leagueName !== $leagueCountry ? $leagueName : '' }}</span>
    @if ($leagueLogo && $leagueLogo !== $leagueFlag)
        <img src="{{ $leagueLogo }}" alt="" class="league-heading-logo" width="18" height="18" loading="lazy" decoding="async" onerror="this.remove()">
    @endif
</span>
