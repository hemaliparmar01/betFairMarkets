<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscriptions extends Model
{
    use SoftDeletes;

    protected $table = 'subscriptions';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'price_for_local' => 'decimal:2',
            'price_for_international' => 'decimal:2',
            'is_popular' => 'boolean',
            'duration_days' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'label_translations' => 'array',
        ];
    }
}
