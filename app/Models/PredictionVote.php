<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'vote_key',
    'customer_id',
    'sport',
    'match_id',
    'poll',
    'value',
])]
class PredictionVote extends Model
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'customer_id' => 'integer',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
