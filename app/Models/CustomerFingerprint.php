<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'customer_id',
    'fingerprint_hash',
    'ip_address',
    'user_agent',
    'screen_resolution',
    'timezone',
    'webgl_hash',
    'fonts_hash',
    'canvas_hash',
    'is_datacenter_ip',
    'is_vpn',
])]
class CustomerFingerprint extends Model
{
    protected $table = 'customers_fingerprints';

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'customer_id' => 'integer',
            'is_datacenter_ip' => 'boolean',
            'is_vpn' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
