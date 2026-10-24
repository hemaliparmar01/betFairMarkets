<div class="odds-group football-alert-markets">
    @if ($checkAuthAndSubscription['display_odds'])
        @forelse ($event['odds_markets'] ?? [] as $market)
            <div data-football-odds-source="{{ $market['source'] }}" class="football-alert-market">
                <span class="football-alert-provider">{{ $market['source'] }}</span>
                @foreach ($market['runners'] as $odd)
                    @if (request()->input('type') === 'prematch_drops')
                        <div class="prematch-drops__data-row">
                            <span class="alert-unavailable" title="{{ __('Drop status unavailable') }}">—</span>
                            @include('users.sports.football.partials.alertFixture')
                            <span class="prematch-drops__market" data-label="Market">{{ $market['label'] }}<small class="alert-source">{{ $market['source'] }}</small></span>
                            <span class="prematch-drops__selection" data-label="Selection">{{ $odd['runner'] }}</span>
                            <div class="prematch-drops__movement" data-label="Odds movement">
                                @include('users.sports.football.partials.alertOdds')
                            </div>
                            <span class="alert-unavailable" title="{{ __('Opening-baseline drop percentage unavailable') }}">—</span>
                            <span class="alert-unavailable" title="{{ __('Drop start time unavailable') }}">—</span>
                            <a class="prematch-drops__chart" data-action="chart" data-chart-url="{{ route('football.matches.show', $event['sportsApiPro_match_id']) }}" href="{{ route('football.matches.show', $event['sportsApiPro_match_id']) }}" aria-label="{{ __('View market chart') }}" title="{{ __('View market chart') }}"><i class="fas fa-chart-bar" aria-hidden="true"></i></a>
                        </div>
                    @else
                        <article class="football-alert market-shock-card football-shock-item">
                            @include('users.sports.football.partials.alertMatch', ['showFavorite' => $loop->first && $loop->parent->first])
                            <div data-football-odds-source="{{ $market['source'] }}">
                                <div class="market-shock-card__details">
                                    <div><span class="market-shock-card__detail-label">Market</span><span class="market-shock-card__detail-value">{{ $market['label'] }}<small class="alert-source">{{ $market['source'] }}</small></span></div>
                                    <div><span class="market-shock-card__detail-label">Selection</span><span class="market-shock-card__detail-value market-shock-card__selection">{{ $odd['runner'] }}</span></div>
                                </div>
                                @include('users.sports.football.partials.alertOdds')
                            </div>
                            <div class="market-shock-card__drop"><span class="market-shock-card__percentage alert-unavailable" title="{{ __('Verified shock percentage unavailable') }}">—</span><span class="market-shock-card__threshold">{{ __('Threshold / window') }} —</span></div>
                            <a class="market-shock-card__chart alert-chart-link" data-action="chart" data-chart-url="{{ route('football.matches.show', $event['sportsApiPro_match_id']) }}" href="{{ route('football.matches.show', $event['sportsApiPro_match_id']) }}"><i class="fas fa-chart-bar" aria-hidden="true"></i> {{ __('Chart') }}</a>
                            @if ($loop->first && $loop->parent->first)
                                @include('users.sports.football.partials.alertActions')
                            @endif
                        </article>
                    @endif
                @endforeach
            </div>
        @empty
            <span class="text-center text-xs text-[#7891a5] dark:text-[#b0c4d4]">{{ __('Odds unavailable') }}</span>
        @endforelse
    @else
        <div class="flex min-h-8 items-center justify-center">@include('lock_icon')</div>
    @endif
</div>
