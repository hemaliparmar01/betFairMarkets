                <div
                    class="event-actions flex w-[148px] items-center justify-end [gap:2px] max-[1024px]:w-[148px] max-[1024px]:justify-start max-[480px]:w-full max-[480px]:justify-center">
                    <button type="button"
                        class="live-stat-icon bg-transparent border-0 [color:var(--color-score-live)] [font-size:15px] [padding:4px_4px] cursor-pointer [border-radius:8px] [width:28px] text-center [transition:0.2s] disabled:cursor-not-allowed disabled:opacity-30 dark:[color:#6a8aa8] dark:[&:hover]:[background:#2a4050] dark:[&:hover]:[color:var(--color-text-dark)] [&:hover]:[background:#dce8f5] [&:hover]:[color:#b52a2a]"
                        @if ($checkAuthAndSubscription['display_odds'])
                            data-statistics-url="{{ route('football.matches.statistics', $event['sportsApiPro_match_id']) }}" data-action="livestats" data-match-id="{{ $event['sportsApiPro_match_id'] }}"
                            aria-expanded="false" aria-controls="live-stats-{{ $event['sportsApiPro_match_id'] }}"
                            title="Live Statistics"
                        @else
                            disabled title="Live Statistics (requires subscription)"
                        @endif
                        @disabled($event['status'] !== 'Live')>
                        <i class="fas fa-wave-square"></i>
                    </button>
                    <button type="button"
                        class="action-icon w-7 cursor-pointer rounded-lg border-0 bg-transparent px-1 py-1 text-center text-[15px] text-[#427292] transition hover:bg-[#dce8f5] hover:text-[var(--color-primary)] disabled:cursor-not-allowed disabled:opacity-30 dark:text-[#6a8aa8] dark:hover:bg-[#2a4050] dark:hover:text-[var(--color-text-dark)]"
                        aria-label="{{ __('Quick Stats') }}"
                        @if ($checkAuthAndSubscription['display_odds'])
                            data-statistics-url="{{ route('football.matches.statistics', $event['sportsApiPro_match_id']) }}" data-action="quickstats" data-match-id="{{ $event['sportsApiPro_match_id'] }}"
                            aria-expanded="false" aria-controls="quick-stats-{{ $event['sportsApiPro_match_id'] }}"
                            title="Quick Stats"
                        @else
                            disabled title="Quick Stats (requires subscription)"
                        @endif
                        @disabled($event['status'] !== 'Live')>
                        <i class="fas fa-chart-pie" aria-hidden="true"></i>
                    </button>
                    <button type="button"
                        class="action-icon bg-transparent border-0 [color:#427292] [font-size:15px] [padding:4px_4px] cursor-pointer [border-radius:8px] [width:28px] text-center [transition:0.2s] dark:[color:#6a8aa8] dark:[&:hover]:[background:#2a4050] dark:[&:hover]:[color:var(--color-text-dark)] [&:hover]:[background:#dce8f5] [&:hover]:[color:var(--color-primary)] [&.disabled]:[opacity:0.3] [&.disabled]:[pointer-events:none]"
                        data-action="chart" data-chart-url="{{ route('football.matches.show', $event['sportsApiPro_match_id']) }}" title="{{ __('Markets & odds charts') }}" aria-label="{{ __('Markets & odds charts') }}"><i class="fas fa-chart-simple"></i></button>
                    <button type="button"
                        class="action-icon bg-transparent border-0 [color:#427292] [font-size:15px] [padding:4px_4px] cursor-pointer [border-radius:8px] [width:28px] text-center [transition:0.2s] dark:[color:#6a8aa8] disabled:cursor-not-allowed disabled:opacity-30 dark:[&:hover]:[background:#2a4050] dark:[&:hover]:[color:var(--color-text-dark)] [&:hover]:[background:#dce8f5] [&:hover]:[color:var(--color-primary)] [&.disabled]:[opacity:0.3] [&.disabled]:[pointer-events:none]"
                        @if ($checkAuthAndSubscription['display_odds'])
                            data-action="stats" data-match-id="{{ $event['sportsApiPro_match_id'] }}" data-statistics-url="{{ route('football.matches.statistics', $event['sportsApiPro_match_id']) }}" aria-expanded="false" aria-controls="statistics-{{ $event['sportsApiPro_match_id'] }}" title="Statistics"
                        @else
                            disabled title="Statistics (requires subscription)"
                        @endif
                        ><i class="fas fa-chart-bar"></i></button>
                    <button type="button"
                        class="action-icon bg-transparent border-0 [color:#427292] [font-size:15px] [padding:4px_4px] cursor-pointer [border-radius:8px] [width:28px] text-center [transition:0.2s] disabled:cursor-not-allowed disabled:opacity-30 dark:[color:#6a8aa8] dark:[&:hover]:[background:#2a4050] dark:[&:hover]:[color:var(--color-text-dark)] [&:hover]:[background:#dce8f5] [&:hover]:[color:var(--color-primary)]"
                        @if ($checkAuthAndSubscription['display_odds'])
                            data-action="prediction" aria-expanded="false" aria-controls="prediction-{{ $event['sportsApiPro_match_id'] }}" data-requires-login="{{ $customerIsAuthenticated ? 'false' : 'true' }}" data-login-url="{{ route('login') }}" data-match-id="{{ $event['sportsApiPro_match_id'] }}"
                        @else
                            disabled title="Prediction (requires subscription)"
                        @endif
                        title="Prediction" @disabled($event['status'] !== 'Prematch')><i class="fas fa-brain"></i></button>
                </div>
