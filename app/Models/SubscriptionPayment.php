<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionPayment extends Model
{
    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['payload' => 'array', 'amount' => 'decimal:2'];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(CustomerSubscriptions::class, 'customer_subscription_id');
    }
}
