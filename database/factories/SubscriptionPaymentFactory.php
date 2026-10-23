<?php

namespace Database\Factories;

use App\Models\SubscriptionPayment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SubscriptionPayment>
 */
class SubscriptionPaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_subscription_id' => CustomerSubscriptionsFactory::new(),
            'gateway' => 'paypal',
            'event_id' => (string) Str::uuid(),
            'kind' => 'payment',
            'status' => 'paid',
            'amount' => '10.00',
            'currency' => 'EUR',
        ];
    }
}
