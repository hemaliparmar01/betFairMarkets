<button type="button"
                        class="fav-star [color:#b8ccdf] [font-size:15px] cursor-pointer [background:none] border-0 [width:24px] [transition:0.2s] text-center dark:[color:#4a6a88] dark:[&.active-fav]:[color:#f5b342] [&.active-fav]:[color:#f5b342] disabled:cursor-wait disabled:opacity-60 {{ $event['is_favorite'] ?? false ? 'active-fav' : '' }}"
                        data-action="favorite" data-requires-login="{{ $customerIsAuthenticated ? 'false' : 'true' }}" data-login-url="{{ route('login') }}" data-sport="football" data-event-key="{{ $event['unique_key'] }}"
                        data-event-id="{{ $event['sportsApiPro_match_id'] }}" data-team1="{{ $event['team1'] }}"
                        data-team2="{{ $event['team2'] }}" data-start-timestamp="{{ $event['timestamp'] }}"
                        aria-pressed="{{ $event['is_favorite'] ?? false ? 'true' : 'false' }}"
                        aria-label="Favorite {{ $event['team1'] }} vs {{ $event['team2'] }}"><i
                            class="fas fa-star"></i></button>
