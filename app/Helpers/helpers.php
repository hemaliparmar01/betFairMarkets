<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
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

if (! function_exists('getSportsApiProImage')) {
    function getSportsApiProImage(string $sport, string $type, mixed $entityId): ?string
    {
        $sports = [
            'football' => ['football', 'Football'],
            'cricket' => ['cricket', 'Cricket'],
            'icehockey' => ['hockey', 'IceHockey'],
            'americanfootball' => ['american-football', 'AmericanFootball'],
            'baseball' => ['baseball', 'Baseball'],
            'basketball' => ['basketball', 'Basketball'],
            'tennis' => ['tennis', 'Tennis'],
        ];
        if (! isset($sports[$sport]) || ! in_array($type, ['teams', 'tournaments'], true) || ! is_numeric($entityId) || (int) $entityId <= 0) {
            return null;
        }

        $entityId = (int) $entityId;
        $directory = "images/{$sport}/{$type}";
        $images = glob(public_path("{$directory}/{$entityId}.*"));
        if ($images && File::isFile($images[0])) {
            return '/'.$directory.'/'.basename($images[0]);
        }

        $lockName = "SportsAPIPro:image-pending:{$sport}:{$type}:{$entityId}";
        $missingKey = "SportsAPIPro:image-unavailable:{$sport}:{$type}:{$entityId}";
        $pendingLock = null;
        try {
            if (Cache::store('redis')->has($missingKey)) {
                return null;
            }
            $pendingLock = Cache::store('redis')->lock($lockName, 3600);
            if (! $pendingLock->get()) {
                return null;
            }
            $owner = $pendingLock->owner();
            [$apiSport, $prefix] = $sports[$sport];
            dispatch(static function () use ($sport, $type, $entityId, $directory, $lockName, $missingKey, $owner, $apiSport, $prefix): void {
                $lock = Cache::store('redis')->restoreLock($lockName, $owner);
                try {
                    if (! $lock->isOwnedByCurrentProcess()) {
                        return;
                    }
                    $images = glob(public_path("{$directory}/{$entityId}.*"));
                    if (! $images || ! File::isFile($images[0])) {
                        $response = ApiCall("https://api.sportsapipro.com/v2/{$apiSport}/images/{$type}/{$entityId}", [
                            'x-api-key' => GetSportApiProKey(),
                            'Accept' => 'image/png,image/jpeg,image/webp,image/gif',
                        ], ['connect_timeout' => 3, 'timeout' => 10, 'raw_response' => true, 'follow_redirects' => true]);
                        $contentType = strtolower(trim(explode(';', $response['content_type'])[0]));
                        $extension = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp', 'image/gif' => 'gif'][$contentType] ?? null;
                        if (! $response['successful'] || $extension === null || $response['body'] === '' || strlen($response['body']) > 5_000_000) {
                            Cache::store('redis')->put($missingKey, true, in_array($response['status'], [404, 410], true) ? 21600 : 300);

                            return;
                        }
                        File::ensureDirectoryExists(public_path($directory));
                        File::replace(public_path("{$directory}/{$entityId}.{$extension}"), $response['body']);
                        $images = [public_path("{$directory}/{$entityId}.{$extension}")];
                    }
                    $imagePath = '/'.$directory.'/'.basename($images[0]);
                    $cacheKey = $prefix.':matches';
                    $updated = withRedisLock($cacheKey, static function () use ($cacheKey, $type, $entityId, $imagePath, $sport): bool {
                        $matches = getInRedis($cacheKey) ?? [];
                        $updated = false;
                        $fields = $type === 'teams'
                            ? ['home_team_logo' => 'home_team_id', 'away_team_logo' => 'away_team_id']
                            : ['tournament_logo' => in_array($sport, ['football', 'cricket'], true) ? 'tournament_id' : 'tournament_image_id'];
                        foreach ($matches as &$match) {
                            foreach ($fields as $field => $idField) {
                                if ((int) ($match[$idField] ?? 0) === $entityId && ($match[$field] ?? null) !== $imagePath) {
                                    $match[$field] = $imagePath;
                                    $updated = true;
                                }
                            }
                        }
                        unset($match);
                        if ($updated) {
                            storeInRedis($cacheKey, $matches);
                        }

                        return $updated;
                    }, 2);
                    if ($updated) {
                        $eventClass = 'App\\Events\\'.$prefix.'SportsScoreUpdated';
                        Event::dispatch(new $eventClass);
                    }
                } catch (Throwable $e) {
                    Cache::store('redis')->put($missingKey, true, 300);
                    Log::warning('SportsAPI Pro background image failed', ['sport' => $sport, 'type' => $type, 'entity_id' => $entityId, 'exception' => $e::class]);
                } finally {
                    $lock->release();
                }
            })->onQueue('sports-images')->catch(static function (Throwable $e) use ($lockName, $owner, $missingKey): void {
                Cache::store('redis')->restoreLock($lockName, $owner)->release();
                Cache::store('redis')->put($missingKey, true, 300);
            });
        } catch (Throwable $e) {
            $pendingLock?->release();
            Log::warning('SportsAPI Pro image could not be queued', ['sport' => $sport, 'type' => $type, 'entity_id' => $entityId, 'exception' => $e::class]);
        }

        return null;
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
