<?php

use App\Services\MatchBackup;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

if (! function_exists('hasMeaningfulOpeningOdds')) {
    /** @param array<string, mixed> $runner */
    function hasMeaningfulOpeningOdds(array $runner): bool
    {
        return ($runner['monitoring_baseline_version'] ?? null) === 1
            && in_array($runner['opening_odds_source'] ?? null, ['available_liquidity', 'first_observed'], true)
            && is_numeric($runner['initialBackOdds'] ?? null)
            && is_finite((float) $runner['initialBackOdds'])
            && (float) $runner['initialBackOdds'] > 1
            && (int) ($runner['initialBackOddsTimestamp'] ?? 0) > 0;
    }
}

if (! function_exists('prepareOddsMonitoring')) {
    /**
     * Legacy price history has no liquidity evidence and cannot establish an opening.
     *
     * @param  array<string, mixed>  $runner
     * @return array<string, mixed>
     */
    function prepareOddsMonitoring(array $runner): array
    {
        if (hasMeaningfulOpeningOdds($runner)) {
            return $runner;
        }

        return array_replace($runner, [
            'initialBackOdds' => null, 'initialBackOddsTimestamp' => null,
            'opening_odds_source' => null, 'opening_odds_evidence' => null,
            'odds_direction' => 'same', 'backOddsHistory' => [],
            'odds_history_version' => 1, 'odds_history_incomplete' => false,
            'odds_history_processed_at' => 0,
            'movementHistory' => [], 'recentOddsHistory' => [],
            'prematch_drop_baseline_odds' => null, 'prematch_drop_triggered' => false,
            'prematch_drop_detected_at' => null, 'market_shock_detected_at' => null,
            'market_shock_snapshots' => [], 'pressure_type' => 'neutral', 'pressure_amount' => 0,
        ]);
    }
}

if (! function_exists('meaningfulOpeningOdds')) {
    /**
     * Available sizes are quote evidence, not traded volume. A funded competitive
     * Back/Lay book permits genuine low prices too. Without size data, use only
     * the first observed valid quote, never an invented historical opening.
     *
     * @param  array<string, mixed>  $quote
     * @param  array<string, mixed>  $previous
     * @return array{initialBackOdds: ?float, initialBackOddsTimestamp: ?int, opening_odds_source: ?string, opening_odds_evidence: ?array<string, float>}
     */
    function meaningfulOpeningOdds(array $quote, array $previous, float $now, bool $canStart = true): array
    {
        if (hasMeaningfulOpeningOdds($previous)) {
            return array_intersect_key($previous, array_flip(['initialBackOdds', 'initialBackOddsTimestamp', 'opening_odds_source', 'opening_odds_evidence']));
        }
        $opening = ['initialBackOdds' => null, 'initialBackOddsTimestamp' => null, 'opening_odds_source' => null, 'opening_odds_evidence' => null];
        $price = $quote['backOdds'] ?? null;
        if (! $canStart || ! is_numeric($price) || ! is_finite((float) $price) || (float) $price <= 1) {
            return $opening;
        }
        $hasLiquidity = array_key_exists('backSize', $quote) || array_key_exists('laySize', $quote);
        if ($hasLiquidity) {
            $lay = $quote['layOdds'] ?? null;
            $backSize = $quote['backSize'] ?? null;
            $laySize = $quote['laySize'] ?? null;
            if (! is_numeric($lay) || ! is_finite((float) $lay) || (float) $lay < (float) $price
                || ! is_numeric($backSize) || ! is_finite((float) $backSize) || (float) $backSize <= 0
                || ! is_numeric($laySize) || ! is_finite((float) $laySize) || (float) $laySize <= 0
                || ((float) $lay - (float) $price) / (float) $price > 0.20 + 1e-9) {
                return $opening;
            }
            $opening['opening_odds_evidence'] = ['back_size' => (float) $backSize, 'lay_odds' => (float) $lay, 'lay_size' => (float) $laySize];
        }
        $opening['initialBackOdds'] = (float) $price;
        $opening['initialBackOddsTimestamp'] = (int) $now;
        $opening['opening_odds_source'] = $hasLiquidity ? 'available_liquidity' : 'first_observed';

        return $opening;
    }
}

