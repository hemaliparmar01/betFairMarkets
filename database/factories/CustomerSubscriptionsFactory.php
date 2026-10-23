<?php

namespace Database\Factories;

use App\Models\CustomerSubscriptions;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerSubscriptions>
 */
class CustomerSubscriptionsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => CustomerFactory::new(),
            'subscription_id' => SubscriptionsFactory::new(),
            'gateway' => 'paypal',
            'status' => 'pending',
            'amount' => '10.00',
            'currency' => 'EUR',
            'duration_days' => 30,
            'auto_renew' => false,
        ];
    }
}
