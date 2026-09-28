@extends('users.layout.main')

@section('title', 'Market Activity Terminal · BF Markets')

@section('content')
@php
    $marketTags = [
        'match_odds' => [
            'label' => 'Match Odds',
            'cls' => 'mo',
        ],
        'ou_0_5' => [
            'label' => 'O/U 0.5',
            'cls' => 'ou',
        ],
        'ou_1_5' => [
            'label' => 'O/U 1.5',
            'cls' => 'ou',
        ],
        'ou_2_5' => [
            'label' => 'O/U 2.5',
            'cls' => 'ou',
        ],
        'ou_3_5' => [
            'label' => 'O/U 3.5',
            'cls' => 'ou',
        ],
        'ou_4_5' => [
            'label' => 'O/U 4.5',
            'cls' => 'ou',
        ],
        'btts' => [
            'label' => 'BTTS',
            'cls' => 'btts',
        ],
        'first_half' => [
            'label' => 'First Half',
            'cls' => 'fh',
        ],
        'fh_ou_0_5' => [
            'label' => 'FH O/U 0.5',
            'cls' => 'fh',
        ],
        'fh_ou_1_5' => [
            'label' => 'FH O/U 1.5',
            'cls' => 'fh',
        ],
        'correct_score' => [
            'label' => 'Correct Score',
            'cls' => 'cs',
        ],
        'win_market' => [
            'label' => 'Win Market',
            'cls' => 'win',
        ],
    ];
    $events = [
        [
            'id' => 1,
            'sport' => 'football',
            'sportIcon' => '⚽',
            'status' => 'LIVE',
            'time' => '75\'',
            'comp' => 'Premier League',
            'match' => 'Arsenal vs Chelsea',
            'score' => '2-1',
            'market' => 'match_odds',
            'marketStatus' => 'correct_score',
            'marketDisplayScore' => '2-1',
            'runners' => [
                [
                    'name' => 'Arsenal',
                    'back' => 1.85,
                    'backSize' => 45200,
                    'lay' => 1.88,
                    'laySize' => 42100,
                ],
                [
                    'name' => 'The Draw',
                    'back' => 3.9,
                    'backSize' => 18700,
                    'lay' => 4.1,
                    'laySize' => 16200,
                ],
                [
                    'name' => 'Chelsea',
                    'back' => 4.6,
                    'backSize' => 12300,
                    'lay' => 4.8,
                    'laySize' => 14500,
                ],
            ],
            'favIdx' => 0,
            'balance' => 'back',
            'momentum' => 'back',
            'delta' => '+18%',
            'volatility' => 'high',
        ],
        [
            'id' => 2,
            'sport' => 'football',
            'sportIcon' => '⚽',
            'status' => 'LIVE',
            'time' => '62\'',
            'comp' => 'La Liga',
            'match' => 'Real Madrid vs Barcelona',
            'score' => '1-1',
            'market' => 'match_odds',
            'runners' => [
                [
                    'name' => 'Real Madrid',
                    'back' => 2.1,
                    'backSize' => 38500,
                    'lay' => 2.14,
                    'laySize' => 35200,
                ],
                [
                    'name' => 'The Draw',
                    'back' => 3.5,
                    'backSize' => 15400,
                    'lay' => 3.65,
                    'laySize' => 14800,
                ],
                [
                    'name' => 'Barcelona',
                    'back' => 3.2,
                    'backSize' => 22800,
                    'lay' => 3.35,
                    'laySize' => 24100,
                ],
            ],
            'favIdx' => 0,
            'balance' => 'balanced',
            'momentum' => 'neutral',
            'delta' => '+3%',
            'volatility' => 'medium',
        ],
        [
            'id' => 3,
            'sport' => 'football',
            'sportIcon' => '⚽',
            'status' => 'LIVE',
            'time' => 'HT',
            'comp' => 'Serie A',
            'match' => 'Juventus vs Milan',
            'score' => '0-0',
            'market' => 'match_odds',
            'runners' => [
                [
                    'name' => 'Juventus',
                    'back' => 2.45,
                    'backSize' => 28300,
                    'lay' => 2.5,
                    'laySize' => 26100,
                ],
                [
                    'name' => 'The Draw',
                    'back' => 2.9,
                    'backSize' => 21500,
                    'lay' => 3.05,
                    'laySize' => 22800,
                ],
                [
                    'name' => 'Milan',
                    'back' => 3.15,
                    'backSize' => 19700,
                    'lay' => 3.25,
                    'laySize' => 18400,
                ],
            ],
            'favIdx' => 0,
            'balance' => 'balanced',
            'momentum' => 'neutral',
            'delta' => '+2%',
            'volatility' => 'medium',
        ],
        [
            'id' => 4,
            'sport' => 'football',
            'sportIcon' => '⚽',
            'status' => 'LIVE',
            'time' => '72\'',
            'comp' => 'Bundesliga',
            'match' => 'Bayern vs Dortmund',
            'score' => '3-2',
            'market' => 'ou_2_5',
            'runners' => [
                [
                    'name' => 'Over 2.5',
                    'back' => 2.39,
                    'backSize' => 23200,
                    'lay' => 2.45,
                    'laySize' => 21000,
                ],
                [
                    'name' => 'Under 2.5',
                    'back' => 1.65,
                    'backSize' => 45200,
                    'lay' => 1.68,
                    'laySize' => 42100,
                ],
            ],
            'favIdx' => 1,
            'balance' => 'back',
            'momentum' => 'neutral',
            'delta' => '+5%',
            'volatility' => 'high',
        ],
        [
            'id' => 5,
            'sport' => 'football',
            'sportIcon' => '⚽',
            'status' => 'LIVE',
            'time' => '70\'',
            'comp' => 'Ligue 1',
            'match' => 'PSG vs Lyon',
            'score' => '0-0',
            'market' => 'ou_2_5',
            'runners' => [
                [
                    'name' => 'Over 2.5',
                    'back' => 1.95,
                    'backSize' => 35600,
                    'lay' => 1.98,
                    'laySize' => 33200,
                ],
                [
                    'name' => 'Under 2.5',
                    'back' => 1.95,
                    'backSize' => 34200,
                    'lay' => 1.99,
                    'laySize' => 32100,
                ],
            ],
            'favIdx' => 0,
            'balance' => 'balanced',
            'momentum' => 'neutral',
            'delta' => '+1%',
            'volatility' => 'medium',
        ],
        [
            'id' => 6,
            'sport' => 'football',
            'sportIcon' => '⚽',
            'status' => 'UP',
            'time' => 'in 25 min',
            'comp' => 'Champions League',
            'match' => 'Liverpool vs Inter',
            'score' => null,
            'market' => 'btts',
            'runners' => [
                [
                    'name' => 'Yes',
                    'back' => 1.75,
                    'backSize' => 52300,
                    'lay' => 1.78,
                    'laySize' => 48700,
                ],
                [
                    'name' => 'No',
                    'back' => 2.1,
                    'backSize' => 24100,
                    'lay' => 2.16,
                    'laySize' => 22800,
                ],
            ],
            'favIdx' => 0,
            'balance' => 'back',
            'momentum' => 'back',
            'delta' => '+12%',
            'volatility' => 'medium',
        ],
        [
            'id' => 7,
            'sport' => 'football',
            'sportIcon' => '⚽',
            'status' => 'UP',
            'time' => 'in 1h 10m',
            'comp' => 'Eredivisie',
            'match' => 'Ajax vs PSV',
            'score' => null,
            'market' => 'btts',
            'runners' => [
                [
                    'name' => 'Yes',
                    'back' => 1.6,
                    'backSize' => 18500,
                    'lay' => 1.63,
                    'laySize' => 17200,
                ],
                [
                    'name' => 'No',
                    'back' => 2.45,
                    'backSize' => 11300,
                    'lay' => 2.52,
                    'laySize' => 10600,
                ],
            ],
            'favIdx' => 0,
            'balance' => 'back',
            'momentum' => 'back',
            'delta' => '+8%',
            'volatility' => 'medium',
        ],
        [
            'id' => 8,
            'sport' => 'football',
            'sportIcon' => '⚽',
            'status' => 'UP',
            'time' => 'in 2h',
            'comp' => 'Serie A',
            'match' => 'Napoli vs Roma',
            'score' => null,
            'market' => 'first_half',
            'runners' => [
                [
                    'name' => 'Napoli',
                    'back' => 2.2,
                    'backSize' => 22500,
                    'lay' => 2.26,
                    'laySize' => 21100,
                ],
                [
                    'name' => 'The Draw',
                    'back' => 2.15,
                    'backSize' => 24300,
                    'lay' => 2.22,
                    'laySize' => 22800,
                ],
                [
                    'name' => 'Roma',
                    'back' => 3.9,
                    'backSize' => 8700,
                    'lay' => 4.1,
                    'laySize' => 8100,
                ],
            ],
            'favIdx' => 1,
            'balance' => 'balanced',
            'momentum' => 'neutral',
            'delta' => '+1%',
            'volatility' => 'low',
        ],
        [
            'id' => 9,
            'sport' => 'football',
            'sportIcon' => '⚽',
            'status' => 'UP',
            'time' => 'in 3h',
            'comp' => 'Primeira Liga',
            'match' => 'Benfica vs Porto',
            'score' => null,
            'market' => 'ou_3_5',
            'runners' => [
                [
                    'name' => 'Over 3.5',
                    'back' => 2.85,
                    'backSize' => 15200,
                    'lay' => 2.95,
                    'laySize' => 14500,
                ],
                [
                    'name' => 'Under 3.5',
                    'back' => 1.45,
                    'backSize' => 38000,
                    'lay' => 1.48,
                    'laySize' => 35200,
                ],
            ],
            'favIdx' => 1,
            'balance' => 'back',
            'momentum' => 'back',
            'delta' => '+15%',
            'volatility' => 'medium',
        ],
        [
            'id' => 10,
            'sport' => 'football',
            'sportIcon' => '⚽',
            'status' => 'LIVE',
            'time' => '68\'',
            'comp' => 'Scottish Premiership',
            'match' => 'Celtic vs Rangers',
            'score' => '2-0',
            'market' => 'correct_score',
            'marketDisplayScore' => '2-0',
            'runners' => [
                [
                    'name' => '2-0',
                    'back' => 2.1,
                    'backSize' => 17800,
                    'lay' => 2.14,
                    'laySize' => 16200,
                ],
            ],
            'favIdx' => 0,
            'balance' => 'back',
            'momentum' => 'back',
            'delta' => '+22%',
            'volatility' => 'high',
        ],
        [
            'id' => 11,
            'sport' => 'tennis',
            'sportIcon' => '🎾',
            'status' => 'LIVE',
            'time' => 'Set 2',
            'comp' => 'ATP Masters',
            'match' => 'Djokovic vs Alcaraz',
            'score' => '6-4, 3-2',
            'market' => 'match_odds',
            'runners' => [
                [
                    'name' => 'Djokovic',
                    'back' => 1.72,
                    'backSize' => 42800,
                    'lay' => 1.75,
                    'laySize' => 40200,
                ],
                [
                    'name' => 'Alcaraz',
                    'back' => 2.2,
                    'backSize' => 28400,
                    'lay' => 2.28,
                    'laySize' => 26900,
                ],
            ],
            'favIdx' => 0,
            'balance' => 'back',
            'momentum' => 'back',
            'delta' => '+22%',
            'volatility' => 'high',
        ],
        [
            'id' => 12,
            'sport' => 'tennis',
            'sportIcon' => '🎾',
            'status' => 'LIVE',
            'time' => 'Set 1',
            'comp' => 'WTA',
            'match' => 'Swiatek vs Sabalenka',
            'score' => '4-3',
            'market' => 'match_odds',
            'runners' => [
                [
                    'name' => 'Swiatek',
                    'back' => 1.95,
                    'backSize' => 21300,
                    'lay' => 1.99,
                    'laySize' => 20100,
                ],
                [
                    'name' => 'Sabalenka',
                    'back' => 1.95,
                    'backSize' => 19700,
                    'lay' => 2.02,
                    'laySize' => 18800,
                ],
            ],
            'favIdx' => 0,
            'balance' => 'balanced',
            'momentum' => 'neutral',
            'delta' => '+1%',
            'volatility' => 'medium',
        ],
        [
            'id' => 13,
            'sport' => 'tennis',
            'sportIcon' => '🎾',
            'status' => 'LIVE',
            'time' => 'Set 3',
            'comp' => 'ATP 500',
            'match' => 'Medvedev vs Zverev',
            'score' => '6-4, 4-6, 2-1',
            'market' => 'match_odds',
            'runners' => [
                [
                    'name' => 'Medvedev',
                    'back' => 1.65,
                    'backSize' => 28400,
                    'lay' => 1.68,
                    'laySize' => 27100,
                ],
                [
                    'name' => 'Zverev',
                    'back' => 2.35,
                    'backSize' => 19700,
                    'lay' => 2.42,
                    'laySize' => 18800,
                ],
            ],
            'favIdx' => 0,
            'balance' => 'back',
            'momentum' => 'back',
            'delta' => '+18%',
            'volatility' => 'high',
        ],
        [
            'id' => 14,
            'sport' => 'tennis',
            'sportIcon' => '🎾',
            'status' => 'LIVE',
            'time' => 'Set 2',
            'comp' => 'ATP Masters',
            'match' => 'Nadal vs Federer',
            'score' => '6-3, 2-4',
            'market' => 'match_odds',
            'runners' => [
                [
                    'name' => 'Nadal',
                    'back' => 1.55,
                    'backSize' => 38200,
                    'lay' => 1.58,
                    'laySize' => 36100,
                ],
                [
                    'name' => 'Federer',
                    'back' => 2.55,
                    'backSize' => 22300,
                    'lay' => 2.62,
                    'laySize' => 21100,
                ],
            ],
            'favIdx' => 0,
            'balance' => 'back',
            'momentum' => 'neutral',
            'delta' => '+3%',
            'volatility' => 'medium',
        ],
        [
            'id' => 15,
            'sport' => 'tennis',
            'sportIcon' => '🎾',
            'status' => 'UP',
            'time' => 'in 45 min',
            'comp' => 'WTA',
            'match' => 'Osaka vs Andreescu',
            'score' => null,
            'market' => 'match_odds',
            'runners' => [
                [
                    'name' => 'Osaka',
                    'back' => 1.85,
                    'backSize' => 16400,
                    'lay' => 1.89,
                    'laySize' => 15300,
                ],
                [
                    'name' => 'Andreescu',
                    'back' => 2.05,
                    'backSize' => 14200,
                    'lay' => 2.12,
                    'laySize' => 13500,
                ],
            ],
            'favIdx' => 0,
            'balance' => 'back',
            'momentum' => 'neutral',
            'delta' => '+2%',
            'volatility' => 'low',
        ],
        [
            'id' => 16,
            'sport' => 'tennis',
            'sportIcon' => '🎾',
            'status' => 'UP',
            'time' => 'in 1h 20m',
            'comp' => 'WTA',
            'match' => 'Barty vs Halep',
            'score' => null,
            'market' => 'match_odds',
            'runners' => [
                [
                    'name' => 'Barty',
                    'back' => 1.6,
                    'backSize' => 19800,
                    'lay' => 1.63,
                    'laySize' => 18500,
                ],
                [
                    'name' => 'Halep',
                    'back' => 2.45,
                    'backSize' => 11300,
                    'lay' => 2.52,
                    'laySize' => 10600,
                ],
            ],
            'favIdx' => 0,
            'balance' => 'back',
            'momentum' => 'back',
            'delta' => '+6%',
            'volatility' => 'medium',
        ],
        [
            'id' => 17,
            'sport' => 'tennis',
            'sportIcon' => '🎾',
            'status' => 'UP',
            'time' => 'in 2h',
            'comp' => 'ATP 250',
            'match' => 'Thiem vs Tsitsipas',
            'score' => null,
            'market' => 'match_odds',
            'runners' => [
                [
                    'name' => 'Thiem',
                    'back' => 2.3,
                    'backSize' => 12500,
                    'lay' => 2.38,
                    'laySize' => 11800,
                ],
                [
                    'name' => 'Tsitsipas',
                    'back' => 1.68,
                    'backSize' => 18200,
                    'lay' => 1.72,
                    'laySize' => 17100,
                ],
            ],
            'favIdx' => 1,
            'balance' => 'lay',
            'momentum' => 'lay',
            'delta' => '-7%',
            'volatility' => 'medium',
        ],
        [
            'id' => 18,
            'sport' => 'tennis',
            'sportIcon' => '🎾',
            'status' => 'UP',
            'time' => 'in 2h 30m',
            'comp' => 'WTA 500',
            'match' => 'Muguruza vs Kvitova',
            'score' => null,
            'market' => 'match_odds',
            'runners' => [
                [
                    'name' => 'Muguruza',
                    'back' => 1.9,
                    'backSize' => 15200,
                    'lay' => 1.94,
                    'laySize' => 14200,
                ],
                [
                    'name' => 'Kvitova',
                    'back' => 2,
                    'backSize' => 13400,
                    'lay' => 2.06,
                    'laySize' => 12600,
                ],
            ],
            'favIdx' => 0,
            'balance' => 'balanced',
            'momentum' => 'neutral',
            'delta' => '+1%',
            'volatility' => 'low',
        ],
        [
            'id' => 19,
            'sport' => 'tennis',
            'sportIcon' => '🎾',
            'status' => 'UP',
            'time' => 'in 3h',
            'comp' => 'ATP 500',
            'match' => 'Rublev vs Auger-Aliassime',
            'score' => null,
            'market' => 'match_odds',
            'runners' => [
                [
                    'name' => 'Rublev',
                    'back' => 1.75,
                    'backSize' => 17800,
                    'lay' => 1.79,
                    'laySize' => 16700,
                ],
                [
                    'name' => 'Auger-Aliassime',
                    'back' => 2.15,
                    'backSize' => 13500,
                    'lay' => 2.22,
                    'laySize' => 12700,
                ],
            ],
            'favIdx' => 0,
            'balance' => 'back',
            'momentum' => 'neutral',
            'delta' => '+3%',
            'volatility' => 'medium',
        ],
        [
            'id' => 20,
            'sport' => 'tennis',
            'sportIcon' => '🎾',
            'status' => 'UP',
            'time' => 'in 3h 30m',
            'comp' => 'WTA 1000',
            'match' => 'Raducanu vs Gauff',
            'score' => null,
            'market' => 'match_odds',
            'runners' => [
                [
                    'name' => 'Raducanu',
                    'back' => 2.45,
                    'backSize' => 10500,
                    'lay' => 2.55,
                    'laySize' => 9800,
                ],
                [
                    'name' => 'Gauff',
                    'back' => 1.6,
                    'backSize' => 18900,
                    'lay' => 1.63,
                    'laySize' => 17700,
                ],
            ],
            'favIdx' => 1,
            'balance' => 'lay',
            'momentum' => 'lay',
            'delta' => '-4%',
            'volatility' => 'low',
        ],
        [
            'id' => 21,
            'sport' => 'basketball',
            'sportIcon' => '🏀',
            'status' => 'LIVE',
            'time' => 'Q3 05:42',
            'comp' => 'NBA',
            'match' => 'Lakers vs Celtics',
            'score' => '78-82',
            'market' => 'match_odds',
            'runners' => [
                [
                    'name' => 'Lakers',
                    'back' => 2.45,
                    'backSize' => 18400,
                    'lay' => 2.5,
                    'laySize' => 17200,
                ],
                [
                    'name' => 'Celtics',
                    'back' => 1.6,
                    'backSize' => 32100,
                    'lay' => 1.63,
                    'laySize' => 30500,
                ],
            ],
            'favIdx' => 1,
            'balance' => 'lay',
            'momentum' => 'lay',
            'delta' => '-12%',
            'volatility' => 'high',
        ],
        [
            'id' => 22,
            'sport' => 'icehockey',
            'sportIcon' => '🏒',
            'status' => 'LIVE',
            'time' => 'P2 12:30',
            'comp' => 'NHL',
            'match' => 'Maple Leafs vs Canadiens',
            'score' => '2-1',
            'market' => 'match_odds',
            'runners' => [
                [
                    'name' => 'Maple Leafs',
                    'back' => 1.9,
                    'backSize' => 14500,
                    'lay' => 1.94,
                    'laySize' => 13800,
                ],
                [
                    'name' => 'Canadiens',
                    'back' => 3.8,
                    'backSize' => 6200,
                    'lay' => 3.95,
                    'laySize' => 5900,
                ],
            ],
            'favIdx' => 0,
            'balance' => 'back',
            'momentum' => 'back',
            'delta' => '+15%',
            'volatility' => 'medium',
        ],
        [
            'id' => 23,
            'sport' => 'cricket',
            'sportIcon' => '🏏',
            'status' => 'LIVE',
            'time' => 'Over 15.3',
            'comp' => 'IPL',
            'match' => 'Mumbai Indians vs Chennai',
            'score' => '142/4',
            'market' => 'match_odds',
            'runners' => [
                [
                    'name' => 'Mumbai Indians',
                    'back' => 1.75,
                    'backSize' => 24300,
                    'lay' => 1.79,
                    'laySize' => 23100,
                ],
                [
                    'name' => 'Chennai',
                    'back' => 2.2,
                    'backSize' => 16800,
                    'lay' => 2.28,
                    'laySize' => 15900,
                ],
            ],
            'favIdx' => 0,
            'balance' => 'back',
            'momentum' => 'back',
            'delta' => '+25%',
            'volatility' => 'high',
        ],
        [
            'id' => 24,
            'sport' => 'horseracing',
            'sportIcon' => '🏇',
            'status' => 'UP',
            'time' => 'in 4 min',
            'comp' => 'Ascot',
            'match' => 'Ascot 14:30',
            'score' => null,
            'market' => 'win_market',
            'runners' => [
                [
                    'name' => 'Thunder Bolt',
                    'back' => 2.58,
                    'backSize' => 10300,
                    'lay' => 2.47,
                    'laySize' => 10600,
                ],
                [
                    'name' => 'Storm Chaser 1',
                    'back' => 2.73,
                    'backSize' => 8700,
                    'lay' => 2.93,
                    'laySize' => 5200,
                ],
                [
                    'name' => 'Storm Chaser 2',
                    'back' => 3.35,
                    'backSize' => 2300,
                    'lay' => 3.11,
                    'laySize' => 6100,
                ],
                [
                    'name' => 'Silver Arrow',
                    'back' => 4.5,
                    'backSize' => 1800,
                    'lay' => 4.8,
                    'laySize' => 2100,
                ],
                [
                    'name' => 'Midnight Rider',
                    'back' => 6.2,
                    'backSize' => 1200,
                    'lay' => 6.6,
                    'laySize' => 1500,
                ],
            ],
            'favIdx' => 0,
            'balance' => 'balanced',
            'momentum' => 'neutral',
            'delta' => '+2%',
            'volatility' => 'medium',
        ],
        [
            'id' => 25,
            'sport' => 'greyhounds',
            'sportIcon' => '🐕',
            'status' => 'UP',
            'time' => 'in 6 min',
            'comp' => 'Romford',
            'match' => 'Romford 19:45',
            'score' => null,
            'market' => 'win_market',
            'runners' => [
                [
                    'name' => 'Swift Runner',
                    'back' => 2.2,
                    'backSize' => 6400,
                    'lay' => 2.28,
                    'laySize' => 6000,
                ],
                [
                    'name' => 'Fast Paws',
                    'back' => 3.4,
                    'backSize' => 3800,
                    'lay' => 3.6,
                    'laySize' => 3500,
                ],
                [
                    'name' => 'Silver Streak',
                    'back' => 4.1,
                    'backSize' => 2900,
                    'lay' => 4.3,
                    'laySize' => 2600,
                ],
                [
                    'name' => 'Night Shadow',
                    'back' => 5.2,
                    'backSize' => 1900,
                    'lay' => 5.5,
                    'laySize' => 1700,
                ],
            ],
            'favIdx' => 0,
            'balance' => 'balanced',
            'momentum' => 'neutral',
            'delta' => '+1%',
            'volatility' => 'low',
        ],
        [
            'id' => 26,
            'sport' => 'americanfootball',
            'sportIcon' => '🏈',
            'status' => 'UP',
            'time' => 'in 3h',
            'comp' => 'NFL',
            'match' => 'Chiefs vs Eagles',
            'score' => null,
            'market' => 'match_odds',
            'runners' => [
                [
                    'name' => 'Chiefs',
                    'back' => 1.85,
                    'backSize' => 28500,
                    'lay' => 1.88,
                    'laySize' => 27200,
                ],
                [
                    'name' => 'Eagles',
                    'back' => 2.05,
                    'backSize' => 24300,
                    'lay' => 2.1,
                    'laySize' => 23100,
                ],
            ],
            'favIdx' => 0,
            'balance' => 'back',
            'momentum' => 'neutral',
            'delta' => '+4%',
            'volatility' => 'low',
        ],
        [
            'id' => 27,
            'sport' => 'baseball',
            'sportIcon' => '⚾',
            'status' => 'LIVE',
            'time' => 'Inn 7',
            'comp' => 'MLB',
            'match' => 'Yankees vs Red Sox',
            'score' => '4-3',
            'market' => 'match_odds',
            'runners' => [
                [
                    'name' => 'Yankees',
                    'back' => 1.7,
                    'backSize' => 16800,
                    'lay' => 1.74,
                    'laySize' => 15900,
                ],
                [
                    'name' => 'Red Sox',
                    'back' => 2.3,
                    'backSize' => 12400,
                    'lay' => 2.4,
                    'laySize' => 11800,
                ],
            ],
            'favIdx' => 0,
            'balance' => 'back',
            'momentum' => 'back',
            'delta' => '+14%',
            'volatility' => 'medium',
        ],
    ];
    $speedOptions = [
        ['value' => 140, 'label' => '⚡ Pro 140-177ms', 'pro' => true],
        ['value' => 1000, 'label' => '1s'],
        ['value' => 2000, 'label' => '2s'],
        ['value' => 3000, 'label' => '3s'],
        ['value' => 5000, 'label' => '5s'],
        ['value' => 8000, 'label' => '8s'],
        ['value' => 10000, 'label' => '10s'],
    ];
    $marketOptions = [
        ['value' => 'active', 'label' => 'Active Markets'],
        ['value' => 'match_odds', 'label' => 'Match Odds'],
        ['value' => 'ou_0_5', 'label' => 'Over/Under 0.5'],
        ['value' => 'ou_1_5', 'label' => 'Over/Under 1.5'],
        ['value' => 'ou_2_5', 'label' => 'Over/Under 2.5'],
        ['value' => 'ou_3_5', 'label' => 'Over/Under 3.5'],
        ['value' => 'ou_4_5', 'label' => 'Over/Under 4.5'],
        ['value' => 'btts', 'label' => 'BTTS (Yes/No)'],
        ['value' => 'first_half', 'label' => 'First Half'],
        ['value' => 'fh_ou_0_5', 'label' => 'First Half O/U 0.5'],
        ['value' => 'fh_ou_1_5', 'label' => 'First Half O/U 1.5'],
    ];
