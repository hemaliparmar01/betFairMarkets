<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'unique_key',
    'betfair_match_id',
    'sports_api_pro_match_id',
    'team1',
    'team2',
    'tournament',
    'result_status',
    'minutes_status',
    'start_timestamp',
    'data_sync_complete',
    'payload',
])]
class HorseRacingMatch extends Model
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'start_timestamp' => 'integer',
            'data_sync_complete' => 'boolean',
            'payload' => 'array',
        ];
    }

    public function markets(): HasMany
    {
        return $this->hasMany(HorseRacingMarket::class, 'horse_racing_match_unique_key', 'unique_key');
    }
}