if (! function_exists('updateOddsMonitoring')) {
    /**
     * Keep the opening and the chart anchor together for this market/selection.
     *
     * @param  array<string, mixed>  $quote
     * @param  array<string, mixed>  $previous
     * @return array<string, mixed>
     */
    function updateOddsMonitoring(array $quote, array $previous, float $now, bool $canStart = true): array
    {
        foreach (['marketId', 'marketType', 'runnerId', 'handicap'] as $identity) {
            if (isset($quote[$identity], $previous[$identity]) && (string) $quote[$identity] !== (string) $previous[$identity]) {
                $previous = [];
                break;
            }
        }
        $updates = $quote['_odds_updates'] ?? [];
        unset($quote['_odds_updates']);
        $beforeBatch = $previous;
        if ($updates !== []) {
            $processedAt = (float) ($previous['odds_history_processed_at'] ?? 0);
            $updates = array_filter($updates, static fn (array $update): bool => (float) ($update['observed_at'] ?? $now) > $processedAt);
            if ($updates === []) {
                return $previous;
            }
            $lastUpdate = array_key_last($updates);
            foreach ($updates as $index => $update) {
                $observedAt = (float) ($update['observed_at'] ?? $now);
                unset($update['observed_at']);
                if ($index === $lastUpdate) {
                    $now = $observedAt;
                } else {
                    $previous = updateOddsMonitoring(array_replace($quote, $update), $previous, $observedAt, $canStart);
                }
            }
        }
        $hasPreviousOpening = hasMeaningfulOpeningOdds($previous);
        $previous = prepareOddsMonitoring($previous);
        $opening = meaningfulOpeningOdds($quote, $previous, $now, $canStart);
        $runner = array_replace(prepareOddsMonitoring(array_replace($previous, $quote)), $opening);
        $price = $quote['backOdds'] ?? null;
        $current = is_numeric($price) && is_finite((float) $price) && (float) $price > 1 ? (float) $price : 0.0;
        $runner['monitoring_baseline_version'] = 1;
        $runner['monitoring_started_at'] = $previous['monitoring_started_at'] ?? (int) $now;
        $previousPrice = (float) ($previous['currentBackOdds'] ?? 0);
        $previousHistory = $previous['backOddsHistory'] ?? [];
        $runner['previousBackOdds'] = ! $hasPreviousOpening ? $current
            : ($previousPrice > 1 ? $previousPrice : (float) ($previousHistory[array_key_last($previousHistory)]['odds'] ?? $current));
        $runner['currentBackOdds'] = $current;
        $initial = (float) ($opening['initialBackOdds'] ?? 0);
        $runner['odds_direction'] = $initial > 1 && $current > 1 ? ($current < $initial ? 'down' : ($current > $initial ? 'up' : 'same')) : 'same';
        $history = $initial > 1 ? ($previous['backOddsHistory'] ?? []) : [];
        $legacyHistory = ($previous['odds_history_version'] ?? null) !== 1;
        if ($legacyHistory && $history !== []) {
            $history = array_values(array_filter($history, static fn (array $point): bool => is_numeric($point['odds'] ?? null)
                && is_finite((float) $point['odds']) && (float) $point['odds'] > 1
                && (int) ($point['timestamp'] ?? 0) >= $opening['initialBackOddsTimestamp']));
        }
        if ($initial > 1 && ($history === [] || (float) $history[0]['odds'] !== $initial || (int) $history[0]['timestamp'] !== $opening['initialBackOddsTimestamp'])) {
            array_unshift($history, ['timestamp' => $opening['initialBackOddsTimestamp'], 'odds' => $initial]);
        }
        if ($initial > 1 && $current > 1 && (float) $history[array_key_last($history)]['odds'] !== $current) {
            $history[] = ['timestamp' => (int) $now, 'odds' => $current];
        }
        $runner['backOddsHistory'] = $history;
        $runner['odds_history_version'] = 1;
        $runner['odds_history_processed_at'] = max($now, (float) ($previous['odds_history_processed_at'] ?? 0));
        $runner['odds_history_incomplete'] = ($previous['odds_history_incomplete'] ?? false)
            || ($legacyHistory && count($previous['backOddsHistory'] ?? []) === 12);
        if (count($updates) > 1) {
            $previousBatchHistory = $beforeBatch['backOddsHistory'] ?? [];
            $previousBatchPrice = (float) ($beforeBatch['currentBackOdds'] ?? 0);
            $runner['previousBackOdds'] = hasMeaningfulOpeningOdds($beforeBatch)
                ? ($previousBatchPrice > 1 ? $previousBatchPrice : (float) ($previousBatchHistory[array_key_last($previousBatchHistory)]['odds'] ?? $current))
                : ($initial > 1 ? $initial : $current);
        }

        return $runner;
    }
}