@endphp

    <div class="page-header my-[18px] flex flex-wrap items-center justify-between gap-3 [display:flex] [align-items:center] [justify-content:space-between] [flex-wrap:wrap] [gap:12px] [margin:18px_0_14px] [margin:28px_0_8px]">
        <h1 class="page-title [font-size:24px] [font-weight:800] [color:#0b2a40] [display:flex] [align-items:center] [gap:10px] [letter-spacing:-0.3px] [transition:color_0.3s] dark:[color:#e8edf2] [&_i]:[color:#1a6b9c] [&_i]:[font-size:22px] max-[768px]:[font-size:18px] max-[768px]:[&_i]:[font-size:18px] max-[480px]:[font-size:16px] [font-size:32px] [font-weight:700] [letter-spacing:-0.5px] [gap:12px] [&_i]:[font-size:28px] max-[768px]:[font-size:24px] max-[480px]:[font-size:20px] max-[480px]:[gap:8px] max-[480px]:[&_i]:[font-size:20px] max-[768px]:[font-size:22px] max-[768px]:[gap:10px] max-[768px]:[&_i]:[font-size:22px] max-[480px]:[font-size:19px] max-[1024px]:[font-size:28px] max-[480px]:[font-size:21px]"><i class="fas fa-bolt"></i> {{ __('Market Activity') }}</h1>
        <div class="header-indicators flex flex-wrap items-center gap-2   [gap:8px] ">
            <button type="button"
                class="collapse-toggle inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-xs font-bold   [gap:6px] [padding:6px_14px] [border-radius:30px] [font-size:12.5px]  [background:#f0f6fc] [border:1px_solid_#d4e0ec] [color:#1f4b66] cursor-pointer [transition:0.2s] select-none [font-family:inherit] dark:[background:#1f3444] dark:[border-color:#3a5568] dark:[color:#b0c8dd] [&:hover]:[border-color:#1a6b9c] [&:hover]:[color:#1a6b9c] dark:[&:hover]:[border-color:#4a8ab5] dark:[&:hover]:[color:#6aafdf] [&_i]:[transition:transform_0.3s]"
                id="collapseToggle" title="Collapse/expand filters">
                <i class="fas fa-chevron-up"></i>
                <span>{{ __('Filters') }}</span>
            </button>
            <span class="live-indicator inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold   [gap:6px] [padding:4px_12px] [border-radius:30px] [background:#e8f7ee] [border:1px_solid_#c6e8d3] [color:#1f9a6e] [font-size:12px]  dark:[background:#1a3a2a] dark:[border-color:#2a5a3e] dark:[color:#5ab88a]">
                <span class="live-dot [width:8px] [height:8px] [border-radius:50%] [background:#1f9a6e] [animation:pulse_1.6s_infinite] dark:[background:#5ab88a]"></span>
                {{ __('Live') }}
            </span>
            <span class="feed-indicator inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold   [gap:6px] [padding:4px_12px] [border-radius:30px] [background:#f0f6fc] [border:1px_solid_#d4e0ec] [font-size:11.5px]  [color:#5a7d99] [font-variant-numeric:tabular-nums] dark:[background:#1f3444] dark:[border-color:#3a5568] dark:[color:#8aaccc] [&_.feed-dot]:[width:6px] [&_.feed-dot]:[height:6px] [&_.feed-dot]:[border-radius:50%] [&_.feed-dot]:[background:#1f9a6e] [&_.feed-dot]:[animation:pulse_1s_infinite] dark:[&_.feed-dot]:[background:#5ab88a]">
                <span class="feed-dot"></span>
                {{ __('WS Feed') }} · <span class="feed-latency [color:#1a6b9c] dark:[color:#6aafdf]" id="latencyDisplay">154</span> {{ __('ms') }}
            </span>
        </div>
    </div>

    <!-- COLLAPSIBLE CONTROLS WRAPPER -->
    <div class="controls-wrapper overflow-hidden  [transition:max-height_0.35s_ease,_opacity_0.25s_ease] [max-height:800px] opacity-100" id="controlsWrapper">

        <!-- SPEED SELECTOR -->
        <div class="speed-bar flex flex-wrap items-center gap-2 border-b py-3    [gap:8px] [padding:10px_0_12px] [border-bottom:1px_solid_#e6edf6] [margin-bottom:12px] [transition:border-color_0.3s] dark:[border-bottom-color:#2a3f50] max-[768px]:[gap:6px]" id="speedBar">
            <span class="speed-label inline-flex items-center [gap:6px] [font-size:12px] font-bold [color:#5a7d99] uppercase [letter-spacing:0.4px] [padding-right:6px] dark:[color:#8aaccc] [&_i]:[color:#1a6b9c] [&_i]:[font-size:13px] dark:[&_i]:[color:#6aafdf]"><i class="fas fa-tachometer-alt"></i> {{ __('Speed:') }}</span>
            @foreach ($speedOptions as $speed)
                <button type="button" class="speed-pill {{ ($speed['pro'] ?? false) ? 'pro' : '' }} {{ $speed['value'] === 3000 ? 'active' : '' }} inline-flex items-center [gap:5px] [padding:6px_13px] [border-radius:20px] [font-size:12px] font-bold [background:#f0f6fc] [border:1px_solid_#d4e0ec] [color:#5a7d99] cursor-pointer [transition:0.2s] select-none [font-variant-numeric:tabular-nums] [font-family:inherit] dark:[background:#1f3444] dark:[border-color:#3a5568] dark:[color:#8aaccc] [&:hover]:[border-color:#1a6b9c] [&:hover]:[color:#1a6b9c] [&:hover]:[transform:translateY(-1px)] dark:[&:hover]:[border-color:#4a8ab5] dark:[&:hover]:[color:#6aafdf] [&.active]:[background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] [&.active]:[border-color:#1a6b9c] [&.active]:[color:white] [&.active]:[box-shadow:0_4px_12px_rgba(26,_107,_156,_0.25)] dark:[&.active]:[background:linear-gradient(135deg,_#1a6b9c,_#4a8ab5)] dark:[&.active]:[border-color:#4a8ab5] [&.pro]:[border-color:#e6b422] [&.pro]:[color:#b8860b] [&.pro]:[background:linear-gradient(135deg,_#fff8e6,_#fef3d6)] dark:[&.pro]:[background:linear-gradient(135deg,_#3a2e1a,_#2e2414)] dark:[&.pro]:[border-color:#e6b422] dark:[&.pro]:[color:#e6b422] [&.pro.active]:[background:linear-gradient(135deg,_#b8860b,_#e6b422)] [&.pro.active]:[border-color:#e6b422] [&.pro.active]:[color:white] [&.pro.active]:[box-shadow:0_4px_12px_rgba(230,_180,_34,_0.3)] max-[768px]:[padding:5px_10px] max-[768px]:[font-size:11px] max-[480px]:[padding:4px_8px] max-[480px]:[font-size:10px]" data-speed="{{ $speed['value'] }}">{{ $speed['label'] }}</button>
            @endforeach
        </div>

        @include('users.layout.sports')

        @include('users.sports.football.football_filter')

        <!-- MARKET CONTAINER (football only) -->
        <div class="market-container flex flex-wrap items-center gap-2.5 border-b py-3   [gap:10px] [padding:10px_0_14px] [border-bottom:1px_solid_#e6edf6] [margin-bottom:14px] [transition:border-color_0.3s] dark:[border-bottom-color:#2a3f50] [&[hidden]]:[display:none] max-[768px]:[gap:6px]" id="marketContainer" hidden>
            <span class="market-container-label inline-flex items-center [gap:6px] [font-size:12px] font-bold [color:#5a7d99] uppercase [letter-spacing:0.4px] dark:[color:#8aaccc] [&_i]:[color:#1a6b9c] [&_i]:[font-size:13px] dark:[&_i]:[color:#6aafdf]"><i class="fas fa-filter"></i> {{ __('Market:') }}</span>
            <select class="market-select [appearance:none] [-webkit-appearance:none] [background-size:14px] [border:1px_solid_#d4e0ec] [border-radius:30px] [padding:8px_38px_8px_16px] [font-size:13px] [font-weight:700] [color:#1f4b66] [cursor:pointer] [transition:0.2s] [min-width:200px] [&:hover]:[border-color:#1a6b9c] [&:focus]:[outline:none] [&:focus]:[border-color:#1a6b9c] [&:focus]:[box-shadow:0_0_0_3px_rgba(26,_107,_156,_0.15)] dark:[background-color:#1f3444] dark:[border-color:#3a5568] dark:[color:#b0c8dd] max-[768px]:[font-size:12px] max-[768px]:[min-width:170px] max-[768px]:[padding:7px_34px_7px_14px]" id="marketSelect">
                @foreach ($marketOptions as $marketOption)
                    <option value="{{ $marketOption['value'] }}">{{ $marketOption['label'] }}</option>
                @endforeach
            </select>
        </div>

    </div>

    <div class="terminal grid gap-4  [grid-template-columns:1fr] [gap:16px] [transition:grid-template-columns_0.35s_ease] [&.with-panel]:[grid-template-columns:1fr_460px] [&.panel-expanded]:[grid-template-columns:1fr_0px] [&.panel-expanded_.detail-panel]:[position:fixed] [&.panel-expanded_.detail-panel]:[top:0] [&.panel-expanded_.detail-panel]:[left:0] [&.panel-expanded_.detail-panel]:[right:0] [&.panel-expanded_.detail-panel]:[bottom:0] [&.panel-expanded_.detail-panel]:[width:100vw] [&.panel-expanded_.detail-panel]:[height:100vh] [&.panel-expanded_.detail-panel]:[z-index:99999] [&.panel-expanded_.detail-panel]:[border-radius:0] [&.panel-expanded_.detail-panel]:[overflow-y:auto] [&.panel-expanded_.detail-panel]:[animation:expandIn_0.3s_ease] max-[1200px]:[&.with-panel]:[grid-template-columns:1fr] [&.panel-expanded_.detail-body]:[max-width:1200px] [&.panel-expanded_.detail-body]:[margin:0_auto] [&.panel-expanded_.detail-body]:[padding:24px_32px] max-[1024px]:[&.with-panel]:[grid-template-columns:1fr] max-[480px]:[&.panel-expanded_.detail-body]:[padding:16px]" id="terminal">
        <div class="events-table-wrap overflow-x-auto rounded-[14px]  [border-radius:14px] [border:1px_solid_#e6eff8] [transition:border-color_0.3s] dark:[border-color:#2a3f50]" id="eventsWrap">
            <table class="events-table w-full min-w-[1150px] border-separate text-[13px]  [border-collapse:separate] [border-spacing:0] [font-size:13px] [min-width:1150px] bg-white [transition:background_0.3s] dark:[background:#1f3444] [&_thead]:[background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] [&_thead]:[color:white] [&_thead]:[position:sticky] [&_thead]:[top:0] [&_thead]:[z-index:5] [&_thead_th]:[padding:10px_12px] [&_thead_th]:[text-align:left] [&_thead_th]:[font-weight:700] [&_thead_th]:[font-size:11px] [&_thead_th]:[letter-spacing:0.5px] [&_thead_th]:[text-transform:uppercase] [&_thead_th]:[white-space:nowrap] [&_tbody_td]:[padding:10px_12px] [&_tbody_td]:[border-bottom:1px_solid_#eef2f8] [&_tbody_td]:[color:#2a4d66] [&_tbody_td]:[white-space:nowrap] [&_tbody_td]:[transition:color_0.3s,_border-color_0.3s] dark:[&_tbody_td]:[border-bottom-color:#2a3f50] dark:[&_tbody_td]:[color:#b0c8dd] [&_tbody_tr]:[cursor:pointer] [&_tbody_tr]:[transition:background_0.2s] [&_tbody_tr:hover]:[background:#f8fbfe] dark:[&_tbody_tr:hover]:[background:#1a2e44] [&_tbody_tr.selected]:[background:#e8f2fc] dark:[&_tbody_tr.selected]:[background:#1a2e44] [&_td.col-odds]:[text-align:center] [&_td.col-odds]:[padding:6px_4px]! max-[768px]:[font-size:12px] max-[768px]:[min-width:1000px] max-[768px]:[&_thead_th]:[padding:8px_10px] max-[768px]:[&_tbody_td]:[padding:8px_10px]">
                <thead>
                    <tr>
                        <th>{{ __('TIME')}}</th>
                        <th>{{ __('COMP')}}</th>
                        <th>{{ __('MATCH')}}</th>
                        <th>{{ __('MARKET')}}</th>
                        <th>{{ __('FAV')}}</th>
                        <th>{{ __('BACK')}}</th>
                        <th>{{ __('SIZE')}}</th>
                        <th>{{ __('LAY')}}</th>
                        <th>{{ __('SIZE')}}</th>
                        <th>{{ __('BAL')}}</th>
                        <th>{{ __('MOM')}}</th>
                        <th>{{ __('Δ')}}</th>
                        <th>{{ __('VOL')}}</th>
                        <th>{{ __('ACTION')}}</th>
                    </tr>
                </thead>
                <tbody id="eventsBody">
                    @foreach ($events as $event)
                        @php
                            $favorite = $event['runners'][$event['favIdx']];
                            $marketTag = $marketTags[$event['market']] ?? ['label' => $event['market'], 'cls' => 'mo'];
                            $balanceClass = ['back' => 'ind-back-heavy', 'lay' => 'ind-lay-heavy', 'balanced' => 'ind-balanced'][$event['balance']] ?? 'ind-balanced';
                            $balanceLabel = ['back' => 'Back ↑', 'lay' => 'Lay ↓', 'balanced' => 'Balanced →'][$event['balance']] ?? 'Balanced →';
                            $momentumClass = ['strong_back' => 'ind-mom-strong-back', 'back' => 'ind-mom-back', 'strong_lay' => 'ind-mom-strong-lay', 'lay' => 'ind-mom-lay', 'neutral' => 'ind-mom-neutral'][$event['momentum']] ?? 'ind-mom-neutral';
                            $momentumLabel = ['strong_back' => '↑↑', 'back' => '↑', 'strong_lay' => '↓↓', 'lay' => '↓', 'neutral' => '→'][$event['momentum']] ?? '→';
                            $volatilityClass = ['low' => 'ind-vol-low', 'medium' => 'ind-vol-med', 'high' => 'ind-vol-high'][$event['volatility']] ?? 'ind-vol-med';
                            $volatilityLabel = ['low' => 'Low', 'medium' => 'Med', 'high' => 'High'][$event['volatility']] ?? 'Med';
                        @endphp
                        <tr data-id="{{ $event['id'] }}">
                            <td class="col-time font-bold [color:#0b2a40] dark:[color:#e8edf2] [&_.status-live]:[color:#c74e4e] [&_.status-live]:[font-weight:800] [&_.status-live]:[margin-right:4px] dark:[&_.status-live]:[color:#e08080] [&_.status-up]:[color:#1a6b9c] [&_.status-up]:[font-weight:700] [&_.status-up]:[margin-right:4px] dark:[&_.status-up]:[color:#6aafdf]">
                                @if ($event['status'] === 'LIVE')<span class="status-live">● LIVE</span>@else<span class="status-up">▲ UP</span>@endif
                                {{ $event['time'] }}
                            </td>
                            <td class="col-comp [font-size:11.5px] [color:#8aaccc] dark:[color:#5a7d99]">{{ $event['sportIcon'] }} {{ $event['comp'] }}</td>
                            <td class="col-match font-semibold [color:#0b2a40] dark:[color:#e8edf2] [&_.fav-star]:[color:#e6b422] [&_.fav-star]:[margin-left:4px] [&_.fav-star]:[font-size:12px]">{{ $event['match'] }}@if (in_array($event['sport'], ['horseracing', 'greyhounds'], true)) <span class="fav-star [color:#b8ccdf] [font-size:15px] cursor-pointer [background:none] border-0 [width:24px] [transition:0.2s] text-center dark:[color:#4a6a88] dark:[&.active-fav]:[color:#f5b342] [&.active-fav]:[color:#f5b342]">⭐</span>@endif</td>
                            <td class="col-market font-semibold [font-size:12px]"><span class="market-tag {{ $marketTag['cls'] }} inline-flex items-center [gap:4px] [padding:3px_10px] [border-radius:20px] [font-size:11px] font-bold whitespace-nowrap [&.mo]:[background:linear-gradient(135deg,_#e8f2fc,_#d4e8f8)] [&.mo]:[color:#1a6b9c] [&.mo]:[border:1px_solid_#b8d4ec] dark:[&.mo]:[background:linear-gradient(135deg,_#1a2e44,_#1f3444)] dark:[&.mo]:[color:#6aafdf] dark:[&.mo]:[border-color:#2a4a5e] [&.ou]:[background:linear-gradient(135deg,_#fff8e6,_#fef3d6)] [&.ou]:[color:#b8860b] [&.ou]:[border:1px_solid_#f0dfa8] dark:[&.ou]:[background:linear-gradient(135deg,_#3a2e1a,_#2e2414)] dark:[&.ou]:[color:#e6b422] dark:[&.ou]:[border-color:#5a4a1a] [&.btts]:[background:linear-gradient(135deg,_#e8f7ee,_#d6f0e0)] [&.btts]:[color:#1f9a6e] [&.btts]:[border:1px_solid_#c6e8d3] dark:[&.btts]:[background:linear-gradient(135deg,_#1a3a2a,_#14301f)] dark:[&.btts]:[color:#5ab88a] dark:[&.btts]:[border-color:#2a5a3e] [&.cs]:[background:linear-gradient(135deg,_#fce8ee,_#f8d9e2)] [&.cs]:[color:#d44a6a] [&.cs]:[border:1px_solid_#f0c9d4] dark:[&.cs]:[background:linear-gradient(135deg,_#3a1f2a,_#2e1a22)] dark:[&.cs]:[color:#e0809a] dark:[&.cs]:[border-color:#5a2a3e] [&.fh]:[background:linear-gradient(135deg,_#f0e8fc,_#e8dcf8)] [&.fh]:[color:#7a4a9c] [&.fh]:[border:1px_solid_#d8c4ec] dark:[&.fh]:[background:linear-gradient(135deg,_#2e1a44,_#251535)] dark:[&.fh]:[color:#b888df] dark:[&.fh]:[border-color:#4a2a5e] [&.win]:[background:linear-gradient(135deg,_#fde8e8,_#fcd9d9)] [&.win]:[color:#c74e4e] [&.win]:[border:1px_solid_#f0c9c9] dark:[&.win]:[background:linear-gradient(135deg,_#3a1f1f,_#2e1a1a)] dark:[&.win]:[color:#e08080] dark:[&.win]:[border-color:#5a2a2a]">{{ $marketTag['label'] }}</span></td>
                            <td class="col-fav font-bold [font-size:12.5px] [color:#0b2a40] dark:[color:#e8edf2] [&_.fav-icon]:[color:#e6b422] [&_.fav-icon]:[font-size:11px] [&_.fav-icon]:[margin-right:4px]"><span class="fav-icon">{{ $event['market'] === 'correct_score' ? '⚽' : '⭐' }}</span>{{ $event['market'] === 'correct_score' ? ($event['marketDisplayScore'] ?? $event['score'] ?? '—') : $favorite['name'] }}</td>
                            <td class="col-odds"><span class="odds-cell odds-cell-back [background:#72bbef] [color:#0b2a40] [border:1px_solid_#4a9fd8] dark:[background:#72bbef] dark:[color:#0b2a40] dark:[border-color:#4a9fd8] [text-align:center] [font-weight:800] [font-size:13.5px] [border-radius:4px] [padding:5px_8px]! [min-width:55px] [display:inline-block] [transition:background_0.4s_ease,_color_0.4s_ease] [margin:0_auto] [font-variant-numeric:tabular-nums] max-[768px]:[font-size:12.5px] max-[768px]:[padding:4px_6px]! max-[768px]:[min-width:50px]">{{ number_format($favorite['back'], 2) }}</span></td>
                            <td class="col-odds"><span class="size-cell size-cell-back [background:rgba(114,_187,_239,_0.18)] [color:#1a6b9c] dark:[background:rgba(114,_187,_239,_0.15)] dark:[color:#72bbef] [text-align:center] [font-weight:700] [font-size:12px] [border-radius:4px] [padding:5px_8px]! [min-width:60px] [display:inline-block] [margin:0_auto] [font-variant-numeric:tabular-nums] [transition:background_0.4s_ease,_color_0.4s_ease] max-[768px]:[font-size:11px] max-[768px]:[padding:4px_6px]! max-[768px]:[min-width:50px]">{{ $favorite['backSize'] >= 1000 ? number_format($favorite['backSize'] / 1000, 1).'K' : $favorite['backSize'] }}</span></td>
                            <td class="col-odds"><span class="odds-cell odds-cell-lay [background:#faa9ba] [color:#4a1520] [border:1px_solid_#e8889a] dark:[background:#faa9ba] dark:[color:#4a1520] dark:[border-color:#e8889a] [text-align:center] [font-weight:800] [font-size:13.5px] [border-radius:4px] [padding:5px_8px]! [min-width:55px] [display:inline-block] [transition:background_0.4s_ease,_color_0.4s_ease] [margin:0_auto] [font-variant-numeric:tabular-nums] max-[768px]:[font-size:12.5px] max-[768px]:[padding:4px_6px]! max-[768px]:[min-width:50px]">{{ number_format($favorite['lay'], 2) }}</span></td>
                            <td class="col-odds"><span class="size-cell size-cell-lay [background:rgba(250,_169,_186,_0.18)] [color:#a8425a] dark:[background:rgba(250,_169,_186,_0.15)] dark:[color:#faa9ba] [text-align:center] [font-weight:700] [font-size:12px] [border-radius:4px] [padding:5px_8px]! [min-width:60px] [display:inline-block] [margin:0_auto] [font-variant-numeric:tabular-nums] [transition:background_0.4s_ease,_color_0.4s_ease] max-[768px]:[font-size:11px] max-[768px]:[padding:4px_6px]! max-[768px]:[min-width:50px]">{{ $favorite['laySize'] >= 1000 ? number_format($favorite['laySize'] / 1000, 1).'K' : $favorite['laySize'] }}</span></td>
                            <td><span class="ind [&.ind-back-heavy]:[background:#e8f2fc] [&.ind-back-heavy]:[color:#1a6b9c] dark:[&.ind-back-heavy]:[background:#1a2e44] dark:[&.ind-back-heavy]:[color:#6aafdf] [&.ind-lay-heavy]:[background:#fce8ee] [&.ind-lay-heavy]:[color:#d44a6a] dark:[&.ind-lay-heavy]:[background:#3a1f2a] dark:[&.ind-lay-heavy]:[color:#e0809a] [&.ind-balanced]:[background:#f0f0f0] [&.ind-balanced]:[color:#6a8aaa] dark:[&.ind-balanced]:[background:#2a3f50] dark:[&.ind-balanced]:[color:#8aaccc] [&.ind-mom-strong-back]:[background:#e8f2fc] [&.ind-mom-strong-back]:[color:#1a6b9c] [&.ind-mom-strong-back]:[font-weight:800] [&.ind-mom-back]:[background:#e8f2fc] [&.ind-mom-back]:[color:#1a6b9c] [&.ind-mom-neutral]:[background:#f0f0f0] [&.ind-mom-neutral]:[color:#6a8aaa] [&.ind-mom-lay]:[background:#fce8ee] [&.ind-mom-lay]:[color:#d44a6a] [&.ind-mom-strong-lay]:[background:#fce8ee] [&.ind-mom-strong-lay]:[color:#d44a6a] [&.ind-mom-strong-lay]:[font-weight:800] dark:[&.ind-mom-strong-back]:[background:#1a2e44] dark:[&.ind-mom-strong-back]:[color:#6aafdf] dark:[&.ind-mom-back]:[background:#1a2e44] dark:[&.ind-mom-back]:[color:#6aafdf] dark:[&.ind-mom-neutral]:[background:#2a3f50] dark:[&.ind-mom-neutral]:[color:#8aaccc] dark:[&.ind-mom-lay]:[background:#3a1f2a] dark:[&.ind-mom-lay]:[color:#e0809a] dark:[&.ind-mom-strong-lay]:[background:#3a1f2a] dark:[&.ind-mom-strong-lay]:[color:#e0809a] [&.ind-delta-pos]:[background:#e8f7ee] [&.ind-delta-pos]:[color:#1f9a6e] dark:[&.ind-delta-pos]:[background:#1a3a2a] dark:[&.ind-delta-pos]:[color:#5ab88a] [&.ind-delta-neg]:[background:#fde8e8] [&.ind-delta-neg]:[color:#c74e4e] dark:[&.ind-delta-neg]:[background:#3a1f1f] dark:[&.ind-delta-neg]:[color:#e08080] [&.ind-delta-zero]:[background:#f0f0f0] [&.ind-delta-zero]:[color:#6a8aaa] dark:[&.ind-delta-zero]:[background:#2a3f50] dark:[&.ind-delta-zero]:[color:#8aaccc] [&.ind-vol-low]:[background:#e8f7ee] [&.ind-vol-low]:[color:#1f9a6e] dark:[&.ind-vol-low]:[background:#1a3a2a] dark:[&.ind-vol-low]:[color:#5ab88a] [&.ind-vol-med]:[background:#fff8e6] [&.ind-vol-med]:[color:#b8860b] dark:[&.ind-vol-med]:[background:#3a2e1a] dark:[&.ind-vol-med]:[color:#e6b422] [&.ind-vol-high]:[background:#fde8e8] [&.ind-vol-high]:[color:#c74e4e] dark:[&.ind-vol-high]:[background:#3a1f1f] dark:[&.ind-vol-high]:[color:#e08080] {{ $balanceClass }} inline-flex items-center [gap:4px] [padding:3px_8px] [border-radius:12px] [font-size:11px] font-bold whitespace-nowrap [transition:0.3s]">{{ $balanceLabel }}</span></td>
                            <td><span class="ind [&.ind-back-heavy]:[background:#e8f2fc] [&.ind-back-heavy]:[color:#1a6b9c] dark:[&.ind-back-heavy]:[background:#1a2e44] dark:[&.ind-back-heavy]:[color:#6aafdf] [&.ind-lay-heavy]:[background:#fce8ee] [&.ind-lay-heavy]:[color:#d44a6a] dark:[&.ind-lay-heavy]:[background:#3a1f2a] dark:[&.ind-lay-heavy]:[color:#e0809a] [&.ind-balanced]:[background:#f0f0f0] [&.ind-balanced]:[color:#6a8aaa] dark:[&.ind-balanced]:[background:#2a3f50] dark:[&.ind-balanced]:[color:#8aaccc] [&.ind-mom-strong-back]:[background:#e8f2fc] [&.ind-mom-strong-back]:[color:#1a6b9c] [&.ind-mom-strong-back]:[font-weight:800] [&.ind-mom-back]:[background:#e8f2fc] [&.ind-mom-back]:[color:#1a6b9c] [&.ind-mom-neutral]:[background:#f0f0f0] [&.ind-mom-neutral]:[color:#6a8aaa] [&.ind-mom-lay]:[background:#fce8ee] [&.ind-mom-lay]:[color:#d44a6a] [&.ind-mom-strong-lay]:[background:#fce8ee] [&.ind-mom-strong-lay]:[color:#d44a6a] [&.ind-mom-strong-lay]:[font-weight:800] dark:[&.ind-mom-strong-back]:[background:#1a2e44] dark:[&.ind-mom-strong-back]:[color:#6aafdf] dark:[&.ind-mom-back]:[background:#1a2e44] dark:[&.ind-mom-back]:[color:#6aafdf] dark:[&.ind-mom-neutral]:[background:#2a3f50] dark:[&.ind-mom-neutral]:[color:#8aaccc] dark:[&.ind-mom-lay]:[background:#3a1f2a] dark:[&.ind-mom-lay]:[color:#e0809a] dark:[&.ind-mom-strong-lay]:[background:#3a1f2a] dark:[&.ind-mom-strong-lay]:[color:#e0809a] [&.ind-delta-pos]:[background:#e8f7ee] [&.ind-delta-pos]:[color:#1f9a6e] dark:[&.ind-delta-pos]:[background:#1a3a2a] dark:[&.ind-delta-pos]:[color:#5ab88a] [&.ind-delta-neg]:[background:#fde8e8] [&.ind-delta-neg]:[color:#c74e4e] dark:[&.ind-delta-neg]:[background:#3a1f1f] dark:[&.ind-delta-neg]:[color:#e08080] [&.ind-delta-zero]:[background:#f0f0f0] [&.ind-delta-zero]:[color:#6a8aaa] dark:[&.ind-delta-zero]:[background:#2a3f50] dark:[&.ind-delta-zero]:[color:#8aaccc] [&.ind-vol-low]:[background:#e8f7ee] [&.ind-vol-low]:[color:#1f9a6e] dark:[&.ind-vol-low]:[background:#1a3a2a] dark:[&.ind-vol-low]:[color:#5ab88a] [&.ind-vol-med]:[background:#fff8e6] [&.ind-vol-med]:[color:#b8860b] dark:[&.ind-vol-med]:[background:#3a2e1a] dark:[&.ind-vol-med]:[color:#e6b422] [&.ind-vol-high]:[background:#fde8e8] [&.ind-vol-high]:[color:#c74e4e] dark:[&.ind-vol-high]:[background:#3a1f1f] dark:[&.ind-vol-high]:[color:#e08080] {{ $momentumClass }} inline-flex items-center [gap:4px] [padding:3px_8px] [border-radius:12px] [font-size:11px] font-bold whitespace-nowrap [transition:0.3s]">{{ $momentumLabel }}</span></td>
                            <td><span class="ind [&.ind-back-heavy]:[background:#e8f2fc] [&.ind-back-heavy]:[color:#1a6b9c] dark:[&.ind-back-heavy]:[background:#1a2e44] dark:[&.ind-back-heavy]:[color:#6aafdf] [&.ind-lay-heavy]:[background:#fce8ee] [&.ind-lay-heavy]:[color:#d44a6a] dark:[&.ind-lay-heavy]:[background:#3a1f2a] dark:[&.ind-lay-heavy]:[color:#e0809a] [&.ind-balanced]:[background:#f0f0f0] [&.ind-balanced]:[color:#6a8aaa] dark:[&.ind-balanced]:[background:#2a3f50] dark:[&.ind-balanced]:[color:#8aaccc] [&.ind-mom-strong-back]:[background:#e8f2fc] [&.ind-mom-strong-back]:[color:#1a6b9c] [&.ind-mom-strong-back]:[font-weight:800] [&.ind-mom-back]:[background:#e8f2fc] [&.ind-mom-back]:[color:#1a6b9c] [&.ind-mom-neutral]:[background:#f0f0f0] [&.ind-mom-neutral]:[color:#6a8aaa] [&.ind-mom-lay]:[background:#fce8ee] [&.ind-mom-lay]:[color:#d44a6a] [&.ind-mom-strong-lay]:[background:#fce8ee] [&.ind-mom-strong-lay]:[color:#d44a6a] [&.ind-mom-strong-lay]:[font-weight:800] dark:[&.ind-mom-strong-back]:[background:#1a2e44] dark:[&.ind-mom-strong-back]:[color:#6aafdf] dark:[&.ind-mom-back]:[background:#1a2e44] dark:[&.ind-mom-back]:[color:#6aafdf] dark:[&.ind-mom-neutral]:[background:#2a3f50] dark:[&.ind-mom-neutral]:[color:#8aaccc] dark:[&.ind-mom-lay]:[background:#3a1f2a] dark:[&.ind-mom-lay]:[color:#e0809a] dark:[&.ind-mom-strong-lay]:[background:#3a1f2a] dark:[&.ind-mom-strong-lay]:[color:#e0809a] [&.ind-delta-pos]:[background:#e8f7ee] [&.ind-delta-pos]:[color:#1f9a6e] dark:[&.ind-delta-pos]:[background:#1a3a2a] dark:[&.ind-delta-pos]:[color:#5ab88a] [&.ind-delta-neg]:[background:#fde8e8] [&.ind-delta-neg]:[color:#c74e4e] dark:[&.ind-delta-neg]:[background:#3a1f1f] dark:[&.ind-delta-neg]:[color:#e08080] [&.ind-delta-zero]:[background:#f0f0f0] [&.ind-delta-zero]:[color:#6a8aaa] dark:[&.ind-delta-zero]:[background:#2a3f50] dark:[&.ind-delta-zero]:[color:#8aaccc] [&.ind-vol-low]:[background:#e8f7ee] [&.ind-vol-low]:[color:#1f9a6e] dark:[&.ind-vol-low]:[background:#1a3a2a] dark:[&.ind-vol-low]:[color:#5ab88a] [&.ind-vol-med]:[background:#fff8e6] [&.ind-vol-med]:[color:#b8860b] dark:[&.ind-vol-med]:[background:#3a2e1a] dark:[&.ind-vol-med]:[color:#e6b422] [&.ind-vol-high]:[background:#fde8e8] [&.ind-vol-high]:[color:#c74e4e] dark:[&.ind-vol-high]:[background:#3a1f1f] dark:[&.ind-vol-high]:[color:#e08080] {{ str_starts_with($event['delta'], '+') ? 'ind-delta-pos' : (str_starts_with($event['delta'], '-') ? 'ind-delta-neg' : 'ind-delta-zero') }} inline-flex items-center [gap:4px] [padding:3px_8px] [border-radius:12px] [font-size:11px] font-bold whitespace-nowrap [transition:0.3s]">{{ $event['delta'] }}</span></td>
                            <td><span class="ind [&.ind-back-heavy]:[background:#e8f2fc] [&.ind-back-heavy]:[color:#1a6b9c] dark:[&.ind-back-heavy]:[background:#1a2e44] dark:[&.ind-back-heavy]:[color:#6aafdf] [&.ind-lay-heavy]:[background:#fce8ee] [&.ind-lay-heavy]:[color:#d44a6a] dark:[&.ind-lay-heavy]:[background:#3a1f2a] dark:[&.ind-lay-heavy]:[color:#e0809a] [&.ind-balanced]:[background:#f0f0f0] [&.ind-balanced]:[color:#6a8aaa] dark:[&.ind-balanced]:[background:#2a3f50] dark:[&.ind-balanced]:[color:#8aaccc] [&.ind-mom-strong-back]:[background:#e8f2fc] [&.ind-mom-strong-back]:[color:#1a6b9c] [&.ind-mom-strong-back]:[font-weight:800] [&.ind-mom-back]:[background:#e8f2fc] [&.ind-mom-back]:[color:#1a6b9c] [&.ind-mom-neutral]:[background:#f0f0f0] [&.ind-mom-neutral]:[color:#6a8aaa] [&.ind-mom-lay]:[background:#fce8ee] [&.ind-mom-lay]:[color:#d44a6a] [&.ind-mom-strong-lay]:[background:#fce8ee] [&.ind-mom-strong-lay]:[color:#d44a6a] [&.ind-mom-strong-lay]:[font-weight:800] dark:[&.ind-mom-strong-back]:[background:#1a2e44] dark:[&.ind-mom-strong-back]:[color:#6aafdf] dark:[&.ind-mom-back]:[background:#1a2e44] dark:[&.ind-mom-back]:[color:#6aafdf] dark:[&.ind-mom-neutral]:[background:#2a3f50] dark:[&.ind-mom-neutral]:[color:#8aaccc] dark:[&.ind-mom-lay]:[background:#3a1f2a] dark:[&.ind-mom-lay]:[color:#e0809a] dark:[&.ind-mom-strong-lay]:[background:#3a1f2a] dark:[&.ind-mom-strong-lay]:[color:#e0809a] [&.ind-delta-pos]:[background:#e8f7ee] [&.ind-delta-pos]:[color:#1f9a6e] dark:[&.ind-delta-pos]:[background:#1a3a2a] dark:[&.ind-delta-pos]:[color:#5ab88a] [&.ind-delta-neg]:[background:#fde8e8] [&.ind-delta-neg]:[color:#c74e4e] dark:[&.ind-delta-neg]:[background:#3a1f1f] dark:[&.ind-delta-neg]:[color:#e08080] [&.ind-delta-zero]:[background:#f0f0f0] [&.ind-delta-zero]:[color:#6a8aaa] dark:[&.ind-delta-zero]:[background:#2a3f50] dark:[&.ind-delta-zero]:[color:#8aaccc] [&.ind-vol-low]:[background:#e8f7ee] [&.ind-vol-low]:[color:#1f9a6e] dark:[&.ind-vol-low]:[background:#1a3a2a] dark:[&.ind-vol-low]:[color:#5ab88a] [&.ind-vol-med]:[background:#fff8e6] [&.ind-vol-med]:[color:#b8860b] dark:[&.ind-vol-med]:[background:#3a2e1a] dark:[&.ind-vol-med]:[color:#e6b422] [&.ind-vol-high]:[background:#fde8e8] [&.ind-vol-high]:[color:#c74e4e] dark:[&.ind-vol-high]:[background:#3a1f1f] dark:[&.ind-vol-high]:[color:#e08080] {{ $volatilityClass }} inline-flex items-center [gap:4px] [padding:3px_8px] [border-radius:12px] [font-size:11px] font-bold whitespace-nowrap [transition:0.3s]">{{ $volatilityLabel }}</span></td>
                            <td><button type="button" class="open-btn inline-flex items-center [gap:4px] [padding:5px_12px] [border-radius:20px] [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white [font-size:11.5px] font-bold border-0 cursor-pointer [transition:transform_0.2s,_box-shadow_0.2s] [font-family:inherit] whitespace-nowrap dark:[background:linear-gradient(135deg,_#1a6b9c,_#4a8ab5)] [&:hover]:[transform:translateY(-1px)] [&:hover]:[box-shadow:0_6px_16px_rgba(26,_107,_156,_0.3)]" data-id="{{ $event['id'] }}">{{ __('Open') }} →</button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="detail-panel overflow-y-auto rounded-2xl border hidden [background:#f8fbfe] [border:1px_solid_#e6eff8] [border-radius:16px] p-0 overflow-hidden [transition:0.3s] [height:fit-content] [position:sticky] [top:20px] [max-height:calc(100vh_-_40px)] [overflow-y:auto] dark:[background:#1f3444] dark:[border-color:#2a3f50] [&.open]:[display:block] [&.open]:[animation:slideIn_0.3s_ease] max-[1024px]:[position:static] max-[480px]:[padding:0]" id="detailPanel">
            <div class="empty-panel flex flex-col items-center justify-center [padding:60px_20px] text-center [color:#8aaccc] [&_i]:[font-size:40px] [&_i]:[margin-bottom:12px] [&_i]:[opacity:0.5] [&_p]:[font-size:14px]" id="emptyPanel">
                <i class="fas fa-mouse-pointer"></i>
                <p>{{ __('Select an event to view detailed data') }}</p>
            </div>
            <div id="detailContent" style="display:none;"></div>
        </div>
    </div>

    <button type="button" class="scroll-top fixed [right:20px] [bottom:20px] [width:45px] [height:45px] border-0 [border-radius:50%] [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white [font-size:20px] cursor-pointer [z-index:9999] hidden items-center justify-center [box-shadow:0_4px_16px_rgba(26,_107,_156,_0.3)] [transition:0.2s] [&.visible]:[display:flex] [&:hover]:[transform:translateY(-3px)]" id="scrollTop" aria-label="Scroll to top">↑</button>

    @push('js')
        <script>
            (function() {
                // ===== COLLAPSE TOGGLE =====
                const collapseToggle = document.getElementById('collapseToggle');
                if (localStorage.getItem('bf-markets-collapsed') === 'true') {
                    document.body.classList.add('collapsed');
                }
                collapseToggle.addEventListener('click', function() {
                    document.body.classList.toggle('collapsed');
                    const isCollapsed = document.body.classList.contains('collapsed');
                    localStorage.setItem('bf-markets-collapsed', isCollapsed ? 'true' : 'false');
                    if (typeof ym !== 'undefined') {
                        ym(109087271, 'reachGoal', 'market_activity_collapse_toggle', {
                            state: isCollapsed ? 'collapsed' : 'expanded'
                        });
                    }
                });

                // ===== SIMULATION =====
                const SIM = {
                    tickInterval: 3000,
                    isProMode: false,
                    latency: 154,
                    previousRunners: {},
                    timerId: null
                };

                function rand(min, max) {
                    return Math.random() * (max - min) + min;
                }

                function randInt(min, max) {
                    return Math.floor(rand(min, max + 1));
                }

                function r2(n) {
                    return Math.round(n * 100) / 100;
                }

                function fmtSize(n) {
                    return n >= 1000 ? (n / 1000).toFixed(1) + 'K' : n.toString();
                }

                function fmtDelta(prev, curr) {
                    if (prev === 0) return '0%';
                    const pct = ((curr - prev) / prev) * 100;
                    if (Math.abs(pct) < 0.05) return '0%';
                    return (pct > 0 ? '+' : '') + pct.toFixed(1) + '%';
                }

                // ===== MARKET TAGS =====
                const MARKET_TAGS = @json($marketTags);
                const events = @json($events);

                // ===== SIMULATE =====
                function simulateTick() {
                    const numUpdates = SIM.isProMode ? randInt(2, 5) : randInt(1, 3);
                    const shuffled = events.slice().sort(function() {
                        return Math.random() - 0.5;
                    });
                    for (let i = 0; i < numUpdates && i < shuffled.length; i++) {
                        updateEvent(shuffled[i]);
                    }
                    SIM.latency = SIM.isProMode ? randInt(140, 177) : randInt(80, 150);
                    const latEl = document.getElementById('latencyDisplay');
                    if (latEl) latEl.textContent = SIM.latency;
                    updateVisibleCells();
                    if (currentEventId !== null) updateDetailPanel();
                }

                function updateEvent(ev) {
                    if (!SIM.previousRunners[ev.id]) {
                        SIM.previousRunners[ev.id] = ev.runners.map(function(r) {
                            return {
                                back: r.back,
                                lay: r.lay,
                                backSize: r.backSize,
                                laySize: r.laySize
                            };
                        });
                    }

                    ev.runners.forEach(function(runner) {
                        const backDrift = rand(-0.025, 0.025);
                        const layDrift = rand(-0.025, 0.025);

                        let volatilityFactor = 1;
                        if (ev.sport === 'horseracing' || ev.sport === 'greyhounds') volatilityFactor = 1.6;
                        else if (ev.sport === 'football' && ev.status === 'LIVE') volatilityFactor = 1.4;
                        else if (ev.sport === 'tennis') volatilityFactor = 1.2;

                        let speedFactor = 1;
                        if (SIM.tickInterval >= 5000) speedFactor = 1.8;
                        else if (SIM.tickInterval >= 3000) speedFactor = 1.4;
                        else if (SIM.tickInterval >= 2000) speedFactor = 1.2;

                        let newBack = r2(runner.back * (1 + backDrift * volatilityFactor * speedFactor));
                        let newLay = r2(runner.lay * (1 + layDrift * volatilityFactor * speedFactor));
                        if (newLay <= newBack) newLay = r2(newBack + Math.max(0.01, newBack * 0.01));
                        newBack = Math.max(1.01, Math.min(50, newBack));
                        newLay = Math.max(1.02, Math.min(60, newLay));

                        const backSizeChange = rand(-0.12, 0.16) * speedFactor;
                        const laySizeChange = rand(-0.12, 0.16) * speedFactor;
                        let newBackSize = Math.round(runner.backSize * (1 + backSizeChange));
                        let newLaySize = Math.round(runner.laySize * (1 + laySizeChange));
                        newBackSize = Math.max(500, Math.min(200000, newBackSize));
                        newLaySize = Math.max(500, Math.min(200000, newLaySize));

                        runner.back = newBack;
                        runner.lay = newLay;
                        runner.backSize = newBackSize;
                        runner.laySize = newLaySize;
                    });

                    let totalBack = 0,
                        totalLay = 0;
                    ev.runners.forEach(function(r) {
                        totalBack += r.backSize;
                        totalLay += r.laySize;
                    });
                    const ratio = totalBack / (totalBack + totalLay);
                    if (ratio > 0.58) ev.balance = 'back';
                    else if (ratio < 0.42) ev.balance = 'lay';
                    else ev.balance = 'balanced';

                    const favRunner = ev.runners[ev.favIdx];
                    const prevFav = SIM.previousRunners[ev.id][ev.favIdx];
                    if (prevFav) ev.delta = fmtDelta(prevFav.backSize, favRunner.backSize);

                    const deltaNum = parseFloat(ev.delta.replace('%', '').replace('+', ''));
                    if (deltaNum > 10) ev.momentum = 'strong_back';
                    else if (deltaNum > 3) ev.momentum = 'back';
                    else if (deltaNum < -10) ev.momentum = 'strong_lay';
                    else if (deltaNum < -3) ev.momentum = 'lay';
                    else ev.momentum = 'neutral';

                    const absDelta = Math.abs(deltaNum);
                    if (absDelta > 15) ev.volatility = 'high';
                    else if (absDelta > 5) ev.volatility = 'medium';
                    else ev.volatility = 'low';

                    SIM.previousRunners[ev.id] = ev.runners.map(function(r) {
                        return {
                            back: r.back,
                            lay: r.lay,
                            backSize: r.backSize,
                            laySize: r.laySize
                        };
                    });
                }

                // ===== HELPERS =====
                function balanceClass(bal) {
                    return bal === 'back' ? 'ind-back-heavy' : bal === 'lay' ? 'ind-lay-heavy' : 'ind-balanced';
                }

                function balanceText(bal) {
                    return bal === 'back' ? 'Back ↑' : bal === 'lay' ? 'Lay ↓' : 'Balanced →';
                }

                function momentumClass(mom) {
                    if (mom === 'strong_back') return 'ind-mom-strong-back';
                    if (mom === 'back') return 'ind-mom-back';
                    if (mom === 'strong_lay') return 'ind-mom-strong-lay';
                    if (mom === 'lay') return 'ind-mom-lay';
                    return 'ind-mom-neutral';
                }

                function momentumText(mom) {
                    if (mom === 'strong_back') return '↑↑';
                    if (mom === 'back') return '↑';
                    if (mom === 'strong_lay') return '↓↓';
                    if (mom === 'lay') return '↓';
                    return '→';
                }

                function deltaClass(d) {
                    return d.startsWith('+') ? 'ind-delta-pos' : d.startsWith('-') ? 'ind-delta-neg' : 'ind-delta-zero';
                }

                function volClass(v) {
                    return v === 'low' ? 'ind-vol-low' : v === 'high' ? 'ind-vol-high' : 'ind-vol-med';
                }

                function volText(v) {
                    return v === 'low' ? 'Low' : v === 'high' ? 'High' : 'Med';
                }

                function timeDisplay(ev) {
                    if (ev.status === 'LIVE') return '<span class="status-live">● LIVE</span> ' + ev.time;
                    return '<span class="status-up">▲ UP</span> ' + ev.time;
                }

                function getMarketTag(marketKey) {
                    const tag = MARKET_TAGS[marketKey] || {
                        label: marketKey,
                        cls: 'mo'
                    };
                    return '<span class="market-tag ' + tag.cls  ' inline-flex items-center [gap:4px] [padding:3px_10px] [border-radius:20px] [font-size:11px] font-bold whitespace-nowrap [&.mo]:[background:linear-gradient(135deg,_#e8f2fc,_#d4e8f8)] [&.mo]:[color:#1a6b9c] [&.mo]:[border:1px_solid_#b8d4ec] dark:[&.mo]:[background:linear-gradient(135deg,_#1a2e44,_#1f3444)] dark:[&.mo]:[color:#6aafdf] dark:[&.mo]:[border-color:#2a4a5e] [&.ou]:[background:linear-gradient(135deg,_#fff8e6,_#fef3d6)] [&.ou]:[color:#b8860b] [&.ou]:[border:1px_solid_#f0dfa8] dark:[&.ou]:[background:linear-gradient(135deg,_#3a2e1a,_#2e2414)] dark:[&.ou]:[color:#e6b422] dark:[&.ou]:[border-color:#5a4a1a] [&.btts]:[background:linear-gradient(135deg,_#e8f7ee,_#d6f0e0)] [&.btts]:[color:#1f9a6e] [&.btts]:[border:1px_solid_#c6e8d3] dark:[&.btts]:[background:linear-gradient(135deg,_#1a3a2a,_#14301f)] dark:[&.btts]:[color:#5ab88a] dark:[&.btts]:[border-color:#2a5a3e] [&.cs]:[background:linear-gradient(135deg,_#fce8ee,_#f8d9e2)] [&.cs]:[color:#d44a6a] [&.cs]:[border:1px_solid_#f0c9d4] dark:[&.cs]:[background:linear-gradient(135deg,_#3a1f2a,_#2e1a22)] dark:[&.cs]:[color:#e0809a] dark:[&.cs]:[border-color:#5a2a3e] [&.fh]:[background:linear-gradient(135deg,_#f0e8fc,_#e8dcf8)] [&.fh]:[color:#7a4a9c] [&.fh]:[border:1px_solid_#d8c4ec] dark:[&.fh]:[background:linear-gradient(135deg,_#2e1a44,_#251535)] dark:[&.fh]:[color:#b888df] dark:[&.fh]:[border-color:#4a2a5e] [&.win]:[background:linear-gradient(135deg,_#fde8e8,_#fcd9d9)] [&.win]:[color:#c74e4e] [&.win]:[border:1px_solid_#f0c9c9] dark:[&.win]:[background:linear-gradient(135deg,_#3a1f1f,_#2e1a1a)] dark:[&.win]:[color:#e08080] dark:[&.win]:[border-color:#5a2a2a]">' + tag.label + '</span>';
                }

                function getFavDisplay(ev) {
                    if (ev.market === 'correct_score') {
                        return '<span class="fav-icon">⚽</span>' + (ev.marketDisplayScore || ev.score || '—');
                    }
                    const fav = ev.runners[ev.favIdx];
                    return '<span class="fav-icon">⭐</span>' + fav.name;
                }

                // ===== RENDER TABLE =====
                const eventsBody = document.getElementById('eventsBody');
                const terminal = document.getElementById('terminal');
                const detailPanel = document.getElementById('detailPanel');
                const emptyPanel = document.getElementById('emptyPanel');
                const detailContent = document.getElementById('detailContent');
                const marketContainer = document.getElementById('marketContainer');
                const marketSelect = document.getElementById('marketSelect');
                let currentEventId = null;
                let activeMarketFilter = 'active';

                // function renderEvents(list) {
                //     eventsBody.innerHTML = '';
                //     list.forEach(function(ev) {
                //         alert("asdasd");

                //         const tr = document.createElement('tr');
                //         tr.dataset.id = ev.id;

                //         const favRunner = ev.runners[ev.favIdx];
                //         const favMark = (ev.sport === 'horseracing' || ev.sport === 'greyhounds') ?
                //             ' <span class="fav-star [color:#b8ccdf] [font-size:15px] cursor-pointer [background:none] border-0 [width:24px] [transition:0.2s] text-center dark:[color:#4a6a88] dark:[&.active-fav]:[color:#f5b342] [&.active-fav]:[color:#f5b342]">⭐</span>' : '';

                //         tr.innerHTML = `
                //     <td class="col-time font-bold [color:#0b2a40] dark:[color:#e8edf2] [&_.status-live]:[color:#c74e4e] [&_.status-live]:[font-weight:800] [&_.status-live]:[margin-right:4px] dark:[&_.status-live]:[color:#e08080] [&_.status-up]:[color:#1a6b9c] [&_.status-up]:[font-weight:700] [&_.status-up]:[margin-right:4px] dark:[&_.status-up]:[color:#6aafdf]">${timeDisplay(ev)}</td>
                //     <td class="col-comp [font-size:11.5px] [color:#8aaccc] dark:[color:#5a7d99]">${ev.sportIcon} ${ev.comp}</td>
                //     <td class="col-match font-semibold [color:#0b2a40] dark:[color:#e8edf2] [&_.fav-star]:[color:#e6b422] [&_.fav-star]:[margin-left:4px] [&_.fav-star]:[font-size:12px]">${ev.match}${favMark}</td>
                //     <td class="col-market font-semibold [font-size:12px]">${getMarketTag(ev.market)}</td>
                //     <td class="col-fav font-bold [font-size:12.5px] [color:#0b2a40] dark:[color:#e8edf2] [&_.fav-icon]:[color:#e6b422] [&_.fav-icon]:[font-size:11px] [&_.fav-icon]:[margin-right:4px]">${getFavDisplay(ev)}</td>
                //     <td class="col-odds"><span class="odds-cell odds-cell-back [background:#72bbef] [color:#0b2a40] [border:1px_solid_#4a9fd8] dark:[background:#72bbef] dark:[color:#0b2a40] dark:[border-color:#4a9fd8] [text-align:center] [font-weight:800] [font-size:13.5px] [border-radius:4px] [padding:5px_8px]! [min-width:55px] [display:inline-block] [transition:background_0.4s_ease,_color_0.4s_ease] [margin:0_auto] [font-variant-numeric:tabular-nums] max-[768px]:[font-size:12.5px] max-[768px]:[padding:4px_6px]! max-[768px]:[min-width:50px]">${favRunner.back.toFixed(2)}</span></td>
                //     <td class="col-odds"><span class="size-cell size-cell-back [background:rgba(114,_187,_239,_0.18)] [color:#1a6b9c] dark:[background:rgba(114,_187,_239,_0.15)] dark:[color:#72bbef] [text-align:center] [font-weight:700] [font-size:12px] [border-radius:4px] [padding:5px_8px]! [min-width:60px] [display:inline-block] [margin:0_auto] [font-variant-numeric:tabular-nums] [transition:background_0.4s_ease,_color_0.4s_ease] max-[768px]:[font-size:11px] max-[768px]:[padding:4px_6px]! max-[768px]:[min-width:50px]">${fmtSize(favRunner.backSize)}</span></td>
                //     <td class="col-odds"><span class="odds-cell odds-cell-lay [background:#faa9ba] [color:#4a1520] [border:1px_solid_#e8889a] dark:[background:#faa9ba] dark:[color:#4a1520] dark:[border-color:#e8889a] [text-align:center] [font-weight:800] [font-size:13.5px] [border-radius:4px] [padding:5px_8px]! [min-width:55px] [display:inline-block] [transition:background_0.4s_ease,_color_0.4s_ease] [margin:0_auto] [font-variant-numeric:tabular-nums] max-[768px]:[font-size:12.5px] max-[768px]:[padding:4px_6px]! max-[768px]:[min-width:50px]">${favRunner.lay.toFixed(2)}</span></td>
                //     <td class="col-odds"><span class="size-cell size-cell-lay [background:rgba(250,_169,_186,_0.18)] [color:#a8425a] dark:[background:rgba(250,_169,_186,_0.15)] dark:[color:#faa9ba] [text-align:center] [font-weight:700] [font-size:12px] [border-radius:4px] [padding:5px_8px]! [min-width:60px] [display:inline-block] [margin:0_auto] [font-variant-numeric:tabular-nums] [transition:background_0.4s_ease,_color_0.4s_ease] max-[768px]:[font-size:11px] max-[768px]:[padding:4px_6px]! max-[768px]:[min-width:50px]">${fmtSize(favRunner.laySize)}</span></td>
                //     <td><span class="ind ${balanceClass(ev.balance)} inline-flex items-center [gap:4px] [padding:3px_8px] [border-radius:12px] [font-size:11px] font-bold whitespace-nowrap [transition:0.3s]">${balanceText(ev.balance)}</span></td>
                //     <td><span class="ind ${momentumClass(ev.momentum)} inline-flex items-center [gap:4px] [padding:3px_8px] [border-radius:12px] [font-size:11px] font-bold whitespace-nowrap [transition:0.3s]">${momentumText(ev.momentum)}</span></td>
                //     <td><span class="ind ${deltaClass(ev.delta)} inline-flex items-center [gap:4px] [padding:3px_8px] [border-radius:12px] [font-size:11px] font-bold whitespace-nowrap [transition:0.3s]">${ev.delta}</span></td>
                //     <td><span class="ind ${volClass(ev.volatility)} inline-flex items-center [gap:4px] [padding:3px_8px] [border-radius:12px] [font-size:11px] font-bold whitespace-nowrap [transition:0.3s]">${volText(ev.volatility)}</span></td>
                //     <td><button class="open-btn inline-flex items-center [gap:4px] [padding:5px_12px] [border-radius:20px] [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white [font-size:11.5px] font-bold border-0 cursor-pointer [transition:transform_0.2s,_box-shadow_0.2s] [font-family:inherit] whitespace-nowrap dark:[background:linear-gradient(135deg,_#1a6b9c,_#4a8ab5)] [&:hover]:[transform:translateY(-1px)] [&:hover]:[box-shadow:0_6px_16px_rgba(26,_107,_156,_0.3)]" data-id="${ev.id}">Open →</button></td>
                // `;

                //         eventsBody.appendChild(tr);
                //     });
                // }

                eventsBody.addEventListener('click', function(e) {
                    const row = e.target.closest('tr[data-id]');
                    if (row && eventsBody.contains(row)) openDetail(Number(row.dataset.id));
                });

                // function updateVisibleCells() {
                //     const rows = eventsBody.querySelectorAll('tr');
                //     rows.forEach(function(tr) {
                //         const id = parseInt(tr.dataset.id);
                //         const ev = events.find(function(e) {
                //             return e.id === id;
                //         });
                //         if (!ev) return;
                //         const favRunner = ev.runners[ev.favIdx];
                //         const cells = tr.querySelectorAll('td');

                //         const backEl = cells[5].querySelector('.odds-cell-back');
                //         const newBackText = favRunner.back.toFixed(2);
                //         if (backEl.textContent !== newBackText) {
                //             backEl.textContent = newBackText;
                //             backEl.classList.add('odds-flash-up');
                //             setTimeout(function() {
                //                 backEl.classList.remove('odds-flash-up');
                //             }, 500);
                //         }

                //         cells[6].querySelector('.size-cell-back').textContent = fmtSize(favRunner.backSize);

                //         const layEl = cells[7].querySelector('.odds-cell-lay');
                //         const newLayText = favRunner.lay.toFixed(2);
                //         if (layEl.textContent !== newLayText) {
                //             layEl.textContent = newLayText;
                //             layEl.classList.add('odds-flash-down');
                //             setTimeout(function() {
                //                 layEl.classList.remove('odds-flash-down');
                //             }, 500);
                //         }

                //         cells[8].querySelector('.size-cell-lay').textContent = fmtSize(favRunner.laySize);
                //         cells[9].innerHTML = '<span class="ind ' + balanceClass(ev.balance)  ' inline-flex items-center [gap:4px] [padding:3px_8px] [border-radius:12px] [font-size:11px] font-bold whitespace-nowrap [transition:0.3s]">' + balanceText(ev
                //             .balance) + '</span>';
                //         cells[10].innerHTML = '<span class="ind ' + momentumClass(ev.momentum)  ' inline-flex items-center [gap:4px] [padding:3px_8px] [border-radius:12px] [font-size:11px] font-bold whitespace-nowrap [transition:0.3s]">' +
                //             momentumText(ev.momentum) + '</span>';
                //         cells[11].innerHTML = '<span class="ind ' + deltaClass(ev.delta)  ' inline-flex items-center [gap:4px] [padding:3px_8px] [border-radius:12px] [font-size:11px] font-bold whitespace-nowrap [transition:0.3s]">' + ev.delta +
                //             '</span>';
                //         cells[12].innerHTML = '<span class="ind ' + volClass(ev.volatility)  ' inline-flex items-center [gap:4px] [padding:3px_8px] [border-radius:12px] [font-size:11px] font-bold whitespace-nowrap [transition:0.3s]">' + volText(ev
                //             .volatility) + '</span>';
                //     });
                // }

                // // ===== DETAIL PANEL =====
                // function updateDetailPanel() {
                //     const ev = events.find(function(e) {
                //         return e.id === currentEventId;
                //     });
                //     if (!ev) return;

                //     const runnerRows = detailContent.querySelectorAll('.runner-row');
                //     runnerRows.forEach(function(row, idx) {
                //         const r = ev.runners[idx];
                //         if (!r) return;
                //         const backEl = row.querySelector('.runner-back');
                //         const layEl = row.querySelector('.runner-lay');
                //         if (backEl) backEl.innerHTML = r.back.toFixed(2) + '<span class="runner-size [font-size:10px] [opacity:0.75] font-semibold block [margin-top:2px]">' + fmtSize(r
                //             .backSize) + '</span>';
                //         if (layEl) layEl.innerHTML = r.lay.toFixed(2) + '<span class="runner-size [font-size:10px] [opacity:0.75] font-semibold block [margin-top:2px]">' + fmtSize(r
                //             .laySize) + '</span>';
                //     });

                //     let totalBack = 0,
                //         totalLay = 0;
                //     ev.runners.forEach(function(r) {
                //         totalBack += r.backSize;
                //         totalLay += r.laySize;
                //     });
                //     const totalLiq = totalBack + totalLay;
                //     const backPct = totalLiq > 0 ? (totalBack / totalLiq * 100) : 50;
                //     const layPct = 100 - backPct;

                //     const liqBack = detailContent.querySelector('.liq-back');
                //     const liqLay = detailContent.querySelector('.liq-lay');
                //     if (liqBack) {
                //         liqBack.style.width = backPct + '%';
                //         liqBack.textContent = 'Back ' + backPct.toFixed(0) + '%';
                //     }
                //     if (liqLay) {
                //         liqLay.style.width = layPct + '%';
                //         liqLay.textContent = 'Lay ' + layPct.toFixed(0) + '%';
                //     }

                //     const indBoxes = detailContent.querySelectorAll('.indicator-box');
                //     if (indBoxes.length >= 4) {
                //         indBoxes[0].querySelector('.ind-value').textContent = balanceText(ev.balance);
                //         indBoxes[0].querySelector('.ind-value').className = 'ind-value ' + (ev.balance === 'back' ?
                //             'back-heavy' : ev.balance === 'lay' ? 'lay-heavy' : 'balanced');
                //         indBoxes[1].querySelector('.ind-value').textContent = momentumText(ev.momentum);
                //         indBoxes[1].querySelector('.ind-value').className = 'ind-value ' + (ev.momentum === 'back' || ev
                //             .momentum === 'strong_back' ? 'back-heavy' : ev.momentum === 'lay' || ev.momentum ===
                //             'strong_lay' ? 'lay-heavy' : 'balanced');
                //         indBoxes[2].querySelector('.ind-value').textContent = ev.delta;
                //         indBoxes[2].querySelector('.ind-value').className = 'ind-value ' + (ev.delta.startsWith('+') ?
                //             'vol-low' : ev.delta.startsWith('-') ? 'vol-high' : 'balanced');
                //         indBoxes[3].querySelector('.ind-value').textContent = volText(ev.volatility);
                //         indBoxes[3].querySelector('.ind-value').className = 'ind-value vol-' + (ev.volatility === 'low' ?
                //             'low' : ev.volatility === 'high' ? 'high' : 'med');
                //     }
                // }

                function openDetail(id) {
                    const ev = events.find(function(e) {
                        return e.id === id;
                    });
                    if (!ev) return;
                    currentEventId = id;

                    document.querySelectorAll('.events-table tbody tr').forEach(function(r) {
                        r.classList.toggle('selected', parseInt(r.dataset.id) === id);
                    });

                    let totalBack = 0,
                        totalLay = 0;
                    ev.runners.forEach(function(r) {
                        totalBack += r.backSize;
                        totalLay += r.laySize;
                    });
                    const totalLiq = totalBack + totalLay;
                    const backPct = totalLiq > 0 ? (totalBack / totalLiq * 100) : 50;
                    const layPct = 100 - backPct;

                    let runnersHtml = '';
                    ev.runners.forEach(function(r, idx) {
                        const isFav = idx === ev.favIdx;
                        runnersHtml += `
                    <div class="runner-row grid [grid-template-columns:1fr_auto_auto] [gap:10px] items-center [padding:10px_12px] bg-white [border:1px_solid_#e6eff8] [border-radius:10px] [font-size:12.5px] [transition:0.3s] dark:[background:#1a2a38] dark:[border-color:#2a3f50]">
                        <div class="runner-name font-bold [color:#0b2a40] flex items-center [gap:5px] dark:[color:#e8edf2] [&_.fav-star]:[color:#e6b422] [&_.fav-star]:[font-size:13px]">${r.name}${isFav ? ' <span class="fav-star [color:#b8ccdf] [font-size:15px] cursor-pointer [background:none] border-0 [width:24px] [transition:0.2s] text-center dark:[color:#4a6a88] dark:[&.active-fav]:[color:#f5b342] [&.active-fav]:[color:#f5b342]">⭐</span>' : ''}</div>
                        <div class="runner-back [background:#72bbef] [color:#0b2a40] [border:1px_solid_#4a9fd8] [border-radius:4px] [padding:5px_10px] font-extrabold text-center [min-width:70px] [font-variant-numeric:tabular-nums]">${r.back.toFixed(2)}<span class="runner-size [font-size:10px] [opacity:0.75] font-semibold block [margin-top:2px]">${fmtSize(r.backSize)}</span></div>
                        <div class="runner-lay [background:#faa9ba] [color:#4a1520] [border:1px_solid_#e8889a] [border-radius:4px] [padding:5px_10px] font-extrabold text-center [min-width:70px] [font-variant-numeric:tabular-nums]">${r.lay.toFixed(2)}<span class="runner-size [font-size:10px] [opacity:0.75] font-semibold block [margin-top:2px]">${fmtSize(r.laySize)}</span></div>
                    </div>
                `;
                    });

                    let scoreHtml = ev.score ? `<span>· ${ev.score}</span>` : '';
                    let metaHtml =
                        `${ev.sportIcon} ${ev.comp} · ${ev.status === 'LIVE' ? '● LIVE' : '▲ UPCOMING'} · ${ev.time} ${scoreHtml}`;

                    detailContent.innerHTML = `
                        <div class="detail-header [background:linear-gradient(135deg,_#0b2a40,_#1a6b9c)] text-white [padding:10px_16px] [position:sticky] [top:0] [z-index:10] max-[480px]:[padding:8px_12px]">
                            <div class="detail-header-top flex items-center justify-between [gap:10px]">
                                <div class="detail-title [font-size:14.5px] font-extrabold [flex:1] min-w-0 whitespace-nowrap overflow-hidden [text-overflow:ellipsis] max-[480px]:[font-size:13px]">${ev.match}</div>
                                <div class="detail-header-actions flex [gap:6px] shrink-0">
                                    <button class="detail-action-btn [background:rgba(255,_255,_255,_0.15)] border-0 text-white [width:28px] [height:28px] [border-radius:50%] [font-size:13px] cursor-pointer flex items-center justify-center [transition:background_0.2s,_transform_0.2s] [font-family:inherit] [&:hover]:[background:rgba(255,_255,_255,_0.3)] [&:hover]:[transform:scale(1.08)] [&.expand-active]:[background:rgba(255,_255,_255,_0.35)]" id="expandBtn" title="Expand"><i class="fas fa-expand"></i></button>
                                    <button class="detail-action-btn [background:rgba(255,_255,_255,_0.15)] border-0 text-white [width:28px] [height:28px] [border-radius:50%] [font-size:13px] cursor-pointer flex items-center justify-center [transition:background_0.2s,_transform_0.2s] [font-family:inherit] [&:hover]:[background:rgba(255,_255,_255,_0.3)] [&:hover]:[transform:scale(1.08)] [&.expand-active]:[background:rgba(255,_255,_255,_0.35)]" onclick="closeDetail()" title="Close"><i class="fas fa-times"></i></button>
                                </div>
                            </div>
                            <div class="detail-meta [font-size:11.5px] [opacity:0.9] flex items-center [gap:8px] flex-wrap [margin-top:2px] max-[480px]:[font-size:10.5px]">${metaHtml}</div>
                        </div>
                        <div class="detail-body [padding:16px_20px] max-[480px]:[padding:14px_16px]">
                            <div class="detail-section [margin-bottom:20px] [&:last-child]:[margin-bottom:0]">
                                <div class="detail-section-title [font-size:11px] font-extrabold uppercase [letter-spacing:0.6px] [color:#1a6b9c] [margin-bottom:10px] flex items-center [gap:6px] dark:[color:#6aafdf]"><i class="fas fa-users"></i> Runners</div>
                                <div class="runners-list flex flex-col [gap:8px]">${runnersHtml}</div>
                            </div>
                            <div class="detail-section [margin-bottom:20px] [&:last-child]:[margin-bottom:0]">
                                <div class="detail-section-title [font-size:11px] font-extrabold uppercase [letter-spacing:0.6px] [color:#1a6b9c] [margin-bottom:10px] flex items-center [gap:6px] dark:[color:#6aafdf]"><i class="fas fa-water"></i> Liquidity Distribution</div>
                                <div class="liquidity-bar flex [height:26px] [border-radius:8px] overflow-hidden [font-size:11px] font-bold [margin-top:6px]">
                                    <div class="liq-back [background:linear-gradient(135deg,_#1a6b9c,_#4a8ab5)] text-white flex items-center justify-start [padding-left:10px] [transition:width_0.6s_ease]" style="width:${backPct}%;">Back ${backPct.toFixed(0)}%</div>
                                    <div class="liq-lay [background:linear-gradient(135deg,_#d44a6a,_#e0809a)] text-white flex items-center justify-end [padding-right:10px] [transition:width_0.6s_ease]" style="width:${layPct}%;">Lay ${layPct.toFixed(0)}%</div>
                                </div>
                            </div>
                            <div class="detail-section [margin-bottom:20px] [&:last-child]:[margin-bottom:0]">
                                <div class="detail-section-title [font-size:11px] font-extrabold uppercase [letter-spacing:0.6px] [color:#1a6b9c] [margin-bottom:10px] flex items-center [gap:6px] dark:[color:#6aafdf]"><i class="fas fa-chart-line"></i> Indicators</div>
                                <div class="indicators-grid grid [grid-template-columns:repeat(3,_1fr)] [gap:8px] max-[480px]:[grid-template-columns:repeat(3,_1fr)] max-[480px]:[gap:6px]">
                                    <div class="indicator-box bg-white [border:1px_solid_#e6eff8] [border-radius:10px] [padding:10px_8px] text-center [transition:0.3s] dark:[background:#1a2a38] dark:[border-color:#2a3f50] [&_.ind-label]:[font-size:9.5px] [&_.ind-label]:[font-weight:800] [&_.ind-label]:[text-transform:uppercase] [&_.ind-label]:[letter-spacing:0.5px] [&_.ind-label]:[color:#8aaccc] [&_.ind-label]:[margin-bottom:4px] [&_.ind-value]:[font-size:13px] [&_.ind-value]:[font-weight:800] [&_.ind-value]:[color:#0b2a40] dark:[&_.ind-value]:[color:#e8edf2] [&_.ind-value.back-heavy]:[color:#1a6b9c] dark:[&_.ind-value.back-heavy]:[color:#6aafdf] [&_.ind-value.lay-heavy]:[color:#d44a6a] dark:[&_.ind-value.lay-heavy]:[color:#e0809a] [&_.ind-value.balanced]:[color:#6a8aaa] dark:[&_.ind-value.balanced]:[color:#8aaccc] [&_.ind-value.vol-low]:[color:#1f9a6e] dark:[&_.ind-value.vol-low]:[color:#5ab88a] [&_.ind-value.vol-high]:[color:#c74e4e] dark:[&_.ind-value.vol-high]:[color:#e08080] max-[480px]:[padding:8px_6px] max-[480px]:[&_.ind-value]:[font-size:12px]"><div class="ind-label">Balance</div><div class="ind-value ${ev.balance === 'back' ? 'back-heavy' : ev.balance  'lay'  'lay-heavy'  'balanced'}">${balanceText(ev.balance)}</div></div>
                                    <div class="indicator-box bg-white [border:1px_solid_#e6eff8] [border-radius:10px] [padding:10px_8px] text-center [transition:0.3s] dark:[background:#1a2a38] dark:[border-color:#2a3f50] [&_.ind-label]:[font-size:9.5px] [&_.ind-label]:[font-weight:800] [&_.ind-label]:[text-transform:uppercase] [&_.ind-label]:[letter-spacing:0.5px] [&_.ind-label]:[color:#8aaccc] [&_.ind-label]:[margin-bottom:4px] [&_.ind-value]:[font-size:13px] [&_.ind-value]:[font-weight:800] [&_.ind-value]:[color:#0b2a40] dark:[&_.ind-value]:[color:#e8edf2] [&_.ind-value.back-heavy]:[color:#1a6b9c] dark:[&_.ind-value.back-heavy]:[color:#6aafdf] [&_.ind-value.lay-heavy]:[color:#d44a6a] dark:[&_.ind-value.lay-heavy]:[color:#e0809a] [&_.ind-value.balanced]:[color:#6a8aaa] dark:[&_.ind-value.balanced]:[color:#8aaccc] [&_.ind-value.vol-low]:[color:#1f9a6e] dark:[&_.ind-value.vol-low]:[color:#5ab88a] [&_.ind-value.vol-high]:[color:#c74e4e] dark:[&_.ind-value.vol-high]:[color:#e08080] max-[480px]:[padding:8px_6px] max-[480px]:[&_.ind-value]:[font-size:12px]"><div class="ind-label">Momentum</div><div class="ind-value ${ev.momentum === 'back' || ev.momentum  'strong_back' ? 'back-heavy' :   'lay'    'strong_lay'  'lay-heavy'  'balanced'}">${momentumText(ev.momentum)}</div></div>
                                    <div class="indicator-box bg-white [border:1px_solid_#e6eff8] [border-radius:10px] [padding:10px_8px] text-center [transition:0.3s] dark:[background:#1a2a38] dark:[border-color:#2a3f50] [&_.ind-label]:[font-size:9.5px] [&_.ind-label]:[font-weight:800] [&_.ind-label]:[text-transform:uppercase] [&_.ind-label]:[letter-spacing:0.5px] [&_.ind-label]:[color:#8aaccc] [&_.ind-label]:[margin-bottom:4px] [&_.ind-value]:[font-size:13px] [&_.ind-value]:[font-weight:800] [&_.ind-value]:[color:#0b2a40] dark:[&_.ind-value]:[color:#e8edf2] [&_.ind-value.back-heavy]:[color:#1a6b9c] dark:[&_.ind-value.back-heavy]:[color:#6aafdf] [&_.ind-value.lay-heavy]:[color:#d44a6a] dark:[&_.ind-value.lay-heavy]:[color:#e0809a] [&_.ind-value.balanced]:[color:#6a8aaa] dark:[&_.ind-value.balanced]:[color:#8aaccc] [&_.ind-value.vol-low]:[color:#1f9a6e] dark:[&_.ind-value.vol-low]:[color:#5ab88a] [&_.ind-value.vol-high]:[color:#c74e4e] dark:[&_.ind-value.vol-high]:[color:#e08080] max-[480px]:[padding:8px_6px] max-[480px]:[&_.ind-value]:[font-size:12px]"><div class="ind-label">Delta</div><div class="ind-value ${ev.delta.startsWith('+') ? 'vol-low' : ev.delta.startsWith('-')  'vol-high'  'balanced'}">${ev.delta}</div></div>
                                    <div class="indicator-box bg-white [border:1px_solid_#e6eff8] [border-radius:10px] [padding:10px_8px] text-center [transition:0.3s] dark:[background:#1a2a38] dark:[border-color:#2a3f50] [&_.ind-label]:[font-size:9.5px] [&_.ind-label]:[font-weight:800] [&_.ind-label]:[text-transform:uppercase] [&_.ind-label]:[letter-spacing:0.5px] [&_.ind-label]:[color:#8aaccc] [&_.ind-label]:[margin-bottom:4px] [&_.ind-value]:[font-size:13px] [&_.ind-value]:[font-weight:800] [&_.ind-value]:[color:#0b2a40] dark:[&_.ind-value]:[color:#e8edf2] [&_.ind-value.back-heavy]:[color:#1a6b9c] dark:[&_.ind-value.back-heavy]:[color:#6aafdf] [&_.ind-value.lay-heavy]:[color:#d44a6a] dark:[&_.ind-value.lay-heavy]:[color:#e0809a] [&_.ind-value.balanced]:[color:#6a8aaa] dark:[&_.ind-value.balanced]:[color:#8aaccc] [&_.ind-value.vol-low]:[color:#1f9a6e] dark:[&_.ind-value.vol-low]:[color:#5ab88a] [&_.ind-value.vol-high]:[color:#c74e4e] dark:[&_.ind-value.vol-high]:[color:#e08080] max-[480px]:[padding:8px_6px] max-[480px]:[&_.ind-value]:[font-size:12px]"><div class="ind-label">Volatility</div><div class="ind-value vol-${ev.volatility === 'low' ? 'low' : ev.volatility  'high'  'high'  'med'}">${volText(ev.volatility)}</div></div>
                                </div>
                            </div>
                            <div class="terms-section collapsed [margin-top:20px] [padding-top:16px] [border-top:1px_dashed_#d4e0ec] [transition:border-color_0.3s] dark:[border-top-color:#3a5568] [&.collapsed_.terms-toggle-icon]:[transform:rotate(-90deg)] [&.collapsed_.terms-list]:[display:none] [body&_.collapse-toggle_i]:[transform:rotate(180deg)] [body&_.controls-wrapper]:[max-height:0] [body&_.controls-wrapper]:[opacity:0]" id="termsSection">
                                <div class="terms-header flex items-center justify-between cursor-pointer select-none [padding:6px_10px] [border-radius:8px] [background:rgba(26,_107,_156,_0.06)] [transition:background_0.2s] dark:[background:rgba(106,_175,_223,_0.08)] [&:hover]:[background:rgba(26,_107,_156,_0.12)] dark:[&:hover]:[background:rgba(106,_175,_223,_0.15)]" id="termsToggle">
                                    <div class="terms-header-left flex items-center [gap:8px] [font-size:11px] font-extrabold uppercase [letter-spacing:0.6px] [color:#1a6b9c] dark:[color:#6aafdf] [&_i]:[font-size:12px]"><i class="fas fa-book-open"></i><span>Terms</span></div>
                                    <i class="fas fa-chevron-down terms-toggle-icon [font-size:12px] [color:#8aaccc] [transition:transform_0.3s] dark:[color:#5a7d99]"></i>
                                </div>
                                <div class="terms-list flex flex-col [gap:5px] [margin-top:10px] [animation:fadeInTerms_0.25s_ease]">
                                    <div class="term-row flex [align-items:baseline] [gap:8px] [padding:5px_10px] bg-white [border:1px_solid_#e6eff8] [border-radius:6px] [font-size:11.5px] [line-height:1.4] [transition:0.3s] dark:[background:#1a2a38] dark:[border-color:#2a3f50] [&_.term-key]:[font-weight:800] [&_.term-key]:[color:#0b2a40] [&_.term-key]:[white-space:nowrap] [&_.term-key]:[min-width:78px] [&_.term-key]:[flex-shrink:0] dark:[&_.term-key]:[color:#e8edf2] [&_.term-key.term-back]:[color:#1a6b9c] dark:[&_.term-key.term-back]:[color:#72bbef] [&_.term-key.term-lay]:[color:#d44a6a] dark:[&_.term-key.term-lay]:[color:#faa9ba] [&_.term-desc]:[color:#5a7d99] [&_.term-desc]:[font-weight:500] dark:[&_.term-desc]:[color:#8aaccc] max-[480px]:[font-size:11px] max-[480px]:[padding:4px_8px] max-[480px]:[&_.term-key]:[min-width:70px]"><span class="term-key term-back">Back</span><span class="term-desc">Bet in favor of the event</span></div>
                                    <div class="term-row flex [align-items:baseline] [gap:8px] [padding:5px_10px] bg-white [border:1px_solid_#e6eff8] [border-radius:6px] [font-size:11.5px] [line-height:1.4] [transition:0.3s] dark:[background:#1a2a38] dark:[border-color:#2a3f50] [&_.term-key]:[font-weight:800] [&_.term-key]:[color:#0b2a40] [&_.term-key]:[white-space:nowrap] [&_.term-key]:[min-width:78px] [&_.term-key]:[flex-shrink:0] dark:[&_.term-key]:[color:#e8edf2] [&_.term-key.term-back]:[color:#1a6b9c] dark:[&_.term-key.term-back]:[color:#72bbef] [&_.term-key.term-lay]:[color:#d44a6a] dark:[&_.term-key.term-lay]:[color:#faa9ba] [&_.term-desc]:[color:#5a7d99] [&_.term-desc]:[font-weight:500] dark:[&_.term-desc]:[color:#8aaccc] max-[480px]:[font-size:11px] max-[480px]:[padding:4px_8px] max-[480px]:[&_.term-key]:[min-width:70px]"><span class="term-key term-lay">Lay</span><span class="term-desc">Bet against the event</span></div>
                                    <div class="term-row flex [align-items:baseline] [gap:8px] [padding:5px_10px] bg-white [border:1px_solid_#e6eff8] [border-radius:6px] [font-size:11.5px] [line-height:1.4] [transition:0.3s] dark:[background:#1a2a38] dark:[border-color:#2a3f50] [&_.term-key]:[font-weight:800] [&_.term-key]:[color:#0b2a40] [&_.term-key]:[white-space:nowrap] [&_.term-key]:[min-width:78px] [&_.term-key]:[flex-shrink:0] dark:[&_.term-key]:[color:#e8edf2] [&_.term-key.term-back]:[color:#1a6b9c] dark:[&_.term-key.term-back]:[color:#72bbef] [&_.term-key.term-lay]:[color:#d44a6a] dark:[&_.term-key.term-lay]:[color:#faa9ba] [&_.term-desc]:[color:#5a7d99] [&_.term-desc]:[font-weight:500] dark:[&_.term-desc]:[color:#8aaccc] max-[480px]:[font-size:11px] max-[480px]:[padding:4px_8px] max-[480px]:[&_.term-key]:[min-width:70px]"><span class="term-key">Odds</span><span class="term-desc">Price</span></div>
                                    <div class="term-row flex [align-items:baseline] [gap:8px] [padding:5px_10px] bg-white [border:1px_solid_#e6eff8] [border-radius:6px] [font-size:11.5px] [line-height:1.4] [transition:0.3s] dark:[background:#1a2a38] dark:[border-color:#2a3f50] [&_.term-key]:[font-weight:800] [&_.term-key]:[color:#0b2a40] [&_.term-key]:[white-space:nowrap] [&_.term-key]:[min-width:78px] [&_.term-key]:[flex-shrink:0] dark:[&_.term-key]:[color:#e8edf2] [&_.term-key.term-back]:[color:#1a6b9c] dark:[&_.term-key.term-back]:[color:#72bbef] [&_.term-key.term-lay]:[color:#d44a6a] dark:[&_.term-key.term-lay]:[color:#faa9ba] [&_.term-desc]:[color:#5a7d99] [&_.term-desc]:[font-weight:500] dark:[&_.term-desc]:[color:#8aaccc] max-[480px]:[font-size:11px] max-[480px]:[padding:4px_8px] max-[480px]:[&_.term-key]:[min-width:70px]"><span class="term-key">Size</span><span class="term-desc">Money available at best price</span></div>
                                    <div class="term-row flex [align-items:baseline] [gap:8px] [padding:5px_10px] bg-white [border:1px_solid_#e6eff8] [border-radius:6px] [font-size:11.5px] [line-height:1.4] [transition:0.3s] dark:[background:#1a2a38] dark:[border-color:#2a3f50] [&_.term-key]:[font-weight:800] [&_.term-key]:[color:#0b2a40] [&_.term-key]:[white-space:nowrap] [&_.term-key]:[min-width:78px] [&_.term-key]:[flex-shrink:0] dark:[&_.term-key]:[color:#e8edf2] [&_.term-key.term-back]:[color:#1a6b9c] dark:[&_.term-key.term-back]:[color:#72bbef] [&_.term-key.term-lay]:[color:#d44a6a] dark:[&_.term-key.term-lay]:[color:#faa9ba] [&_.term-desc]:[color:#5a7d99] [&_.term-desc]:[font-weight:500] dark:[&_.term-desc]:[color:#8aaccc] max-[480px]:[font-size:11px] max-[480px]:[padding:4px_8px] max-[480px]:[&_.term-key]:[min-width:70px]"><span class="term-key">Balance</span><span class="term-desc">Where the money is now</span></div>
                                    <div class="term-row flex [align-items:baseline] [gap:8px] [padding:5px_10px] bg-white [border:1px_solid_#e6eff8] [border-radius:6px] [font-size:11.5px] [line-height:1.4] [transition:0.3s] dark:[background:#1a2a38] dark:[border-color:#2a3f50] [&_.term-key]:[font-weight:800] [&_.term-key]:[color:#0b2a40] [&_.term-key]:[white-space:nowrap] [&_.term-key]:[min-width:78px] [&_.term-key]:[flex-shrink:0] dark:[&_.term-key]:[color:#e8edf2] [&_.term-key.term-back]:[color:#1a6b9c] dark:[&_.term-key.term-back]:[color:#72bbef] [&_.term-key.term-lay]:[color:#d44a6a] dark:[&_.term-key.term-lay]:[color:#faa9ba] [&_.term-desc]:[color:#5a7d99] [&_.term-desc]:[font-weight:500] dark:[&_.term-desc]:[color:#8aaccc] max-[480px]:[font-size:11px] max-[480px]:[padding:4px_8px] max-[480px]:[&_.term-key]:[min-width:70px]"><span class="term-key">Momentum</span><span class="term-desc">Where the money is moving</span></div>
                                    <div class="term-row flex [align-items:baseline] [gap:8px] [padding:5px_10px] bg-white [border:1px_solid_#e6eff8] [border-radius:6px] [font-size:11.5px] [line-height:1.4] [transition:0.3s] dark:[background:#1a2a38] dark:[border-color:#2a3f50] [&_.term-key]:[font-weight:800] [&_.term-key]:[color:#0b2a40] [&_.term-key]:[white-space:nowrap] [&_.term-key]:[min-width:78px] [&_.term-key]:[flex-shrink:0] dark:[&_.term-key]:[color:#e8edf2] [&_.term-key.term-back]:[color:#1a6b9c] dark:[&_.term-key.term-back]:[color:#72bbef] [&_.term-key.term-lay]:[color:#d44a6a] dark:[&_.term-key.term-lay]:[color:#faa9ba] [&_.term-desc]:[color:#5a7d99] [&_.term-desc]:[font-weight:500] dark:[&_.term-desc]:[color:#8aaccc] max-[480px]:[font-size:11px] max-[480px]:[padding:4px_8px] max-[480px]:[&_.term-key]:[min-width:70px]"><span class="term-key">Δ Delta</span><span class="term-desc">How fast Back Size changes</span></div>
                                    <div class="term-row flex [align-items:baseline] [gap:8px] [padding:5px_10px] bg-white [border:1px_solid_#e6eff8] [border-radius:6px] [font-size:11.5px] [line-height:1.4] [transition:0.3s] dark:[background:#1a2a38] dark:[border-color:#2a3f50] [&_.term-key]:[font-weight:800] [&_.term-key]:[color:#0b2a40] [&_.term-key]:[white-space:nowrap] [&_.term-key]:[min-width:78px] [&_.term-key]:[flex-shrink:0] dark:[&_.term-key]:[color:#e8edf2] [&_.term-key.term-back]:[color:#1a6b9c] dark:[&_.term-key.term-back]:[color:#72bbef] [&_.term-key.term-lay]:[color:#d44a6a] dark:[&_.term-key.term-lay]:[color:#faa9ba] [&_.term-desc]:[color:#5a7d99] [&_.term-desc]:[font-weight:500] dark:[&_.term-desc]:[color:#8aaccc] max-[480px]:[font-size:11px] max-[480px]:[padding:4px_8px] max-[480px]:[&_.term-key]:[min-width:70px]"><span class="term-key">Volatility</span><span class="term-desc">How unpredictable the market is</span></div>
                                    <div class="term-row flex [align-items:baseline] [gap:8px] [padding:5px_10px] bg-white [border:1px_solid_#e6eff8] [border-radius:6px] [font-size:11.5px] [line-height:1.4] [transition:0.3s] dark:[background:#1a2a38] dark:[border-color:#2a3f50] [&_.term-key]:[font-weight:800] [&_.term-key]:[color:#0b2a40] [&_.term-key]:[white-space:nowrap] [&_.term-key]:[min-width:78px] [&_.term-key]:[flex-shrink:0] dark:[&_.term-key]:[color:#e8edf2] [&_.term-key.term-back]:[color:#1a6b9c] dark:[&_.term-key.term-back]:[color:#72bbef] [&_.term-key.term-lay]:[color:#d44a6a] dark:[&_.term-key.term-lay]:[color:#faa9ba] [&_.term-desc]:[color:#5a7d99] [&_.term-desc]:[font-weight:500] dark:[&_.term-desc]:[color:#8aaccc] max-[480px]:[font-size:11px] max-[480px]:[padding:4px_8px] max-[480px]:[&_.term-key]:[min-width:70px]"><span class="term-key">Liquidity</span><span class="term-desc">Total money in the market</span></div>
                                    <div class="term-row flex [align-items:baseline] [gap:8px] [padding:5px_10px] bg-white [border:1px_solid_#e6eff8] [border-radius:6px] [font-size:11.5px] [line-height:1.4] [transition:0.3s] dark:[background:#1a2a38] dark:[border-color:#2a3f50] [&_.term-key]:[font-weight:800] [&_.term-key]:[color:#0b2a40] [&_.term-key]:[white-space:nowrap] [&_.term-key]:[min-width:78px] [&_.term-key]:[flex-shrink:0] dark:[&_.term-key]:[color:#e8edf2] [&_.term-key.term-back]:[color:#1a6b9c] dark:[&_.term-key.term-back]:[color:#72bbef] [&_.term-key.term-lay]:[color:#d44a6a] dark:[&_.term-key.term-lay]:[color:#faa9ba] [&_.term-desc]:[color:#5a7d99] [&_.term-desc]:[font-weight:500] dark:[&_.term-desc]:[color:#8aaccc] max-[480px]:[font-size:11px] max-[480px]:[padding:4px_8px] max-[480px]:[&_.term-key]:[min-width:70px]"><span class="term-key">Runner</span><span class="term-desc">Participant in the market</span></div>
                                    <div class="term-row flex [align-items:baseline] [gap:8px] [padding:5px_10px] bg-white [border:1px_solid_#e6eff8] [border-radius:6px] [font-size:11.5px] [line-height:1.4] [transition:0.3s] dark:[background:#1a2a38] dark:[border-color:#2a3f50] [&_.term-key]:[font-weight:800] [&_.term-key]:[color:#0b2a40] [&_.term-key]:[white-space:nowrap] [&_.term-key]:[min-width:78px] [&_.term-key]:[flex-shrink:0] dark:[&_.term-key]:[color:#e8edf2] [&_.term-key.term-back]:[color:#1a6b9c] dark:[&_.term-key.term-back]:[color:#72bbef] [&_.term-key.term-lay]:[color:#d44a6a] dark:[&_.term-key.term-lay]:[color:#faa9ba] [&_.term-desc]:[color:#5a7d99] [&_.term-desc]:[font-weight:500] dark:[&_.term-desc]:[color:#8aaccc] max-[480px]:[font-size:11px] max-[480px]:[padding:4px_8px] max-[480px]:[&_.term-key]:[min-width:70px]"><span class="term-key">Prematch</span><span class="term-desc">Market before event starts</span></div>
                                    <div class="term-row flex [align-items:baseline] [gap:8px] [padding:5px_10px] bg-white [border:1px_solid_#e6eff8] [border-radius:6px] [font-size:11.5px] [line-height:1.4] [transition:0.3s] dark:[background:#1a2a38] dark:[border-color:#2a3f50] [&_.term-key]:[font-weight:800] [&_.term-key]:[color:#0b2a40] [&_.term-key]:[white-space:nowrap] [&_.term-key]:[min-width:78px] [&_.term-key]:[flex-shrink:0] dark:[&_.term-key]:[color:#e8edf2] [&_.term-key.term-back]:[color:#1a6b9c] dark:[&_.term-key.term-back]:[color:#72bbef] [&_.term-key.term-lay]:[color:#d44a6a] dark:[&_.term-key.term-lay]:[color:#faa9ba] [&_.term-desc]:[color:#5a7d99] [&_.term-desc]:[font-weight:500] dark:[&_.term-desc]:[color:#8aaccc] max-[480px]:[font-size:11px] max-[480px]:[padding:4px_8px] max-[480px]:[&_.term-key]:[min-width:70px]"><span class="term-key">Favorite</span><span class="term-desc">Participant with the lowest Back Odds</span></div>
                                </div>
                            </div>
                        </div>
                    `;

                    emptyPanel.style.display = 'none';
                    detailContent.style.display = 'block';
                    detailPanel.classList.add('open');
                    terminal.classList.add('with-panel');
                    terminal.classList.remove('panel-expanded');

                    const expandBtn = document.getElementById('expandBtn');
                    if (expandBtn) expandBtn.addEventListener('click', toggleExpand);

                    const termsToggle = document.getElementById('termsToggle');
                    const termsSection = document.getElementById('termsSection');
                    if (termsToggle && termsSection) {
                        termsToggle.addEventListener('click', function() {
                            termsSection.classList.toggle('collapsed');
                        });
                    }

                    if (typeof ym !== 'undefined') {
                        ym(109087271, 'reachGoal', 'market_activity_open_detail', {
                            event: ev.match
                        });
                    }
                }

                function toggleExpand() {
                    const expandBtn = document.getElementById('expandBtn');
                    const isExpanded = terminal.classList.toggle('panel-expanded');
                    if (isExpanded) {
                        terminal.classList.remove('with-panel');
                        if (expandBtn) {
                            expandBtn.innerHTML = '<i class="fas fa-compress"></i>';
                            expandBtn.classList.add('expand-active');
                        }
                        document.body.style.overflow = 'hidden';
                    } else {
                        terminal.classList.add('with-panel');
                        if (expandBtn) {
                            expandBtn.innerHTML = '<i class="fas fa-expand"></i>';
                            expandBtn.classList.remove('expand-active');
                        }
                        document.body.style.overflow = '';
                    }
                }

                window.closeDetail = function() {
                    detailPanel.classList.remove('open');
                    terminal.classList.remove('with-panel');
                    terminal.classList.remove('panel-expanded');
                    document.body.style.overflow = '';
                    detailContent.style.display = 'none';
                    emptyPanel.style.display = 'flex';
                    currentEventId = null;
                    document.querySelectorAll('.events-table tbody tr').forEach(function(r) {
                        r.classList.remove('selected');
                    });
                };

                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        if (terminal.classList.contains('panel-expanded')) toggleExpand();
                        else if (detailPanel.classList.contains('open')) closeDetail();
                    }
                });

                // ===== FILTERS =====
                let activeFilter = 'all';
                let activeSport = 'all';

                // function applyFilters() {
                //     let filtered = events.slice();

                //     if (activeSport !== 'all') {
                //         filtered = filtered.filter(function(e) {
                //             return e.sport === activeSport;
                //         });
                //     }

                //     if (activeFilter === 'live') filtered = filtered.filter(function(e) {
                //         return e.status === 'LIVE';
                //     });
                //     else if (activeFilter === 'upcoming') filtered = filtered.filter(function(e) {
                //         return e.status === 'UP';
                //     });
                //     else if (activeFilter === 'ht00') filtered = filtered.filter(function(e) {
                //         return e.isHT00;
                //     });
                //     else if (activeFilter === '00_70') filtered = filtered.filter(function(e) {
                //         return e.is00at70;
                //     });
                //     else if (activeFilter === 'fav_losing') filtered = filtered.filter(function(e) {
                //         return e.isFavLosing;
                //     });
                //     else if (activeFilter === 'prematch_drops') filtered = filtered.filter(function(e) {
                //         return e.status === 'UP' && e.delta.startsWith('-');
                //     });
                //     else if (activeFilter === 'market_shock') filtered = filtered.filter(function(e) {
                //         return e.volatility === 'high' && e.status === 'LIVE';
                //     });
                //     else if (activeFilter === 'fav_not_winning') filtered = filtered.filter(function(e) {
                //         return e.status === 'LIVE' && (e.momentum === 'lay' || e.momentum === 'strong_lay');
                //     });

                //     // Market filter (only for football)
                //     if (activeSport === 'football' && activeMarketFilter !== 'active') {
                //         filtered = filtered.filter(function(e) {
                //             return e.market === activeMarketFilter;
                //         });
                //     }

                //     renderEvents(filtered);
                // }

                // document.querySelectorAll('.filter-pill').forEach(function(pill) {
                //     pill.addEventListener('click', function() {
                //         document.querySelectorAll('.filter-pill').forEach(function(p) {
                //             p.classList.remove('active');
                //         });
                //         pill.classList.add('active');
                //         activeFilter = pill.dataset.filter;
                //         applyFilters();
                //         if (typeof ym !== 'undefined') ym(109087271, 'reachGoal',
                //         'market_activity_filter', {
                //             filter: activeFilter
                //         });
                //     });
                // });

                // document.querySelectorAll('.sport-pill').forEach(function(pill) {
                //     pill.addEventListener('click', function() {
                //         document.querySelectorAll('.sport-pill').forEach(function(p) {
                //             p.classList.remove('active');
                //         });
                //         pill.classList.add('active');
                //         activeSport = pill.dataset.sport;

                //         // Show/hide market container (only for football)
                //         if (activeSport === 'football') {
                //             marketContainer.hidden = false;
                //         } else {
                //             marketContainer.hidden = true;
                //             activeMarketFilter = 'active';
                //             marketSelect.value = 'active';
                //         }

                //         applyFilters();
                //         if (typeof ym !== 'undefined') ym(109087271, 'reachGoal', 'market_activity_sport', {
                //             sport: activeSport
                //         });
                //     });
                // });

                // ===== MARKET SELECT =====
                // marketSelect.addEventListener('change', function() {
                //     activeMarketFilter = marketSelect.value;
                //     applyFilters();
                //     if (typeof ym !== 'undefined') {
                //         ym(109087271, 'reachGoal', 'market_activity_market_change', {
                //             market: activeMarketFilter
                //         });
                //     }
                // });

                // ===== SPEED SELECTOR =====
                // function scheduleSimLoop() {
                //     if (SIM.timerId) clearTimeout(SIM.timerId);
                //     const delay = SIM.isProMode ? randInt(140, 177) : SIM.tickInterval;
                //     SIM.timerId = setTimeout(function() {
                //         simulateTick();
                //         scheduleSimLoop();
                //     }, delay);
                // }

                // document.querySelectorAll('.speed-pill').forEach(function(pill) {
                //     pill.addEventListener('click', function() {
                //         document.querySelectorAll('.speed-pill').forEach(function(p) {
                //             p.classList.remove('active');
                //         });
                //         pill.classList.add('active');

                //         const sp = parseInt(pill.dataset.speed);
                //         if (sp === 140) {
                //             SIM.isProMode = true;
                //             SIM.tickInterval = 0;
                //         } else {
                //             SIM.isProMode = false;
                //             SIM.tickInterval = sp;
                //         }

                //         scheduleSimLoop();

                //         if (typeof ym !== 'undefined') {
                //             ym(109087271, 'reachGoal', 'market_activity_speed_change', {
                //                 speed: pill.textContent.trim()
                //             });
                //         }
                //     });
                // });

                // ===== INIT =====
                // scheduleSimLoop();
            })();
        </script>
    @endpush
@endsection
