@extends('users.layout.main')
@section('title', 'Cricket Market Activity · BF Markets')
@section('content')
    @include('users.layout.sports')
    @include('users.sports.cricket.cricket_filter')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-xl font-extrabold text-[#12324a] dark:text-[#e8edf2]">🏏 {{ __('Cricket Market Activity') }}</h1>
        <label class="flex items-center gap-2 text-sm font-semibold text-[#2a4d66]">{{ __('Market:') }}
            <select id="marketDropdown" class="max-w-[260px] rounded-lg border border-[#d4e0ec] bg-[#fafdff] p-2 dark:border-[#3a5568] dark:bg-[#1f3444] dark:text-[#e8edf2]">
                <option value="">{{ __('Active Markets') }}</option>
                @foreach ($getAllMarkets['options'] as $option)<option value="{{ $option['value'] }}">{{ $option['label'] }}</option>@endforeach
            </select>
        </label>
    </div>
    <section id="cricketDashboard" data-login-url="{{ route('login') }}" data-favorite-url="{{ route('favorites.toggle') }}" data-source-url="{{ route('cricket.market-activity') }}" data-source-method="GET" data-source-kind="markets" class="min-w-0 rounded-2xl border border-[#dbe7f2] bg-white p-3 dark:border-[#2a3f50] dark:bg-[#1a2a38]">
        <div class="overflow-x-auto [scrollbar-width:thin]">
            <table class="w-full min-w-[1100px] border-collapse text-left text-sm text-[#2a4d66] dark:text-[#e8edf2]">
                <thead class="bg-gradient-to-r from-[#0b2a40] to-[#1a6b9c] text-xs text-white"><tr>
                    @foreach (['TIME', 'COMP', 'MATCH', 'MARKET', 'FAV', 'BACK', 'SIZE', 'LAY', 'SIZE', 'ACTION'] as $heading)<th class="whitespace-nowrap px-3 py-4">{{ $heading }}</th>@endforeach
                </tr></thead>
                <tbody data-cricket-market-rows>
                @fragment('cricketMarketRows')
                    @php $checkAuthAndSubscription = checkAuthAndSubscription(); @endphp
                    @forelse ($marketActivites as $row)
                        @php
                            $favorite = $row['favorite'] ?? [];
                            $marketPayload = ['title' => $row['marketName'], 'team1' => $row['team1'], 'team2' => $row['team2'], 'score' => $row['score'], 'status' => $row['status'], 'minutes' => $row['minutes'], 'runners' => $checkAuthAndSubscription['display_odds'] ? $row['runners'] : [], 'locked' => ! $checkAuthAndSubscription['display_odds']];
                        @endphp
                        <tr data-match-id="{{ $row['match_id'] }}" data-market-id="{{ $row['id'] }}" data-cricket-market="{{ json_encode($marketPayload) }}" data-cricket-match-url="{{ route('cricket.matches.show', $row['match_id']) }}" class="border-b border-[#edf2f8] bg-white hover:bg-[#f5f9fd] dark:border-[#2a3f50] dark:bg-[#1a2a38] dark:hover:bg-[#203746]">
                            <td class="whitespace-nowrap px-3 py-4"><span class="font-bold {{ $row['status'] === 'Live' ? 'text-red-500' : 'text-[#5a7d99]' }}">{{ $row['status'] === 'Live' ? '● LIVE' : $row['status'] }}</span> @if ($row['status'] !== 'Finished')<span class="ml-1 text-xs">{{ $row['minutes'] }}</span>@endif</td>
                            <td class="px-3 py-4">{{ $row['tournament'] }}</td>
                            <td class="px-3 py-4"><button type="button" data-action="open-match" class="text-left font-bold">{{ $row['team1'] }} vs {{ $row['team2'] }}</button><small class="block">{{ $row['score'] }}</small></td>
                            <td class="px-3 py-4"><span class="inline-block rounded-full border border-[#b9d7f3] bg-[#e2f0fc] px-3 py-1 font-semibold text-[#1a6b9c] dark:bg-[#25435c] dark:text-[#b0c8dd]">{{ $row['marketName'] }}</span></td>
                            <td class="px-3 py-4 font-semibold"><span class="text-amber-500">☆</span> {{ $favorite['runner'] ?? '—' }}</td>
                            @foreach (['currentBackOdds', 'backSize', 'layOdds', 'laySize'] as $field)
                                <td class="whitespace-nowrap px-3 py-4"><span @if ($field === 'currentBackOdds' && $checkAuthAndSubscription['display_odds'] && (float) ($favorite[$field] ?? 0) > 0) tabindex="0" data-cricket-odds-tooltip="{{ number_format($favorite['initialBackOdds'] ?? $favorite['previousBackOdds'] ?? $favorite[$field], 2) }} {{ (float) $favorite[$field] > (float) ($favorite['initialBackOdds'] ?? $favorite['previousBackOdds'] ?? $favorite[$field]) ? '<<' : '>>' }} {{ number_format($favorite[$field], 2) }}" @endif class="inline-flex min-w-16 justify-center rounded-md px-2.5 py-2 font-bold {{ in_array($field, ['currentBackOdds', 'backSize']) ? 'bg-[#d5edff] text-[#1a6b9c] dark:bg-[#25435c] dark:text-[#b0c8dd]' : 'bg-[#ffe3eb] text-[#bc3d60] dark:bg-[#4a2633] dark:text-[#ffb7cb]' }}">
                                    @if ($checkAuthAndSubscription['display_odds']){{ (float) ($favorite[$field] ?? 0) > 0 ? number_format($favorite[$field], 2) : '—' }}@else @include('lock_icon') @endif
                                </span></td>
                            @endforeach
                            <td class="px-3 py-4">@if ($checkAuthAndSubscription['display_odds'])<button type="button" data-action="market" class="whitespace-nowrap rounded-full bg-[#0b2a40] px-4 py-2 font-semibold text-white dark:bg-[#4a8ab5]">{{ __('Open') }} →</button>@else @include('lock_icon') @endif</td>
                        </tr>
                    @empty
                        @if (! request()->boolean('append'))<tr data-cricket-empty><td colspan="10" class="py-10 text-center">{{ __('No data available.') }}</td></tr>@endif
                    @endforelse
                @endfragment
                </tbody>
            </table>
        </div>
        <div class="flex justify-center py-8 text-sm font-semibold text-slate-300 {{ $hasMore ? '' : 'hidden' }}" data-cricket-lazy-loader data-has-more="{{ $hasMore ? 'true' : 'false' }}" data-next-page="{{ $nextPage }}"><span data-lazy-loader-text>↓ {{ __('Scroll for more matches') }} ↓</span></div>
        <div id="cricketModal" class="fixed inset-0 z-[10000] hidden items-center justify-center bg-black/60 p-4" role="dialog" aria-modal="true" aria-labelledby="cricketModalTitle"><div class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-2xl bg-white p-5 text-[#12324a] shadow-xl dark:bg-[#1a2a38] dark:text-[#e8edf2]" data-cricket-modal-panel></div></div>
    </section>
    @include('popup')
@endsection
@push('js')
    @vite('resources/users/js/sports/cricket.js')
@endpush
