@extends('admin.layouts.main', ['active' => 'subscriptions'])

@section('title', 'Subscription Plans | Admin')


@section('content')
    <div class="min-h-screen px-4 py-6 sm:px-6 lg:px-8 theme-bg-base">
        <div class="mx-auto max-w-7xl">

            {{-- Page Header --}}
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.22em] theme-text-primary">
                        Admin Panel
                    </p>
                    <h1 class="mt-2 text-3xl font-bold theme-text-main">
                        Subscription Plans
                    </h1>
                    <p class="mt-2 text-sm theme-text-muted">
                        Manage BetFair Markets pricing plans for EUR and GEL users.
                    </p>
                </div>

                <a href="{{ url('admin/subscriptions/create') }}"
                    class="inline-flex items-center justify-center rounded-2xl px-5 py-3 text-sm font-semibold text-white transition hover:-translate-y-0.5 betguru-gradient-cta">
                    + Add Plan
                </a>
            </div>

            {{-- Stats Cards --}}
            <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-3xl p-5 betguru-card-elevated">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] theme-text-muted">Total
                        Plans</p>
                    <p class="mt-3 text-2xl font-bold theme-text-main">{{ $plans->count() }}</p>
                </div>

                <div class="rounded-3xl p-5 betguru-card-elevated">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] theme-text-muted">
                        Active Plans</p>
                    <p class="mt-3 text-2xl font-bold theme-text-success">
                        {{ $plans->where('is_active', true)->count() }}
                    </p>
                </div>
            </div>

            {{-- Table Card --}}
            <div x-data="subscriptionPlanTable()" class="overflow-hidden rounded-[2rem] betguru-card">
                {{-- Filters --}}
                <form method="GET" action="{{ url('admin/subscriptions') }}"
                    class="flex flex-col gap-4 border-b p-5 lg:flex-row lg:items-center lg:justify-between theme-border">

                    <div class="relative w-full lg:max-w-md">
                        <svg class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 theme-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-4.35-4.35m1.85-5.65a7.5 7.5 0 11-15 0 7.5 7.5 0 0115 0z" />
                        </svg>

                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search plans..."
                            class="betguru-input betguru-pl-11">
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <select name="status" class="betguru-select min-w-[150px]">
                            <option value="">All Status</option>
                            <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactive</option>
                        </select>

                        <button type="submit" class="rounded-2xl px-5 py-3 text-sm font-semibold text-white betguru-gradient-cta-flat">
                            Filter
                        </button>

                        <a href="{{ url('admin/subscriptions') }}" class="rounded-2xl px-5 py-3 text-sm font-semibold theme-panel-subtle">
                            Reset
                        </a>
                    </div>
                </form>

                {{-- Desktop Table --}}
                <div class="overflow-x-auto lg:block">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="theme-bg-surface-soft">
                            <tr>
                                <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-[0.16em] theme-text-muted">Label</th>
                                <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-[0.16em] theme-text-muted">Duration (days)</th>
                                <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-[0.16em] theme-text-muted">Price for local</th>
                                <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-[0.16em] theme-text-muted">Price for international</th>
                                <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-[0.16em] theme-text-muted">Badge</th>
                                <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-[0.16em] theme-text-muted">Status</th>
                                <th class="px-5 py-4 text-right text-xs font-bold uppercase tracking-[0.16em] theme-text-muted">Action</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            @if ($plans->count() === 0)
                                <tr>
                                    <td colspan="6" class="px-5 py-10 text-center text-sm theme-text-muted">
                                        No subscription plans found.
                                    </td>
                                </tr>
                            @endif
                            @foreach ($plans as $plan)
                                <tr class="transition hover:bg-slate-50">
                                    {{-- Plan --}}
                                    <td class="px-5 py-5">
                                        <p class="font-semibold theme-text-main">
                                            {{ $plan->label }}
                                        </p>
                                    </td>

                                    {{-- Duration days --}}
                                    <td class="px-5 py-5">
                                        <p class="font-semibold theme-text-main">
                                            {{ $plan->duration_days }}
                                        </p>
                                    </td>

                                    {{-- Local Price --}}
                                    <td class="px-5 py-5">
                                        <div>
                                            <p class="text-lg font-bold theme-text-main">
                                                {{ number_format($plan->price_for_local, 2) }} GEL
                                            </p>
                                            <p class="text-xs theme-text-muted">
                                                Georgian users
                                            </p>
                                        </div>
                                    </td>

                                    {{-- International Price --}}
                                    <td class="px-5 py-5">
                                        <div>
                                            <p class="text-lg font-bold theme-text-main">
                                                {{ number_format($plan->price_for_international, 2) }} €
                                            </p>
                                            <p class="text-xs theme-text-muted">
                                                International users
                                            </p>
                                        </div>
                                    </td>

                                    {{-- Badge --}}
                                    <td class="px-5 py-5">
                                        @if ($plan->is_popular)
                                            <span class="rounded-full px-3 py-1 text-xs font-semibold theme-chip-accent">
                                                Popular
                                            </span>
                                        @else
                                            <span class="text-sm theme-text-muted">—</span>
                                        @endif
                                    </td>

                                    {{-- Status --}}
                                    <td class="px-5 py-5">
                                        @if ($plan->is_active)
                                            <span class="rounded-full px-3 py-1 text-xs font-semibold theme-chip-success">
                                                Active
                                            </span>
                                        @else
                                            <span class="rounded-full px-3 py-1 text-xs font-semibold theme-chip-danger-strong">
                                                Inactive
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Actions --}}
                                    <td class="px-5 py-5 text-right">
                                        <div class="inline-flex items-center gap-2">
                                            <a href="{{ route('admin.subscriptions.edit', $plan) }}"
                                                class="rounded-xl px-3 py-2 text-xs font-semibold transition theme-panel-subtle">
                                                Edit
                                            </a>

                                            <form method="POST" action="{{ route('admin.subscriptions.destroy', $plan) }}">
                                                @csrf
                                                @method('DELETE')

                                                <button type="submit"
                                                    onclick="return confirm('Are you sure you want to delete this plan?')"
                                                    class="rounded-xl px-3 py-2 text-xs font-semibold transition theme-chip-danger">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @include('admin.components.datatable-pagination', [
                        'paginator' => $plans,
                        'perPageOptions' => [5, 10, 15, 25, 50],
                        'defaultPerPage' => 10,
                    ])
                </div>

                {{-- Mobile Cards --}}
                <div class="grid gap-4 p-4 lg:hidden">
                    @foreach ($plans as $plan)
                        <div
                            class="rounded-3xl p-5 betguru-card-elevated">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-lg font-bold theme-text-main">
                                        {{ $plan->label }}</p>
                                    
                                </div>

                                @if ($plan->is_active)
                                    <span class="rounded-full px-3 py-1 text-xs font-semibold theme-chip-success">
                                        Active
                                    </span>
                                @else
                                    <span class="rounded-full px-3 py-1 text-xs font-semibold theme-chip-danger-strong">
                                        Inactive
                                    </span>
                                @endif
                            </div>

                            <div class="mt-5 grid grid-cols-2 gap-3">
                                <div class="rounded-2xl p-4 betguru-card-soft">
                                    <p class="text-xs theme-text-muted">Price</p>
                                    <p class="mt-1 font-bold theme-text-main">
                                        {{ number_format($plan->price_for_local, 2) }} GEL / {{ number_format($plan->price_for_international, 2) }} EUR
                                    </p>
                                </div>

                                <div class="rounded-2xl p-4 betguru-card-soft">
                                    <p class="text-xs theme-text-muted">Duration</p>
                                    <p class="mt-1 font-bold theme-text-main">
                                        {{ $plan->duration_days }} days
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4 flex items-center justify-between">
                                <span class="text-sm theme-text-muted">
                                    Local / International
                                </span>

                                @if ($plan->is_popular)
                                    <span class="rounded-full px-3 py-1 text-xs font-semibold theme-chip-accent">
                                        Popular
                                    </span>
                                @endif
                            </div>

                            <div class="mt-5 grid grid-cols-2 gap-3">
                                <a href="{{ route('admin.subscriptions.edit', $plan) }}"
                                    class="rounded-2xl px-4 py-3 text-center text-sm font-semibold theme-panel-subtle">
                                    Edit
                                </a>

                                <form method="POST" action="{{ route('admin.subscriptions.destroy', $plan) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        onclick="return confirm('Are you sure you want to delete this plan?')"
                                        class="w-full rounded-2xl px-4 py-3 text-sm font-semibold theme-chip-danger">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>

            </div>
        </div>
    </div>
@endsection
