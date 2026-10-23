<?php

namespace App\Services\Admin;

use App\Models\Subscriptions;
use App\Repositories\Admin\SubscriptionPlanRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SubscriptionPlanService
{
    public function __construct(
        private readonly SubscriptionPlanRepository $subscriptionPlanRepository
    ) {}

    public function getAll(array $filters = []): LengthAwarePaginator
    {
        return $this->subscriptionPlanRepository->getAll($filters);
    }

    public function create(array $data): Subscriptions
    {
        return DB::transaction(function () use ($data): Subscriptions {
            if (isset($data['icon'])) {
                $data['icon'] = $data['icon']->store('subscriptions', 'public');
            }
            $data['description_translations'] = json_encode($data['description_translations'] ?? []);
            $data['saving_label_translations'] = json_encode($data['saving_label_translations'] ?? []);
            $data['button_text_translations'] = json_encode($data['button_text_translations'] ?? []);
            $data['price_label_translations'] = json_encode($data['price_label_translations'] ?? []);
            $data['label_translations'] = $data['label_translations'] ?? [];
            $data['features'] = json_encode($data['features']);
            $data['is_popular'] = filter_var($data['is_popular'] ?? false, FILTER_VALIDATE_BOOLEAN);

            return $this->subscriptionPlanRepository->create($data);
        });
    }

    public function update(Subscriptions $subscription, array $data): Subscriptions
    {
        return DB::transaction(function () use ($subscription, $data): Subscriptions {
            if (isset($data['icon'])) {
                $data['icon'] = $data['icon']->store('subscriptions', 'public');
            } else {
                unset($data['icon']);
            }
            $data['description_translations'] = json_encode($data['description_translations'] ?? []);
            $data['saving_label_translations'] = json_encode($data['saving_label_translations'] ?? []);
            $data['button_text_translations'] = json_encode($data['button_text_translations'] ?? []);
            $data['price_label_translations'] = json_encode($data['price_label_translations'] ?? []);
            $data['label_translations'] = $data['label_translations'] ?? [];
            $data['features'] = json_encode($data['features']);
            $data['is_popular'] = filter_var($data['is_popular'] ?? false, FILTER_VALIDATE_BOOLEAN);

            return $this->subscriptionPlanRepository->update($subscription, $data);
        });
    }

    public function delete(Subscriptions $subscription): bool
    {
        return DB::transaction(function () use ($subscription): bool {
            return $this->subscriptionPlanRepository->delete($subscription);
        });
    }
}
