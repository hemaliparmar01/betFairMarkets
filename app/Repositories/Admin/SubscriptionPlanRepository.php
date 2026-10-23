<?php

namespace App\Repositories\Admin;

use App\Models\Subscriptions;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class SubscriptionPlanRepository
{
    public function create(array $data): Subscriptions
    {
        return Subscriptions::query()->create($data);
    }

    public function update(Subscriptions $subscriptionPlan, array $data): Subscriptions
    {
        $subscriptionPlan->update($data);

        return $subscriptionPlan->refresh();
    }

    public function delete(Subscriptions $subscription): bool
    {
        return (bool) $subscription->delete();
    }

    public function getNextSortOrder(string $label): int
    {
        $maxSortOrder = Subscriptions::query()
            ->where('label', $label)
            ->max('sort_order');

        return ((int) $maxSortOrder) + 1;
    }

    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 10)));

        $query = Subscriptions::query();

        if (! empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function (Builder $query) use ($search): void {
                $query->where('label', 'like', "%{$search}%")
                    ->orWhere('label_translations', 'like', "%{$search}%")
                    ->orWhere('price_for_local', 'like', "%{$search}%")
                    ->orWhere('price_for_international', 'like', "%{$search}%")
                    ->orWhere('duration_days', 'like', "%{$search}%");
            });
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('is_active', $filters['status']);
        }

        return $query
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }
}
