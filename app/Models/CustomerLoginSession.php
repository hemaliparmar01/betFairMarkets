<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'customer_id',
    'session_id',
    'ip_address',
    'user_agent',
    'device_type',
    'logged_in_at',
    'logged_out_at',
    'duration_seconds',
])]
class CustomerLoginSession extends Model
{
    protected $table = 'customers_login_sessions';

    public $timestamps = false;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'customer_id' => 'integer',
            'logged_in_at' => 'datetime',
            'logged_out_at' => 'datetime',
            'duration_seconds' => 'integer',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
