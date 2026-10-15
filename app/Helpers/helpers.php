<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

if (! function_exists('GetBetfairKey')) {
    function GetBetfairKey()
    {
        return env('Betfair_API_KEY');
    }
}

if (! function_exists('GetSportApiProKey')) {
    function GetSportApiProKey()
    {
        return env('SPORTS_API_PRO_KEY');
    }
}

if (! function_exists('recordSportsApiProRestRequest')) {
    function recordSportsApiProRestRequest(string $endpoint): int
    {
        $cacheKey = 'SportsAPIPro:rest-requests:'.gmdate('Y-m-d');
        $requestCount = (int) Redis::incr($cacheKey);

        if ($requestCount === 1) {
            Redis::expireat($cacheKey, strtotime('tomorrow 00:05 UTC'));
        }

        if (in_array($requestCount, [60000, 67500, 71250], true)) {
            Log::warning('SportsAPI Pro daily REST request usage is approaching the plan limit', [
                'requests' => $requestCount,
                'daily_limit' => 75000,
                'endpoint' => $endpoint,
            ]);
        }

        return $requestCount;
    }
}

if (! function_exists('ApiCall')) {
    /**
     * @param  string|array<string, string>|list<string>  $key
     * @param  array{query?: array<string, mixed>, connect_timeout?: int, timeout?: int, raw_response?: bool, follow_redirects?: bool}  $options
     */
    function ApiCall(string $url, string|array $key, array $options = []): mixed
    {
        $query = http_build_query($options['query'] ?? [], '', '&', PHP_QUERY_RFC3986);
        if ($query !== '') {
            $url .= (str_contains($url, '?') ? '&' : '?').$query;
        }

        if (parse_url($url, PHP_URL_HOST) === 'api.sportsapipro.com') {
            recordSportsApiProRestRequest((string) parse_url($url, PHP_URL_PATH));
        }

        $headers = [];
        foreach ((array) $key as $name => $value) {
            $headers[] = is_string($name) ? $name.': '.$value : $value;
        }

        $ch = curl_init($url);
        try {
            curl_setopt_array($ch, [
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => $options['connect_timeout'] ?? 0,
                CURLOPT_TIMEOUT => $options['timeout'] ?? 0,
                CURLOPT_FOLLOWLOCATION => $options['follow_redirects'] ?? false,
                CURLOPT_MAXREDIRS => 5,
            ]);
            $body = curl_exec($ch);

            if ($options['raw_response'] ?? false) {
                $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

                return [
                    'body' => $body === false ? '' : $body,
                    'status' => $status,
                    'content_type' => (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE),
                    'successful' => $body !== false && $status >= 200 && $status < 300,
                ];
            }

            return $body === false ? null : json_decode($body, true);
        } finally {
            curl_close($ch);
        }
    }
}

if (! function_exists('storeInRedis')) {
    function storeInRedis(string $cacheKey, array $data, int $ttlMinutes = 10)
    {
        $existingData = Redis::get($cacheKey);
        $existingData = $existingData ? json_decode($existingData, true) : [];
        $mergedData = array_replace($existingData, $data);
        Redis::set($cacheKey, json_encode($mergedData));
        Redis::expire($cacheKey, $ttlMinutes * 60);
    }
}

if (! function_exists('getInRedis')) {
    function getInRedis(string $cacheKey)
    {
        $cachedData = Redis::get($cacheKey);

        return $cachedData === null ? null : json_decode($cachedData, true);
    }
}

if (! function_exists('withRedisLock')) {
    function withRedisLock(string $cacheKey, callable $callback, int $waitSeconds = 5): mixed
    {
        return Cache::store('redis')
            ->lock("{$cacheKey}:write-lock", 30)
            ->block($waitSeconds, $callback);
    }
}

if (! function_exists('getCurrentUserTimezone')) {
    function getCurrentUserTimezone()
    {
        return 'Asia/Kolkata';
    }
}

if (! function_exists('checkAuthAndSubscription')) {
    function checkAuthAndSubscription()
    {
        if (! auth()->guard('customers')->check()) {
            return ['is_authenticated' => false, 'has_active_subscription' => false, 'display_odds' => false];
        }

        $customer = auth()->guard('customers')->user();
        $adminFreeDays = (int) $customer->admin_free_days;

        if ($adminFreeDays != null) {
            if ($adminFreeDays == -1) {
                return ['is_authenticated' => true, 'has_active_subscription' => true, 'display_odds' => true];
            }
            if ($adminFreeDays > 0) {
                $currentExpireDate = $customer->subscription_ends_at ?? $customer->trial_ends_at;

                if ($currentExpireDate) {
                    $timezone = get_current_user_timezone();
                    $extendedExpireDate = Carbon::parse($currentExpireDate)->timezone($timezone)->addDays($adminFreeDays);
                    $currentDate = Carbon::now($timezone);
                    if ($currentDate->lte($extendedExpireDate)) {
                        return ['is_authenticated' => true, 'has_active_subscription' => true, 'display_odds' => true];
                    }
                }
            }
        }
        if ($customer->hasActiveSubscription()) {
            return ['is_authenticated' => true, 'has_active_subscription' => true, 'display_odds' => true];
        }

        if ($customer->hasActiveTrial()) {
            return ['is_authenticated' => true, 'has_active_subscription' => true, 'display_odds' => true];
        }

        return ['is_authenticated' => true, 'has_active_subscription' => false, 'display_odds' => false];
    }
}
