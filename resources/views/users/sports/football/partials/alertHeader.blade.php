@if (request()->input('type') === 'prematch_drops')
<header class="prematch-drops__header">
                <div class="prematch-drops__heading">
                    <span class="prematch-drops__icon">
                        <svg viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 7l8 8 5-5 11 11"/>
                            <path d="M21 21h7v-7"/>
                        </svg>
                    </span>
                    <div>
                        <div class="prematch-drops__title-row">
                            <h2 class="prematch-drops__title">Prematch Drops</h2>
                            <span class="prematch-drops__info">i</span>
                        </div>
                        <p class="prematch-drops__subtitle">Track ongoing price drops in real-time before the match starts</p>
                    </div>
                </div>
                @include('users.sports.football.partials.alertFilterLinks')
</header>
            <div class="prematch-drops__column-head" aria-hidden="true">
                <span>Status</span><span>Match / League</span><span>Market</span><span>Selection</span><span>Odds movement (baseline → current)</span><span>Drop %</span><span>Drop started</span><span>Chart</span>
            </div>
@else
<header class="market-shock-detector__header">
            <div class="market-shock-detector__heading">
                <span class="market-shock-detector__icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M13.2 1 4.8 13h6L9.9 23 19.2 10h-6.1L13.2 1Z" />
                    </svg>
                </span>

                <div>
                    <div class="market-shock-detector__title-row">
                        <h2 class="market-shock-detector__title">
                            Market Shock Detector
                        </h2>

                        <span class="market-shock-detector__info">i</span>
                    </div>

                    <p class="market-shock-detector__subtitle">
                        Detects sudden, significant market shocks in real-time
                    </p>
                </div>
            </div>
            @include('users.sports.football.partials.alertFilterLinks')
</header>
@endif
