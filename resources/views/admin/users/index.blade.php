@extends('admin.layouts.main', ['active' => 'users'])

@section('title', 'User Management | Admin')

@section('content')
    <div class="min-h-screen px-4 py-6 sm:px-6 lg:px-8 theme-bg-base">
        <div class="mx-auto max-w-7xl">
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.22em] theme-text-primary">
                        Admin Panel
                    </p>
                    <h1 class="mt-2 text-3xl font-bold theme-text-main">
                        User Management
                    </h1>
                    <p class="mt-2 text-sm theme-text-muted">
                        Review customer access, login activity, subscription status, and free active-day adjustments.
                    </p>
                </div>
            </div>

            <a href="{{ route('admin.users.create') }}" class="mb-5 inline-flex rounded-xl px-5 py-3 font-semibold text-white betguru-gradient-cta">Add Customer</a>
            @if (session('success'))
                <div class="mb-5 rounded-2xl px-4 py-3 text-sm font-semibold theme-chip-success">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-5 rounded-2xl px-4 py-3 text-sm font-semibold theme-chip-danger">
                    {{ session('error') }}
                </div>
            @endif

            <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-3xl p-5 betguru-card-elevated">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] theme-text-muted">
                        Total Users
                    </p>
                    <p class="mt-3 text-2xl font-bold theme-text-main">{{ $getState['totalUsers'] ?? 0 }}</p>
                </div>

                <div class="rounded-3xl p-5 betguru-card-elevated">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] theme-text-muted">
                        Online Users
                    </p>
                    <p class="mt-3 text-2xl font-bold theme-text-success">{{ $getState['onlineUsers'] ?? 0 }}</p>
                </div>

                <div class="rounded-3xl p-5 betguru-card-elevated">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] theme-text-muted">
                        Restricted Users
                    </p>
                    <p class="mt-3 text-2xl font-bold theme-text-danger">{{ $getState['restrictedUsers'] ?? 0 }}</p>
                </div>

                <div class="rounded-3xl p-5 betguru-card-elevated">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] theme-text-muted">
                        Expiring Soon
                    </p>
                    <p class="mt-3 text-2xl font-bold theme-text-main">{{ $getState['expiringSoonUsers'] ?? 0 }}</p>
                </div>
            </div>

            <div class="overflow-hidden rounded-[2rem] betguru-card">
                <form method="GET" action="{{ route('admin.users') }}"
                    class="flex flex-col gap-4 border-b p-5 lg:flex-row lg:items-center lg:justify-between theme-border">

                    <div class="relative w-full lg:max-w-md">
                        <svg class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 theme-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-4.35-4.35m1.85-5.65a7.5 7.5 0 11-15 0 7.5 7.5 0 0115 0z" />
                        </svg>

                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Search by email, name, username, mobile, country..."
                            class="betguru-input betguru-pl-11">
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <button type="submit"
                            class="rounded-2xl px-5 py-3 text-sm font-semibold text-white betguru-gradient-cta-flat">
                            Filter
                        </button>

                        <a href="{{ route('admin.users') }}"
                            class="rounded-2xl px-5 py-3 text-sm font-semibold theme-panel-subtle">
                            Reset
                        </a>
                    </div>
                </form>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="theme-bg-surface-soft">
                            <tr>
                                <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-[0.16em] theme-text-muted">User</th>
                                <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-[0.16em] theme-text-muted">Mobile</th>
                                <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-[0.16em] theme-text-muted">Country</th>
                                <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-[0.16em] theme-text-muted">Subscription</th>
                                <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-[0.16em] theme-text-muted">Trial End</th>
                                <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-[0.16em] theme-text-muted">Last Login</th>
                                <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-[0.16em] theme-text-muted">Login Count</th>
                                <th class="px-5 py-4 text-right text-xs font-bold uppercase tracking-[0.16em] theme-text-muted">Action</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            @forelse ($customers as $customer)
                                @php
                                    $displaySubscription = $customer->getRelation('displaySubscription');
                                    $trialEndsAt = filled($customer->trial_ends_at)
                                        ? \Illuminate\Support\Carbon::parse($customer->trial_ends_at)
                                        : null;
                                    $lastLoginAt = filled($customer->last_login_at)
                                        ? \Illuminate\Support\Carbon::parse($customer->last_login_at)
                                        : null;
                                    $isOnline = (int) ($customer->is_online ?? 0) === 1;
                                    $subscriptionLabel = 'No Active Plan';
                                    $subscriptionClass = 'theme-chip-danger-strong';

                                    if ($displaySubscription) {
                                        if ((string) $displaySubscription->status === 'cancelled' && $customer->subscription_ends_at && $customer->subscription_ends_at->isFuture()) {
                                            $subscriptionLabel = 'Access Active';
                                            $subscriptionClass = 'theme-chip-primary';
                                        } else {
                                            $subscriptionLabel = ucfirst(str_replace('_', ' ', (string) $displaySubscription->status));
                                            $subscriptionClass = in_array((string) $displaySubscription->status, ['active', 'pending'], true)
                                                ? 'theme-chip-success'
                                                : 'theme-chip-danger-strong';
                                        }
                                    } elseif ($trialEndsAt && $trialEndsAt->isFuture()) {
                                        $subscriptionLabel = 'Trial';
                                        $subscriptionClass = 'theme-chip-accent';
                                    }
                                @endphp

                                <tr class="transition hover:bg-slate-50">
                                    <td class="px-5 py-5">
                                        <div class="flex items-center gap-3">
                                            @if ($customer->avatar_path)
                                                <img src="{{ asset('storage/' . $customer->avatar_path) }}"
                                                    alt="{{ $customer->username }}"
                                                    class="h-11 w-11 rounded-full object-cover">
                                            @else
                                                <div class="flex h-11 w-11 items-center justify-center rounded-full text-xs font-bold text-white betguru-gradient-badge">
                                                    {{ strtoupper(substr($customer->name ?? 'U', 0, 1) . substr($customer->surname ?? '', 0, 1)) ?: strtoupper(substr($customer->username ?? 'U', 0, 2)) }}
                                                </div>
                                            @endif

                                            <div class="min-w-0">
                                                <div class="flex items-center gap-2">
                                                    <p class="truncate font-semibold theme-text-main">
                                                        #{{ $customer->id }} {{ $customer->name }} {{ $customer->surname }}
                                                    </p>

                                                    @if ($isOnline)
                                                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold theme-chip-success">
                                                            Online
                                                        </span>
                                                    @endif

                                                    @if ((int) $customer->is_verified === 1)
                                                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold theme-chip-success">
                                                            Verified
                                                        </span>
                                                    @else
                                                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold theme-chip-accent">
                                                            Unverified
                                                        </span>
                                                    @endif

                                                    @if ($customer->is_blocked)
                                                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold theme-chip-danger-strong">
                                                            Blocked
                                                        </span>
                                                    @endif

                                                    @if ($customer->is_suspended)
                                                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold theme-chip-accent">
                                                            Suspended
                                                        </span>
                                                    @endif
                                                </div>

                                                <p class="mt-1 truncate text-sm theme-text-muted">
                                                    {{ $customer->username }} • {{ $customer->email }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-5 py-5 text-sm theme-text-soft">
                                        {{ $customer->mobile ?: '—' }}
                                    </td>

                                    <td class="px-5 py-5 text-sm theme-text-soft">
                                        {{ $customer->country ?: '—' }}
                                    </td>

                                    <td class="px-5 py-5">
                                        <div class="space-y-2">
                                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $subscriptionClass }}">
                                                {{ $customer->hasLifetimeAccess() ? 'Lifetime Access' : ($customer->hasActiveSubscription() ? 'Active' : ($customer->hasActiveTrial() ? 'Trial' : 'Expired')) }}
                                            </span>

                                            <p class="text-xs theme-text-muted">{{ $customer->subscription_plan ?? 'No plan' }} · {{ $customer->hasLifetimeAccess() || $customer->admin_free_days > 0 || $displaySubscription?->gateway === 'admin' ? 'Admin Granted' : ($displaySubscription ? 'Paid' : 'None') }}<br>Admin free days: {{ (int) $customer->admin_free_days }}<br>Starts: {{ $customer->subscription_starts_at?->format('d M Y H:i') ?? '—' }}<br>Expires: {{ $customer->hasLifetimeAccess() ? 'Lifetime Access' : ($customer->subscription_ends_at?->format('d M Y H:i') ?? '—') }}</p>
                                            @if ($displaySubscription)
                                                <p class="text-xs theme-text-muted">
                                                    {{ $displaySubscription->subscriptionPlan?->label ?? 'Plan' }}
                                                    @if ($customer->subscription_ends_at)
                                                        • Ends {{ $customer->subscription_ends_at->format('d M Y') }}
                                                    @endif
                                                </p>
                                            @endif
                                        </div>
                                    </td>

                                    <td class="px-5 py-5 text-sm theme-text-soft">
                                        {{ $trialEndsAt ? $trialEndsAt->format('d M Y h:i A') : '—' }}
                                    </td>

                                    <td class="px-5 py-5 text-sm theme-text-soft">
                                        {{ $lastLoginAt ? $lastLoginAt->format('d M Y h:i A') : '—' }}
                                    </td>

                                    <td class="px-5 py-5 text-sm theme-text-soft">
                                        {{ (int) $customer->login_count }}
                                    </td>

                                    <td class="px-5 py-5 text-right">
                                        <a href="{{ route('admin.users.edit', $customer) }}"
                                            class="inline-flex rounded-xl px-4 py-2 text-xs font-semibold theme-panel-subtle">
                                            Edit / Activate / Extend
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-5 py-10 text-center text-sm theme-text-muted">
                                        No users found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    @include('admin.components.datatable-pagination', [
                        'paginator' => $customers,
                        'perPageOptions' => [5, 10, 15, 25, 50],
                        'defaultPerPage' => 10,
                    ])
                </div>
            </div>
        </div>
    </div>
@endsection
