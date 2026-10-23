<?php

namespace Database\Factories;

use App\Models\Subscriptions;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Subscriptions> */
class SubscriptionsFactory extends Factory
{
    protected $model = Subscriptions::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'label' => fake()->unique()->word(),
            'label_translations' => ['ka' => 'Premium'],
            'description' => 'Full access',
            'duration_days' => 30,
            'price_for_local' => 20,
            'price_for_international' => 10,
            'price_label' => 'per month',
            'features' => json_encode([['text' => 'Live markets', 'is_highlighted' => false]]),
            'button_text' => 'Subscribe',
            'is_active' => true,
        ];
    }
}
