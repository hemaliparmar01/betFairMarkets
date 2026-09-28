@extends('users.layout.main')

@section('title', 'Fixtures · BF Markets')

@section('content')
    {{-- @php
        $sportMarkets = [
            'default' => '1X2',
            'options' => [
                [
                    'value' => '1X2',
                    'label' => 'Match Odds / 1X2',
                    'oddsCount' => 3,
                ],
                [
                    'value' => 'OU2.5',
                    'label' => 'Over/Under 2.5',
                    'oddsCount' => 2,
                ],
                [
                    'value' => 'OU0.5',
                    'label' => 'Over/Under 0.5',
                    'oddsCount' => 2,
                ],
                [
                    'value' => 'OU1.5',
                    'label' => 'Over/Under 1.5',
                    'oddsCount' => 2,
                ],
                [
                    'value' => 'OU3.5',
                    'label' => 'Over/Under 3.5',
                    'oddsCount' => 2,
                ],
                [
                    'value' => 'BTTS',
                    'label' => 'Both Teams To Score',
                    'oddsCount' => 2,
                ],
            ],
        ];
        $events = [
            [
                'id' => 'e1',
                'sport' => 'Soccer',
                'league' => 'Premier League',
                'home' => 'Liverpool',
                'away' => 'Arsenal',
                'status' => 'Live',
                'time' => '45\'',
                'score' => '2-1',
                'odds' => [2.1, 3.4, 3.8],
                'oddsType' => '1X2',
                'cards' => [
                    'home' => 'yellow',
                    'away' => null,
                ],
                'corners' => [
                    'home' => 4,
                    'away' => 2,
                ],
            ],
            [
                'id' => 'e2',
                'sport' => 'Soccer',
                'league' => 'Premier League',
                'home' => 'Chelsea',
                'away' => 'Tottenham',
                'status' => 'Live',
                'time' => '38\'',
                'score' => '0-0',
                'odds' => [2.3, 3.2, 3.1],
                'oddsType' => '1X2',
                'cards' => [
                    'home' => null,
                    'away' => 'yellow',
                ],
                'corners' => [
                    'home' => 3,
                    'away' => 1,
                ],
            ],
            [
                'id' => 'e3',
                'sport' => 'Soccer',
                'league' => 'La Liga',
                'home' => 'Barcelona',
                'away' => 'Real Madrid',
                'status' => 'Upcoming',
                'time' => '20:45',
                'score' => null,
                'odds' => [1.7, 3.8, 5.2],
                'oddsType' => '1X2',
                'cards' => [
                    'home' => null,
                    'away' => null,
                ],
                'corners' => [
                    'home' => 0,
                    'away' => 0,
                ],
            ],
            [
                'id' => 'e4',
                'sport' => 'Soccer',
                'league' => 'La Liga',
                'home' => 'Atletico Madrid',
                'away' => 'Sevilla',
                'status' => 'Upcoming',
                'time' => '22:00',
                'score' => null,
                'odds' => [1.9, 3.4, 4.2],
                'oddsType' => '1X2',
                'cards' => [
                    'home' => null,
                    'away' => null,
                ],
                'corners' => [
                    'home' => 0,
                    'away' => 0,
                ],
            ],
            [
                'id' => 'e5',
                'sport' => 'Soccer',
                'league' => 'Bundesliga',
                'home' => 'Bayern',
                'away' => 'Dortmund',
                'status' => 'Finished',
                'time' => 'FT',
                'score' => '3-2',
                'odds' => [1.45, 4.5, 6],
                'oddsType' => '1X2',
                'cards' => [
                    'home' => 'red',
                    'away' => 'yellow',
                ],
                'corners' => [
                    'home' => 7,
                    'away' => 3,
                ],
            ],
            [
                'id' => 'e6',
                'sport' => 'Soccer',
                'league' => 'Bundesliga',
                'home' => 'Leverkusen',
                'away' => 'Leipzig',
                'status' => 'Finished',
                'time' => 'FT',
                'score' => '1-1',
                'odds' => [2.2, 3.6, 3],
                'oddsType' => '1X2',
                'cards' => [
                    'home' => 'yellow',
                    'away' => null,
                ],
                'corners' => [
                    'home' => 5,
                    'away' => 6,
                ],
            ],
            [
                'id' => 'e7',
                'sport' => 'Soccer',
                'league' => 'Serie A',
                'home' => 'Inter',
                'away' => 'Milan',
                'status' => 'Live',
                'time' => '72\'',
                'score' => '1-1',
                'odds' => [2.3, 3.2, 3.1],
                'oddsType' => '1X2',
                'cards' => [
                    'home' => null,
                    'away' => 'yellow',
                ],
                'corners' => [
                    'home' => 5,
                    'away' => 6,
                ],
            ],
            [
                'id' => 'e8',
                'sport' => 'Soccer',
                'league' => 'Serie A',
                'home' => 'Juventus',
                'away' => 'Napoli',
                'status' => 'Upcoming',
                'time' => '19:30',
                'score' => null,
                'odds' => [2.1, 3.3, 3.5],
                'oddsType' => '1X2',
                'cards' => [
                    'home' => null,
                    'away' => null,
                ],
                'corners' => [
                    'home' => 0,
                    'away' => 0,
                ],
            ],
            [
                'id' => 'e9',
                'sport' => 'Soccer',
                'league' => 'Umaglesi Liga',
                'home' => 'FC Spaeri',
                'away' => 'FC Rustavi',
                'status' => 'Live',
                'time' => '19:00',
                'score' => '0-0',
                'odds' => [4, 3.75, 2.06],
                'oddsType' => '1X2',
                'cards' => [
                    'home' => null,
                    'away' => null,
                ],
                'corners' => [
                    'home' => 0,
                    'away' => 0,
                ],
            ],
        ];
        $initialGroups = collect($events)->filter(fn($event) => $event['status'] === 'Live')->groupBy('league')->sortKeys();
    @endphp --}}

    <!-- Yandex.Metrika counter -->
    <script type="text/javascript">
        (function(m, e, t, r, i, k, a) {
            m[i] = m[i] || function() {
                (m[i].a = m[i].a || []).push(arguments)
            };
            m[i].l = 1 * new Date();
            for (var j = 0; j < document.scripts.length; j++) {
                if (document.scripts[j].src === r) {
                    return;
                }
            }
            k = e.createElement(t), a = e.getElementsByTagName(t)[0], k.async = 1, k.src = r, a.parentNode.insertBefore(
                k, a)
        })(window, document, 'script', 'https://mc.yandex.ru/metrika/tag.js?id=109087271', 'ym');

        ym(109087271, 'init', {
            ssr: true,
            webvisor: true,
            clickmap: true,
            ecommerce: "dataLayer",
            referrer: document.referrer,
            url: location.href,
            accurateTrackBounce: true,
            trackLinks: true
        });
    </script>
    <noscript>
        <div><img src="https://mc.yandex.ru/watch/109087271" style="position:absolute; left:-9999px;" alt="" /></div>
    </noscript>

    <div>
        @include('users.layout.sports')

        @include('users.sports.football.football_filter')

        <div class="market-selector-wrap flex flex-wrap items-center gap-2.5 py-3.5   [gap:14px] [margin:4px_0_12px_0] [padding:6px_0_4px_0] [border-top:1px_solid_#e3ecf5] [padding-top:12px] [transition:border-color_0.3s] [justify-content:end] dark:[border-top-color:#2a3f50] dark:[&.hidden]:[display:none] [&.hidden]:[display:none]!" id="marketSelectorWrap">
            <span class="market-label font-semibold [color:#1e4763] [font-size:14px] [transition:color_0.3s]" id="marketLabel">{{ __('Market:') }}</span>
            <select class="market-dropdown min-w-[200px] rounded-lg border px-3 py-2 bg-white [border:1px_solid_#ccdbe9] [border-radius:40px] [padding:5px_20px_5px_18px] font-medium [font-size:14px] [color:#0b2a40] cursor-pointer [outline:none] [background:#fafdff] [transition:0.3s] [min-width:180px] dark:[background:#1f3444] dark:[border-color:#3a5568] dark:[color:#e8edf2] max-[800px]:[min-width:140px]" id="marketDropdown">
                @foreach ($getAllMarkets['options'] as $option)
                    <option value="{{ $option['value'] }}" data-odds-count="{{ $option['oddsCount'] }}">{{ $option['label'] }}</option>
                @endforeach
            </select>
        </div>

        @include('users.sports.football.footballDetails',["data" => $data])
    </div>
@endsection
