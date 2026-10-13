<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'surname',
    'username',
    'email',
    'password',
    'mobile',
    'mobile_code',
    'country',
    'country_code',
    'device_id',
    'registration_ip',
    'registration_user_agent',
    'webgl_vendor',
    'avatar_path',
    'trial_ends_at',
    'subscription_ends_at',
    'subscription_plan',
])]
#[Hidden(['password', 'remember_token', 'code', 'login_otp'])]
class Customer extends Authenticatable
{
    use Notifiable;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_proxy_or_datacenter' => 'boolean',
            'risk_score' => 'integer',
            'is_blocked' => 'boolean',
            'is_suspended' => 'boolean',
            'is_verified' => 'boolean',
            'register_sms_count' => 'integer',
            'login_sms_count' => 'integer',
            'trial_ends_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
            'admin_free_days' => 'integer',
            'last_login_at' => 'datetime',
            'login_count' => 'integer',
        ];
    }

    public function loginSessions(): HasMany
    {
        return $this->hasMany(CustomerLoginSession::class);
    }

    public function fingerprints(): HasMany
    {
        return $this->hasMany(CustomerFingerprint::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function predictionVotes(): HasMany
    {
        return $this->hasMany(PredictionVote::class);
    }

    public function hasActiveSubscription(): bool
    {
        return $this->subscription_ends_at?->isFuture() ?? false;
    }

    public function hasActiveTrial(): bool
    {
        return $this->trial_ends_at?->isFuture() ?? false;
    }
}
