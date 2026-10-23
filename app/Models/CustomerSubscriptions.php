<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerSubscriptions extends Model
{
    protected $table = 'customers_subscriptions';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'auto_renew' => 'boolean',
            'next_billing_at' => 'datetime',
            'last_payment_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'duration_days' => 'integer',
        ];
    }

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(Subscriptions::class, 'subscription_id')->withTrashed();
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
