<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'selection_key',
    'basketball_match_unique_key',
    'market_id',
    'market_type',
    'runner_id',
    'runner',
    'sort_priority',
    'handicap',
    'previous_back_odds',
    'current_back_odds',
    'back_size',
    'lay_odds',
    'lay_size',
    'payload',
])]
class BasketballMarket extends Model
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'sort_priority' => 'integer',
            'handicap' => 'float',
            'previous_back_odds' => 'float',
            'current_back_odds' => 'float',
            'back_size' => 'float',
            'lay_odds' => 'float',
            'lay_size' => 'float',
            'payload' => 'array',
        ];
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(BasketballMatch::class, 'basketball_match_unique_key', 'unique_key');
    }
}
