@extends('users.layout.main')
@section('title', 'Ice Hockey Fixtures · BF Markets')
@section('content')
    @include('users.layout.sports')
    @include('users.sports.icehockey.icehockey_filter')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-xl font-extrabold text-[#12324a] dark:text-[#e8edf2]">🏒 {{ __('Ice Hockey Fixtures') }}</h1>
        <label class="flex items-center gap-2 text-sm font-semibold text-[#2a4d66]">{{ __('Market:') }}
            <select id="marketDropdown" class="market-dropdown max-w-[260px] rounded-lg border border-[#d4e0ec] bg-[#fafdff] p-2 dark:border-[#3a5568] dark:bg-[#1f3444] dark:text-[#e8edf2]">
                @foreach ($getAllMarkets['options'] as $option)
                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                @endforeach
            </select>
        </label>
    </div>
    <section id="icehockeyDashboard" data-login-url="{{ route('login') }}" data-favorite-url="{{ route('favorites.toggle') }}" data-source-url="{{ route('icehockey.filter-icehockey') }}" data-source-method="POST" data-source-kind="fixtures" class="min-w-0 rounded-2xl border border-[#dbe7f2] bg-white p-3 dark:border-[#2a3f50] dark:bg-[#1a2a38]">
        @include('users.sports.icehockey.icehockeyDetails', ['data' => $data])
        <div class="flex justify-center py-8 text-sm font-semibold text-slate-300 {{ $hasMore ? '' : 'hidden' }}" data-icehockey-lazy-loader data-has-more="{{ $hasMore ? 'true' : 'false' }}" data-next-page="{{ $nextPage }}">
            <span data-lazy-loader-text>↓ {{ __('Scroll for more matches') }} ↓</span>
        </div>
    </section>
    @include('popup')
@endsection
@push('js')
    @vite('resources/users/js/sports/icehockey.js')
@endpush
