@extends('users.layout.main')
@section('title', 'Greyhounds Fixtures · BF Markets')
@section('content')
    @include('users.layout.sports')
    @include('users.sports.greyhounds.greyhounds_filter')
    <h1 class="mb-4 text-xl font-extrabold text-[#12324a] dark:text-[#e8edf2]">🐕 {{ __('Greyhounds Fixtures') }}</h1>
    <section id="greyhoundsDashboard" data-login-url="{{ route('login') }}" data-favorite-url="{{ route('favorites.toggle') }}" data-source-url="{{ route('greyhounds.filter-greyhounds') }}" data-source-method="POST" data-source-kind="fixtures" class="min-w-0 rounded-2xl border border-[#dbe7f2] bg-white p-3 dark:border-[#2a3f50] dark:bg-[#1a2a38]">
        @include('users.sports.greyhounds.greyhoundsDetails')
        <div class="flex justify-center py-8 text-sm font-semibold text-slate-300 {{ $hasMore ? '' : 'hidden' }}" data-greyhounds-lazy-loader data-has-more="{{ $hasMore ? 'true' : 'false' }}" data-next-page="{{ $nextPage }}"><span data-lazy-loader-text>↓ {{ __('Scroll for more races') }} ↓</span></div>
    </section>
    @include('popup')
@endsection
@push('js')
    @vite('resources/users/js/sports/greyhounds.js')
@endpush