if (! function_exists('oddsMovementHistory')) {
    /** @return list<array{timestamp: int, odds: float}> */
    function oddsMovementHistory(array $runner): array
    {
        return hasMeaningfulOpeningOdds($runner) && is_array($runner['backOddsHistory'] ?? null)
            ? $runner['backOddsHistory'] : [];
    }
}

if (! function_exists('oddsMovementSequence')) {
    /** @param list<array{timestamp?: int, odds?: float}> $history */
    function oddsMovementSequence(array $history): string
    {
        $prices = [];
        $previous = null;
        foreach ($history as $point) {
            $price = $point['odds'] ?? null;
            if (! is_numeric($price) || ! is_finite((float) $price) || (float) $price <= 1 || (float) $price === $previous) {
                continue;
            }
            $previous = (float) $price;
            $formatted = rtrim(number_format($previous, 6, '.', ''), '0');
            $prices[] = str_pad($formatted, strpos($formatted, '.') + 3, '0');
        }

        return implode(' → ', $prices);
    }
}

if (! function_exists('prepareMatchOddsMonitoring')) {
    /**
     * @param  array<string, mixed>  $match
     * @return array<string, mixed>
     */
    function prepareMatchOddsMonitoring(array $match): array
    {
        foreach ($match['markets'] ?? [] as $key => $runners) {
            $match['markets'][$key] = array_map('prepareOddsMonitoring', $runners);
            if (! array_any($match['markets'][$key], 'hasMeaningfulOpeningOdds')) {
                unset($match['market_indicators'][$key]);
            }
        }

        return $match;
    }
}

