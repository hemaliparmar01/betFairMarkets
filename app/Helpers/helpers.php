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
    function ApiCall(string $url, string $key)
    {
        if (parse_url($url, PHP_URL_HOST) === 'api.sportsapipro.com') {
            recordSportsApiProRestRequest((string) parse_url($url, PHP_URL_PATH));
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [$key]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = json_decode(curl_exec($ch), true);

        curl_close($ch);

        return $data;
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
    function withRedisLock(string $cacheKey, callable $callback)
    {
        return Cache::store('redis')
            ->lock("{$cacheKey}:write-lock", 30)
            ->block(5, $callback);
    }
}

if (! function_exists('getCurrentUserTimezone')) {
    function getCurrentUserTimezone()
    {
        return 'Asia/Kolkata';
    }
}
