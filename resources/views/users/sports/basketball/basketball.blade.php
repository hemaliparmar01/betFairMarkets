@extends('users.layout.main')
@section('title', 'Basketball Fixtures · BF Markets')
@section('content')
    @include('users.layout.sports')
    @include('users.sports.basketball.basketball_filter')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-xl font-extrabold text-[#12324a] dark:text-[var(--color-text-dark)]">🏀 {{ __('Basketball Fixtures') }}</h1>
        <label class="flex items-center gap-2 text-sm font-semibold text-[#2a4d66]">{{ __('Market:') }}
            <select id="marketDropdown" class="market-dropdown max-w-[260px] rounded-lg border border-[var(--color-border-control)] bg-[#fafdff] p-2 dark:border-[#3a5568] dark:bg-[var(--color-surface-raised-dark)] dark:text-[var(--color-text-dark)]">
                @foreach ($getAllMarkets['options'] as $option)
                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                @endforeach
            </select>
        </label>
    </div>
    <section id="basketballDashboard" data-login-url="{{ route('login') }}" data-favorite-url="{{ route('favorites.toggle') }}" data-source-url="{{ route('basketball.filter-basketball') }}" data-source-method="POST" data-source-kind="fixtures" class="min-w-0 rounded-2xl border border-[#dbe7f2] bg-white p-3 dark:border-[var(--color-border-dark)] dark:bg-[var(--color-surface-dark)]">
        @include('users.sports.basketball.basketballDetails', ['data' => $data])
        <div class="flex justify-center py-8 text-sm font-semibold text-slate-300 {{ $hasMore ? '' : 'hidden' }}" data-basketball-lazy-loader data-has-more="{{ $hasMore ? 'true' : 'false' }}" data-next-page="{{ $nextPage }}">
            <span data-lazy-loader-text>↓ {{ __('Scroll for more matches') }} ↓</span>
        </div>
    </section>
    @include('popup')
@endsection
@push('js')
    @vite('resources/users/js/sports/basketball.js')
@endpush