if (! function_exists('racingFeedHealth')) {
    /** @return array{state: string, last_frame_at: ?int} */
    function racingFeedHealth(string $sport): array
    {
        $feed = $sport === 'greyhounds' ? 'greyhounds' : 'horse_prematch';
        $connection = Cache::store('redis')->get('Betfair:racing:connection', []);
        $health = Cache::store('redis')->get('Betfair:racing:feed:'.$feed, []);
        $state = ($connection['state'] ?? '') === 'connected' && time() - ($connection['at'] ?? 0) <= 75
            ? ($health['state'] ?? 'waiting') : 'disconnected';

        return ['state' => $state, 'last_frame_at' => $health['at'] ?? null];
    }
}

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
    function recordSportsApiProRestRequest(string $endpoint, bool $lowPriority = false): int
    {
        $date = gmdate('Y-m-d');
        $requestCount = (int) Redis::eval(<<<'LUA'
local total = tonumber(redis.call('GET', KEYS[1]) or '0')
local remaining = tonumber(redis.call('GET', KEYS[2]) or '-1')
local limit = math.min(tonumber(redis.call('GET', KEYS[3]) or ARGV[2]), tonumber(ARGV[2]))
if ARGV[1] == '1' and (total >= limit - tonumber(ARGV[3])
    or (remaining >= 0 and remaining <= tonumber(ARGV[3]))
    or tonumber(redis.call('GET', KEYS[4]) or '0') >= tonumber(ARGV[4])
    or tonumber(redis.call('GET', KEYS[5]) or '0') > tonumber(ARGV[6])) then
    return 0
end
total = redis.call('INCR', KEYS[1])
redis.call('EXPIREAT', KEYS[1], ARGV[5])
if remaining > 0 then redis.call('DECR', KEYS[2]) end
if ARGV[1] == '1' then
    redis.call('INCR', KEYS[4])
    redis.call('EXPIREAT', KEYS[4], ARGV[5])
end
return total
LUA, 5, 'SportsAPIPro:rest-requests:'.$date, 'SportsAPIPro:rest-remaining:'.$date,
            'SportsAPIPro:rest-limit:'.$date, 'SportsAPIPro:football-v6-requests:'.$date,
            'SportsAPIPro:rest-backoff:'.$date, $lowPriority ? '1' : '0',
            max(1, (int) config('services.sports_api_pro.daily_rest_limit', 75000)),
            max(0, (int) config('services.sports_api_pro.rest_reserve', 7500)),
            max(0, (int) config('services.sports_api_pro.football_v6_daily_budget', 30000)),
            strtotime('tomorrow 00:05 UTC'), time());

        if ($requestCount === 0) {
            if (Cache::store('redis')->add('SportsAPIPro:v6-budget-notice:'.$date, true, 3600)) {
                Log::notice('Football V6 polling paused for API quota reserve or backoff; cached statistics retained');
            }

            return 0;
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

if (! function_exists('recordSportsApiProRestResponse')) {
    /** @param array<string, string> $headers */
    function recordSportsApiProRestResponse(array $headers, int $requestNumber, int $status): void
    {
        $date = gmdate('Y-m-d');
        $expiresAt = strtotime('tomorrow 00:05 UTC');
        if (is_numeric($headers['x-ratelimit-limit'] ?? null)) {
            Redis::setex('SportsAPIPro:rest-limit:'.$date, $expiresAt - time(), (int) $headers['x-ratelimit-limit']);
        }
        if (is_numeric($headers['x-ratelimit-remaining'] ?? null)) {
            Redis::eval(<<<'LUA'
local total = tonumber(redis.call('GET', KEYS[1]) or ARGV[2])
local remaining = math.max(0, tonumber(ARGV[1]) - math.max(0, total - tonumber(ARGV[2])))
local previous = tonumber(redis.call('GET', KEYS[2]) or '-1')
if previous >= 0 then remaining = math.min(previous, remaining) end
redis.call('SET', KEYS[2], remaining)
redis.call('EXPIREAT', KEYS[2], ARGV[3])
LUA, 2, 'SportsAPIPro:rest-requests:'.$date, 'SportsAPIPro:rest-remaining:'.$date,
                (int) $headers['x-ratelimit-remaining'], $requestNumber, $expiresAt);
        }
        if ($status === 429) {
            $retryAt = is_numeric($headers['retry-after'] ?? null) ? time() + (int) $headers['retry-after']
                : (strtotime($headers['retry-after'] ?? '') ?: time() + 300);
            if (isset($headers['x-ratelimit-remaining']) && (int) $headers['x-ratelimit-remaining'] === 0) {
                $retryAt = strtotime($headers['x-ratelimit-reset'] ?? '') ?: strtotime('tomorrow 00:00 UTC');
            }
            Redis::setex('SportsAPIPro:rest-backoff:'.$date, $expiresAt - time(), max(time() + 60, $retryAt));
        }
    }
}

if (! function_exists('apiResponseLogContext')) {
    /**
     * @param  array<string, mixed>  $response
     * @param  list<string>  $secrets
     * @return array<string, mixed>
     */
    function apiResponseLogContext(array $response, array $secrets = []): array
    {
        if (isset($response['url'])) {
            $response['url'] = preg_replace_callback('/([?&](?:x[-_]?api[-_]?key|x[-_]?portal[-_]?api[-_]?key|api[-_]?key|key|token|access_token|auth_key|auth_signature|signature|password|secret)=)([^&#]*)/i', static function (array $match) use (&$secrets): string {
                $secrets[] = urldecode($match[2]);

                return $match[1].'[REDACTED]';
            }, $response['url']);
        }
        $secrets = array_values(array_filter(array_unique($secrets), static fn (string $secret): bool => $secret !== ''));
        foreach ($secrets as $secret) {
            $encoded = json_encode($secret);
            $variants = [$secret, rawurlencode($secret), urlencode($secret)];
            if (is_string($encoded)) {
                $variants[] = substr($encoded, 1, -1);
            }
            foreach ($response as $field => $value) {
                if (is_string($value)) {
                    $response[$field] = str_replace($variants, '[REDACTED]', $value);
                }
            }
        }

        return $response;
    }
}

if (! function_exists('ApiCall')) {
    /**
     * @param  string|array<string, string>|list<string>  $key
     * @param  array{query?: array<string, mixed>, connect_timeout?: int, timeout?: int, raw_response?: bool, follow_redirects?: bool, low_priority?: bool}  $options
     * @param  array<string, mixed>|null  $diagnostics
     */
    function ApiCall(string $url, string|array $key, array $options = [], ?array &$diagnostics = null): mixed
    {
        $captureDiagnostics = func_num_args() >= 4;
        $diagnostics = null;
        $query = http_build_query($options['query'] ?? [], '', '&', PHP_QUERY_RFC3986);
        if ($query !== '') {
            $url .= (str_contains($url, '?') ? '&' : '?').$query;
        }

        $isSportsApiPro = parse_url($url, PHP_URL_HOST) === 'api.sportsapipro.com';
        $requestNumber = 0;
        if ($isSportsApiPro) {
            $requestNumber = recordSportsApiProRestRequest((string) parse_url($url, PHP_URL_PATH), $options['low_priority'] ?? false);
            if ($requestNumber === 0) {
                $diagnostics = apiResponseLogContext(['url' => $url, 'http_status' => null, 'skipped' => 'quota_reserve']);

                return null;
            }
        }

        $headers = [];
        $secrets = [];
        foreach ((array) $key as $name => $value) {
            $header = is_string($name) ? $name.': '.$value : $value;
            $headers[] = $header;
            if (preg_match('/^[^:]*?(?:authorization|key|token|secret)[^:]*:\s*(.+)$/i', $header, $matches)) {
                $secrets[] = $matches[1];
                $secrets[] = preg_replace('/^Bearer\s+/i', '', $matches[1]);
            }
        }

        $responseHeaders = [];
        $ch = curl_init($url);
        try {
            curl_setopt_array($ch, [
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => $options['connect_timeout'] ?? 0,
                CURLOPT_TIMEOUT => $options['timeout'] ?? 0,
                CURLOPT_FOLLOWLOCATION => $options['follow_redirects'] ?? false,
                CURLOPT_MAXREDIRS => 5,
                CURLOPT_HEADERFUNCTION => static function (CurlHandle $handle, string $line) use (&$responseHeaders): int {
                    if (str_starts_with($line, 'HTTP/')) {
                        $responseHeaders = [];
                    } elseif (str_contains($line, ':')) {
                        [$name, $value] = explode(':', $line, 2);
                        $responseHeaders[strtolower(trim($name))] = trim($value);
                    }

                    return strlen($line);
                },
            ]);
            $body = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $contentType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $curlErrorCode = curl_errno($ch);
            $curlError = curl_error($ch);
            if ($isSportsApiPro) {
                recordSportsApiProRestResponse($responseHeaders, $requestNumber, $status);
            }
            $rawResponse = $options['raw_response'] ?? false;
            $data = null;
            $jsonError = null;
            if ($body !== false && (! $rawResponse || str_contains(strtolower($contentType), 'json'))) {
                $data = json_decode($body, true);
                $jsonError = json_last_error() === JSON_ERROR_NONE ? null : json_last_error_msg();
            }
            $failed = $body === false || $status < 200 || $status >= 300 || $jsonError !== null
                || (is_array($data) && ($data['success'] ?? true) === false);
            if ($captureDiagnostics || $failed) {
                $diagnostics = apiResponseLogContext([
                    'url' => $url,
                    'http_status' => $status,
                    'content_type' => $contentType,
                    'curl_errno' => $curlErrorCode,
                    'curl_error' => $curlError !== '' ? $curlError : null,
                    'json_error' => $jsonError,
                    'response_body' => $body === false ? null : $body,
                ], $secrets);
                if ($failed) {
                    Log::warning('API request failed', $diagnostics);
                }
            }

            if ($rawResponse) {
                return [
                    'body' => $body === false ? '' : $body,
                    'status' => $status,
                    'content_type' => $contentType,
                    'successful' => $body !== false && $status >= 200 && $status < 300,
                ];
            }

            return $data;
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
                $apiDiagnostics = null;
                try {
                    if (! $lock->isOwnedByCurrentProcess()) {
                        return;
                    }
                    $images = glob(public_path("{$directory}/{$entityId}.*"));
                    if (! $images || ! File::isFile($images[0])) {
                        $response = ApiCall("https://api.sportsapipro.com/v2/{$apiSport}/images/{$type}/{$entityId}", [
                            'x-api-key' => GetSportApiProKey(),
                            'Accept' => 'image/png,image/jpeg,image/webp,image/gif',
                        ], ['connect_timeout' => 3, 'timeout' => 10, 'raw_response' => true, 'follow_redirects' => true], $apiDiagnostics);
                        $contentType = strtolower(trim(explode(';', $response['content_type'])[0]));
                        $extension = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp', 'image/gif' => 'gif'][$contentType] ?? null;
                        if (! $response['successful'] || $extension === null || $response['body'] === '' || strlen($response['body']) > 5_000_000) {
                            Log::warning('SportsAPI Pro image response unavailable', ['sport' => $sport, 'type' => $type, 'entity_id' => $entityId, 'api' => $apiDiagnostics]);
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
                    Log::warning('SportsAPI Pro background image failed', ['sport' => $sport, 'type' => $type, 'entity_id' => $entityId, 'api' => $apiDiagnostics, 'exception' => $e::class, 'message' => $e->getMessage()]);
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
    function storeInRedis(string $cacheKey, array $data, int $ttlMinutes = 10, bool $mergeLifecycle = true)
    {
        $existingData = Redis::get($cacheKey);
        $existingData = $existingData ? json_decode($existingData, true) : [];
        $mergedData = array_replace($existingData, $data);
        $sport = MatchBackup::sportForCache($cacheKey);
        if ($sport !== null && $mergeLifecycle) {
            $cleaned = array_fill_keys(Redis::zrangebyscore($cacheKey.':cleaned', time(), '+inf'), true);
            foreach ($mergedData as $matchKey => &$match) {
                if (isset($cleaned[$matchKey])) {
                    unset($mergedData[$matchKey]);

                    continue;
                }
                $previous = $existingData[$matchKey] ?? [];
                if (($previous['result_status'] ?? '') === 'Finished' && ($match['result_status'] ?? '') !== 'Finished') {
                    $match = $previous;
                }
                $match = MatchBackup::merge($match, $previous, $sport);
            }
            unset($match);
        }
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
        if ($customer->hasActiveSubscription()) {
            return ['is_authenticated' => true, 'has_active_subscription' => true, 'display_odds' => true];
        }

        if ($customer->hasActiveTrial()) {
            return ['is_authenticated' => true, 'has_active_subscription' => true, 'display_odds' => true];
        }

        return ['is_authenticated' => true, 'has_active_subscription' => false, 'display_odds' => false];
    }
}

/** @return array{country_name: ?string, country_code: ?string, country_flag: ?string, tournament_logo: ?string, country_id: mixed} */
function leagueHeadingMetadata(array $match): array
{
    $country = is_array($match['country'] ?? null) ? $match['country'] : [];
    $name = $match['country_name'] ?? $match['tournament_country'] ?? $country['name'] ?? (is_string($match['country'] ?? null) ? $match['country'] : null);
    $code = strtoupper((string) ($match['country_code'] ?? $country['alpha2'] ?? $country['iso2'] ?? $country['code'] ?? ''));

    return [
        'country_name' => is_string($name) && trim($name) !== '' ? trim($name) : null,
        'country_code' => preg_match('/^[A-Z]{2}$/D', $code) === 1 ? $code : null,
        'country_flag' => $match['country_flag'] ?? $country['flag'] ?? $country['flagUrl'] ?? null,
        'tournament_logo' => $match['tournament_logo'] ?? null,
        'country_id' => $match['country_id'] ?? $country['id'] ?? null,
    ];
}

function leagueHeadingKey(array $match, string $name): string
{
    $country = leagueHeadingMetadata($match);

    return $name.'|'.($country['country_id'] ?? $country['country_code'] ?? $country['country_name'] ?? '').'|'.($match['tournament_id'] ?? '');
}

function competitionPriority(string $tournament, ?string $country = null): int
{
    $normalize = static fn (string $name): string => strtolower((string) preg_replace('/[^a-z0-9]+/i', '', Str::ascii($name)));
    static $priorities = null;
    $priorities ??= array_flip(array_map($normalize, config('league-priority', [])));
    $aliases = [
        'englishpremierleague' => 'Premier League', 'spanishlaliga' => 'La Liga',
        'italianseriea' => 'Serie A', 'germanbundesliga' => 'Bundesliga',
        'frenchligue1' => 'Ligue 1', 'uefaeuropaconferenceleague' => 'UEFA Conference League',
        'portugueseprimeiraliga' => 'Primeira Liga', 'dutcheredivisie' => 'Eredivisie',
        'turkishsuperlig' => 'Süper Lig', 'belgianjupilerproleague' => 'Belgian Pro League',
        'englishchampionship' => 'EFL Championship', 'englishleague1' => 'EFL League One',
        'brazilianseriea' => 'Brasileirão Série A', 'usamls' => 'Major League Soccer (MLS)',
        'mls' => 'Major League Soccer (MLS)', 'majorleaguesoccer' => 'Major League Soccer (MLS)',
    ];
    $name = $normalize($tournament);
    $countries = ['premierleague' => 'england', 'ligue1' => 'france', 'ligue2' => 'france', 'seriea' => 'italy', 'serieb' => 'italy'];
    if ($country !== null && $country !== '' && isset($countries[$name]) && $normalize($country) !== $countries[$name]) {
        return PHP_INT_MAX;
    }
    if ($name === 'bundesliga' && $normalize($country ?? '') === 'austria') {
        $name = 'austrianbundesliga';
    }

    return $priorities[$normalize($aliases[$name] ?? $name)] ?? PHP_INT_MAX;
}

/** Preserve match order and the existing fallback order of unranked groups. */
function rankCompetitionGroups(array $groups): array
{
    uasort($groups, static fn (array $first, array $second): int => competitionPriority((string) ($first['tournament_name'] ?? ''), $first['country_name'] ?? $first['tournament_country'] ?? null)
        <=> competitionPriority((string) ($second['tournament_name'] ?? ''), $second['country_name'] ?? $second['tournament_country'] ?? null));

    return $groups;
}

function footballStreakClassification(string $name): string
{
    $meaning = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', ' ', Str::ascii($name))));
    if (preg_match('/^(no wins|without clean sheet|no clean sheet|first to concede|losses(?: in a row)?)(?: |$)/', $meaning)) {
        return 'negative';
    }
    if (preg_match('/^(wins(?: in a row)?|clean sheets?|first to score|unbeaten|no losses)(?: |$)/', $meaning)) {
        return 'positive';
    }

    return 'neutral';
}
