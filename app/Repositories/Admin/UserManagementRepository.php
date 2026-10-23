<?php

namespace App\Repositories\Admin;

use App\Models\Customer;
use App\Models\CustomerLoginSession;
use App\Models\CustomerSubscriptions;
use App\Models\Subscriptions;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class UserManagementRepository
{
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        return Customer::query()
            ->select('customers.*')
            ->selectSub(
                CustomerLoginSession::query()
                    ->selectRaw('CASE WHEN COUNT(*) > 0 THEN 1 ELSE 0 END')
                    ->whereColumn('customer_id', 'customers.id')
                    ->whereColumn('session_id', 'customers.active_session_id')
                    ->whereNull('logged_out_at'),
                'is_online'
            )
            ->with([
                'displaySubscription.subscriptionPlan',
            ])
            ->when(! empty($filters['search']), function (Builder $query) use ($filters): void {
                $search = trim((string) $filters['search']);

                $query->where(function (Builder $innerQuery) use ($search): void {
                    $innerQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('surname', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%")
                        ->orWhere('country', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->paginate(max(1, min(100, (int) ($filters['per_page'] ?? 10))))
            ->withQueryString();
    }

    public function findById(Customer $customer): array
    {
        $customer = Customer::query()
            ->select('customers.*')
            ->selectSub(
                CustomerLoginSession::query()
                    ->selectRaw('CASE WHEN COUNT(*) > 0 THEN 1 ELSE 0 END')
                    ->whereColumn('customer_id', 'customers.id')
                    ->whereColumn('session_id', 'customers.active_session_id')
                    ->whereNull('logged_out_at'),
                'is_online'
            )
            ->with([
                'subscriptions.subscriptionPlan',
                'displaySubscription.subscriptionPlan',
            ])
            ->findOrFail($customer->id);

        $currentSubscription = $customer->subscriptions()
            ->with('subscriptionPlan')
            ->where(function (Builder $query): void {
                $query
                    ->whereIn('status', ['active', 'pending'])
                    ->orWhere(function (Builder $innerQuery): void {
                        $innerQuery
                            ->where('status', 'cancelled')
                            ->whereHas('customer', fn ($query) => $query->where('subscription_ends_at', '>=', now()));
                    });
            })
            ->orderByRaw("CASE WHEN status IN ('active', 'pending') THEN 0 ELSE 1 END")
            ->orderByDesc('id')
            ->orderByDesc('id')
            ->first();

        $plans = Subscriptions::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();

        $loginHistory = CustomerLoginSession::query()
            ->where('customer_id', $customer->id)
            ->latest('logged_in_at')
            ->latest('id')
            ->paginate(10, ['*'], 'history_page')
            ->withQueryString();

        return [
            'customer' => $customer,
            'currentSubscription' => $currentSubscription,
            'plans' => $plans,
            'loginHistory' => $loginHistory,
        ];
    }

    public function create(array $data): Customer
    {
        $customer = new Customer;
        $customer->forceFill(Arr::only($data, ['name', 'surname', 'username', 'email', 'password', 'mobile', 'mobile_code', 'country', 'country_code']));
        $customer->forceFill(['is_verified' => true, 'verification_via' => 'admin', 'admin_free_days' => 0])->save();
        if (filled($data['subscription_id'] ?? null) || (int) ($data['admin_free_days'] ?? 0) !== 0 || (int) ($data['add_active_days'] ?? 0) > 0) {
            $this->updateSubscription($customer, $data + ['subscription_status' => 'active']);
        }

        return $customer->fresh();
    }

    public function update(Customer $customer, array $data): Customer
    {
        $section = (string) ($data['section'] ?? 'profile');

        if ($section === 'subscription') {
            $this->updateSubscription($customer, $data);

            return $customer->fresh([
                'subscriptions.subscriptionPlan',
                'displaySubscription.subscriptionPlan',
            ]);
        }

        $customer->forceFill(Arr::only($data, [
            'name',
            'surname',
            'username',
            'email',
            'mobile',
            'country',
            'is_blocked',
            'is_suspended',
            'trial_ends_at',
        ]));

        $customer->save();

        return $customer->fresh([
            'subscriptions.subscriptionPlan',
            'displaySubscription.subscriptionPlan',
        ]);
    }

    public function totalUsers(): int
    {
        return Customer::query()->count();
    }

    public function onlineUsers(): int
    {
        return Customer::query()->whereHas('loginSessions', function (Builder $query): void {
            $query->whereColumn('session_id', 'customers.active_session_id')->whereNull('logged_out_at');
        })->count();
    }

    public function restrictedUsers(): int
    {
        return Customer::query()
            ->where(function (Builder $query): void {
                $query->where('is_blocked', 1)
                    ->orWhere('is_suspended', 1);
            })
            ->count();
    }

    public function expiringSoonUsers(int $days = 7): int
    {
        return Customer::query()
            ->where(function (Builder $query): void {
                $query->whereNull('admin_free_days')->orWhere('admin_free_days', '!=', '-1');
            })
            ->where(function (Builder $query) use ($days): void {
                $query->whereBetween('subscription_ends_at', [now(), now()->copy()->addDays($days)])
                    ->orWhereBetween('trial_ends_at', [now(), now()->copy()->addDays($days)]);
            })
            ->count();
    }

    public function getStats(): array
    {
        return [
            'totalUsers' => $this->totalUsers(),
            'onlineUsers' => $this->onlineUsers(),
            'restrictedUsers' => $this->restrictedUsers(),
            'expiringSoonUsers' => $this->expiringSoonUsers(),
        ];
    }

    private function updateSubscription(Customer $customer, array $data): void
    {
        $customer = Customer::query()->lockForUpdate()->findOrFail($customer->id);
        $plan = filled($data['subscription_id'] ?? null)
            ? Subscriptions::query()->where('is_active', true)->findOrFail((int) $data['subscription_id']) : null;
        $status = (string) ($data['subscription_status'] ?? 'active');
        $freeDays = (int) ($data['admin_free_days'] ?? 0);
        $additionalDays = (int) ($data['add_active_days'] ?? 0);
        $lifetime = $freeDays === -1;
        $grantsAccess = in_array($status, ['active', 'cancelled'], true);

        if (($freeDays !== 0 || $additionalDays > 0) && ! $grantsAccess) {
            throw ValidationException::withMessages(['subscription_status' => 'Free or lifetime access requires an active subscription status.']);
        }
        if ($grantsAccess && $customer->subscriptions()->where('gateway', '!=', 'admin')
            ->where(function (Builder $query): void {
                $query->where('auto_renew', true)->orWhere('status', 'pending')->orWhere('status', 'cancellation_pending');
            })->whereNotIn('status', ['failed', 'expired', 'cancelled'])->exists()) {
            throw ValidationException::withMessages(['subscription_id' => 'Disable paid AutoPay and resolve pending checkouts before granting free or lifetime access. Existing provider billing has not been changed.']);
        }
        if ($customer->hasLifetimeAccess() && ! $lifetime) {
            throw ValidationException::withMessages(['admin_free_days' => 'This customer already has Lifetime Access. No extension is needed.']);
        }
        $startsAt = filled($data['subscription_starts_at'] ?? null) ? Carbon::parse($data['subscription_starts_at'], config('app.timezone'))->timezone(config('app.timezone'))
            : ($customer->hasActiveSubscription() ? ($customer->subscription_starts_at ?? now()) : now());
        $endsAt = null;
        if ($grantsAccess && ! $lifetime) {
            $days = $freeDays > 0 ? $freeDays : $additionalDays;
            $base = $customer->subscription_ends_at?->isFuture() ? $customer->subscription_ends_at->copy() : now();
            if ($startsAt->gt($base)) {
                $base = $startsAt->copy();
            }
            $explicitEnd = filled($data['subscription_ends_at'] ?? null) ? Carbon::parse($data['subscription_ends_at'], config('app.timezone'))->timezone(config('app.timezone')) : null;
            $endsAt = $days > 0 ? $base->addDays($days) : ($explicitEnd ?? $base->addDays((int) ($plan?->duration_days ?? 0)));
            if ($endsAt->lte($startsAt) || $endsAt->lte(now())) {
                throw ValidationException::withMessages(['subscription_ends_at' => 'The access end date must be after the start date and the current time.']);
            }
            if ($customer->hasActiveSubscription() && ($endsAt->lt($customer->subscription_ends_at) || $startsAt->gt(now()))) {
                throw ValidationException::withMessages(['subscription_ends_at' => 'Existing valid access cannot be shortened or interrupted.']);
            }
        }
        if ($plan !== null) {
            $record = $customer->subscriptions()->where('gateway', 'admin')->latest('id')->first();
            $payload = ['customer_id' => $customer->id, 'subscription_id' => $plan->id, 'amount' => 0,
                'currency' => $customer->country_code === 'GE' ? 'GEL' : 'EUR', 'gateway' => 'admin',
                'status' => $status, 'auto_renew' => false, 'duration_days' => $plan->duration_days];
            if ($record !== null) {
                $record->update($payload);
            } else {
                CustomerSubscriptions::query()->create($payload);
            }
        }
        if (! $grantsAccess && $customer->hasActiveSubscription()) {
            return;
        }
        $customer->forceFill([
            'subscription_starts_at' => $grantsAccess ? $startsAt : null,
            'subscription_ends_at' => $endsAt,
            'subscription_plan' => $grantsAccess ? ($plan?->label ?? ($lifetime ? 'Lifetime Access' : ($customer->subscription_plan ?? 'Admin Granted'))) : null,
            'admin_free_days' => $lifetime ? -1 : ($grantsAccess ? max(0, (int) $customer->admin_free_days) + max(0, $freeDays) + $additionalDays : 0),
        ])->save();
    }
}
