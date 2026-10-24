<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class MatchBackup
{
    private const LEGACY_EXPIRY_STATUSES = ['Finished - Awaiting replay', 'Finished - Awaiting Reply'];

    public const SPORTS = [
        'football' => ['Football', 120],
        'basketball' => ['Basketball', 180],
        'icehockey' => ['IceHockey', 180],
        'americanfootball' => ['AmericanFootball', 240],
        'baseball' => ['Baseball', 240],
        'tennis' => ['Tennis', null],
        'cricket' => ['Cricket', null],
        'horseracing' => ['HorseRacing', 3],
        'greyhounds' => ['Greyhounds', 3],
    ];

    public static function sportForCache(string $cacheKey): ?string
    {
        foreach (self::SPORTS as $sport => [$prefix]) {
            if ($cacheKey === $prefix.':matches') {
                return $sport;
            }
        }

        return null;
    }

    public static function isHidden(array $match, string $sport, ?int $now = null): bool
    {
        $now ??= time();
        $race = in_array($sport, ['horseracing', 'greyhounds'], true);
        if (($match['result_status'] ?? '') === 'Finished') {
            return $race;
        }
        $start = (int) ($match['timestamp'] ?? 0);
        $minutes = self::SPORTS[$sport][1] ?? null;
        if ($minutes !== null && $start > 0 && $start <= $now - $minutes * 60 && ! self::hasActiveResume($match, $sport, $now)) {
            return true;
        }
        if ($race) {
            return ($match['result_status'] ?? '') !== 'Prematch';
        }

        return ! empty($match['internal_backup']);
    }

    private static function resumeWindow(string $sport): int
    {
        $key = in_array($sport, ['tennis', 'cricket'], true) ? $sport.'_stale_seconds' : 'resume_stale_seconds';

        return max(1, (int) config('services.match_lifecycle.'.$key, 300));
    }

    private static function hasActiveResume(array $match, string $sport, int $now): bool
    {
        $health = $match['provider_lifecycle'] ?? [];

        return ! in_array($sport, ['horseracing', 'greyhounds'], true)
            && ($match['result_status'] ?? '') === 'Live' && ($health['active'] ?? false)
            && (int) ($health['resumed_at'] ?? 0) > 0
            && (int) ($health['meaningful_at'] ?? 0) > $now - self::resumeWindow($sport);
    }

    /** Retain lifecycle metadata when a provider replaces an existing cache row. */
    public static function merge(array $match, array $previous, string $sport): array
    {
        foreach (['internal_backup', 'provider_lifecycle'] as $field) {
            if (! array_key_exists($field, $match) && isset($previous[$field])) {
                $match[$field] = $previous[$field];
            }
        }
        $incomingHealth = $match['provider_lifecycle'] ?? [];
        $storedHealth = $previous['provider_lifecycle'] ?? [];
        if ([(int) ($incomingHealth['meaningful_at'] ?? 0), (int) ($incomingHealth['sequence'] ?? 0)] < [(int) ($storedHealth['meaningful_at'] ?? 0), (int) ($storedHealth['sequence'] ?? 0)]) {
            $match['provider_lifecycle'] = $storedHealth;
        }
        if (($match['result_status'] ?? '') === 'Finished' || self::hasActiveResume($match, $sport, time())
            || (isset($match['internal_backup']) && ! self::pastCutoff($match, $sport) && self::SPORTS[$sport][1] !== null)) {
            unset($match['internal_backup']);
        }

        return $match;
    }

    private static function pastCutoff(array $match, string $sport): bool
    {
        $minutes = self::SPORTS[$sport][1] ?? null;

        return $minutes !== null && (int) ($match['timestamp'] ?? 0) > 0 && (int) $match['timestamp'] <= time() - $minutes * 60;
    }

    private static function activityFingerprint(array $match): string
    {
        $activity = array_replace(array_fill_keys([
            'result_status', 'minutes_status', 'home_score', 'away_score', 'home_score_data', 'away_score_data',
            'current_set', 'current_period_start_timestamp', 'last_period_end_timestamp', 'timestamp',
        ], null), array_intersect_key($match, array_fill_keys([
            'result_status', 'minutes_status', 'home_score', 'away_score', 'home_score_data', 'away_score_data',
            'current_set', 'current_period_start_timestamp', 'last_period_end_timestamp', 'timestamp',
        ], true)));

        return hash('sha256', json_encode($activity, JSON_THROW_ON_ERROR));
    }

    public static function isOlderEvent(array $match, array $event): bool
    {
        $sequence = (int) ($event['changes']['changeTimestamp'] ?? $event['changes.changeTimestamp'] ?? 0);

        return $sequence > 0 && $sequence < (int) ($match['provider_lifecycle']['sequence'] ?? 0);
    }

    /** Called after the existing provider mapping; receipt alone is not activity. */
    public static function observe(array &$match, string $sport, array $event, string $channel, array $previous = []): bool
    {
        $before = $match['provider_lifecycle'] ?? [];
        $backup = $match['internal_backup'] ?? null;
        $type = $event['status']['type'] ?? $event['status.type'] ?? null;
        $code = $event['status']['code'] ?? $event['status.code'] ?? null;
        if ($type === null && $code === null) {
            return false;
        }
        $now = time();
        $sequence = (int) ($event['changes']['changeTimestamp'] ?? $event['changes.changeTimestamp'] ?? 0);
        $previousSequence = (int) ($before['sequence'] ?? 0);
        $finished = ($match['result_status'] ?? '') === 'Finished';
        if (! $finished && $sequence > 0 && $previousSequence >= $sequence) {
            return false;
        }
        $eventTime = $sequence >= 100000000000 ? intdiv($sequence, 1000) : ($sequence >= 1000000000 ? $sequence : null);
        if (! $finished && $eventTime !== null && ($eventTime < $now - self::resumeWindow($sport) || $eventTime > $now + 60)) {
            return false;
        }
        $fingerprint = self::activityFingerprint($match);
        $previousFingerprint = $before['activity_fingerprint'] ?? ($previous !== [] ? self::activityFingerprint($previous) : null);
        $progress = $previousFingerprint !== null && $fingerprint !== $previousFingerprint;
        $fresh = $finished || ($sequence > $previousSequence && ($previousSequence > 0 || $eventTime !== null)) || $progress;
        if (! $fresh) {
            return false;
        }
        if (is_numeric($event['startTimestamp'] ?? null) && (int) $event['startTimestamp'] > 0) {
            $match['timestamp'] = (int) $event['startTimestamp'];
        }
        $active = ($match['result_status'] ?? '') === 'Live'
            && ! in_array(strtolower((string) $type), ['interrupted', 'willcontinue', 'postponed', 'suspended', 'cancelled', 'canceled'], true)
            && ! in_array(strtolower((string) ($match['minutes_status'] ?? '')), ['half time', 'halftime', 'ht', 'delayed', 'postponed', 'suspended', 'innings break', 'quarter break'], true)
            && in_array(strtolower((string) $type), ['inprogress', 'live', ''], true);
        $match['provider_lifecycle'] = array_replace($before, [
            'received_at' => $now, 'meaningful_at' => $now, 'sequence' => max($sequence, $previousSequence),
            'status_type' => $type ?? ($before['status_type'] ?? null), 'active' => $active,
            'activity_fingerprint' => self::activityFingerprint($match), 'failed_checks' => 0,
        ]);
        if ($finished) {
            $match['provider_lifecycle']['finished_pending'] = true;
            unset($match['internal_backup']);
        } elseif ($active && ! in_array($sport, ['horseracing', 'greyhounds'], true)
            && ($backup !== null || self::pastCutoff($match, $sport) || ! empty($before['resumed_at']))) {
            $match['provider_lifecycle']['resumed_at'] = $before['resumed_at'] ?? $now;
            $match['provider_lifecycle']['resume_pending'] = true;
            unset($match['internal_backup']);
        } elseif (! self::pastCutoff($match, $sport) && self::SPORTS[$sport][1] !== null) {
            unset($match['internal_backup']);
        }

        return $before !== $match['provider_lifecycle'] || $backup !== ($match['internal_backup'] ?? null);
    }

    /** Count existing REST reconciliation attempts, never issue another API call. */
    public function recordRecheck(string $sport, string $matchId, ?bool $reliable): bool
    {
        $cacheKey = self::SPORTS[$sport][0].':matches';

        return withRedisLock($cacheKey, function () use ($matchId, $reliable, $cacheKey): bool {
            $matches = getInRedis($cacheKey) ?? [];
            foreach ($matches as &$match) {
                if ((string) ($match['sportsApiPro_match_id'] ?? '') !== $matchId) {
                    continue;
                }
                $health = $match['provider_lifecycle'] ?? [];
                if ($reliable === null) {
                    $health['recheck_until'] = time() + 30;
                } else {
                    unset($health['recheck_until']);
                    $health['checked_at'] = time();
                    $health['failed_checks'] = $reliable ? 0 : (int) ($health['failed_checks'] ?? 0) + 1;
                }
                $match['provider_lifecycle'] = $health;
                if ($reliable !== null && ! empty($match['internal_backup']) && ($match['result_status'] ?? '') !== 'Finished') {
                    $match['internal_backup']['checks'] = (int) ($match['internal_backup']['checks'] ?? 0) + 1;
                }
                unset($match);
                storeInRedis($cacheKey, $matches, 30, false);

                return true;
            }
            unset($match);

            return false;
        });
    }

    public function transition(array $match, string $sport, int $now): array
    {
        if (in_array($match['result_status'] ?? '', self::LEGACY_EXPIRY_STATUSES, true)) {
            $match['result_status'] = 'Live';
            $match['minutes_status'] = 'Live';
        }
        if (($match['result_status'] ?? '') === 'Finished' && ! in_array($sport, ['horseracing', 'greyhounds'], true)) {
            unset($match['internal_backup'], $match['provider_lifecycle']['finished_pending']);

            return $match;
        }
        $health = $match['provider_lifecycle'] ?? [];
        $health['observed_at'] ??= $now;
        $health['activity_fingerprint'] ??= self::activityFingerprint($match);
        $health['sequence'] ??= max([(int) ($match['sports_change_timestamp'] ?? 0), ...array_values($match['websocket_change_timestamps'] ?? [])]);
        unset($health['resume_pending']);
        $match['provider_lifecycle'] = $health;
        $cutoff = self::SPORTS[$sport][1] ?? null;
        $stale = in_array($sport, ['tennis', 'cricket'], true)
            && ($match['result_status'] ?? '') === 'Live'
            && (int) ($health['failed_checks'] ?? 0) >= 2
            && (int) ($health['checked_at'] ?? 0) > (int) ($health['received_at'] ?? $health['observed_at'])
            && $now - (int) ($health['received_at'] ?? $health['observed_at']) >= (int) config('services.match_lifecycle.'.$sport.'_stale_seconds');
        if (($cutoff !== null && self::isHidden($match, $sport, $now)) || $stale) {
            $match['internal_backup'] ??= ['since' => $now, 'reason' => $cutoff === null ? 'unreliable_provider' : 'cutoff', 'checks' => 0];
        } elseif ($cutoff !== null) {
            unset($match['internal_backup']);
        }

        return $match;
    }

    public function shouldClean(array $match, string $sport, int $now): bool
    {
        $backup = $match['internal_backup'] ?? null;
        $race = in_array($sport, ['horseracing', 'greyhounds'], true);
        if ((int) ($match['provider_lifecycle']['recheck_until'] ?? 0) > $now) {
            return false;
        }
        if (! is_array($backup) || (($match['result_status'] ?? '') === 'Finished' && ! $race)) {
            return false;
        }
        $retention = (int) config('services.match_lifecycle.'.($race ? 'race_retention_seconds' : 'retention_seconds'));

        return $retention > 0 && $now - (int) $backup['since'] >= $retention
            && ($race || (int) ($backup['checks'] ?? 0) >= (int) config('services.match_lifecycle.cleanup_checks'));
    }

    /** One scheduled pass; canonical rows remain available to existing listeners. */
    public function process(string $sport): int
    {
        [$prefix] = self::SPORTS[$sport];
        $cacheKey = $prefix.':matches';
        $model = 'App\\Models\\'.$prefix.'Match';
        $changed = withRedisLock($cacheKey, function () use ($sport, $prefix, $model, $cacheKey): int {
            return Cache::store('redis')->lock($prefix.':database-write-lock', 120)->block(10, function () use ($sport, $model, $cacheKey): int {
                $matches = getInRedis($cacheKey) ?? [];
                $now = time();
                $tombstoneKey = $cacheKey.':cleaned';
                Redis::zremrangebyscore($tombstoneKey, '-inf', $now);
                $deletedKeys = array_fill_keys(Redis::zrangebyscore($tombstoneKey, $now, '+inf'), true);
                $model::query()->where(static fn ($query) => $query->where('payload->internal_backup->since', '>', 0)->orWhere('payload->provider_lifecycle->resumed_at', '>', 0)->orWhereIn('result_status', self::LEGACY_EXPIRY_STATUSES))->whereNotIn('unique_key', array_keys($matches))->with('markets')->chunkById(100, function ($storedMatches) use (&$matches, $deletedKeys): void {
                    foreach ($storedMatches as $stored) {
                        if (isset($matches[$stored->unique_key]) || isset($deletedKeys[$stored->unique_key])) {
                            continue;
                        }
                        $restored = array_replace($stored->payload ?? [], ['unique_key' => $stored->unique_key, 'timestamp' => $stored->start_timestamp, 'result_status' => $stored->result_status, 'sportsApiPro_match_id' => $stored->sports_api_pro_match_id, 'betfair_match_id' => $stored->betfair_match_id, 'markets' => []]);
                        foreach ($stored->markets as $market) {
                            $restored['markets'][$market->market_id.'_'.$market->market_type][] = $market->payload;
                        }
                        $matches[$stored->unique_key] = $restored;
                    }
                });
                $changes = 0;
                foreach ($matches as $key => &$match) {
                    $previous = $match;
                    $wasHidden = ! empty($match['internal_backup']);
                    $match = $this->transition($match, $sport, $now);
                    if ($this->shouldClean($match, $sport, $now)) {
                        $deleted = DB::transaction(function () use ($model, $key, $sport): bool {
                            $stored = $model::query()->where('unique_key', $key)->lockForUpdate()->first();
                            if ($stored?->result_status === 'Finished' && ! in_array($sport, ['horseracing', 'greyhounds'], true)) {
                                return false;
                            }
                            if ($stored !== null) {
                                $stored->markets()->delete();
                                $stored->delete();
                            }

                            return true;
                        });
                        if ($deleted) {
                            Redis::zadd($tombstoneKey, $now + (int) config('services.match_lifecycle.tombstone_seconds'), (string) $key);
                            unset($matches[$key]);
                            $changes++;
                        }

                        continue;
                    }
                    if ($previous !== $match || ! empty($match['internal_backup'])) {
                        $updates = [
                            'payload->internal_backup' => $match['internal_backup'] ?? null,
                            'payload->provider_lifecycle' => $match['provider_lifecycle'] ?? null,
                        ];
                        if (in_array($previous['result_status'] ?? '', self::LEGACY_EXPIRY_STATUSES, true)) {
                            $updates['result_status'] = 'Live';
                            $updates['minutes_status'] = 'Live';
                            $updates['payload->result_status'] = 'Live';
                            $updates['payload->minutes_status'] = 'Live';
                        }
                        if (($match['result_status'] ?? '') === 'Finished' || ! empty($previous['provider_lifecycle']['resume_pending'])) {
                            $payload = $match;
                            unset($payload['markets']);
                            $updates = ['result_status' => $match['result_status'], 'minutes_status' => $match['minutes_status'] ?? 'Finished', 'payload' => json_encode($payload, JSON_THROW_ON_ERROR)];
                        }
                        $model::query()->where('unique_key', $key)->update($updates);
                    }
                    if ($wasHidden !== ! empty($match['internal_backup'])) {
                        $changes++;
                    }
                }
                unset($match);
                if ($matches !== []) {
                    Redis::setex($cacheKey, 1800, json_encode($matches, JSON_THROW_ON_ERROR));
                } else {
                    Redis::del($cacheKey);
                }

                return $changes;
            });
        }, 5);
        if ($changed > 0) {
            $event = 'App\\Events\\'.$prefix.'SportsScoreUpdated';
            event(new $event);
        }

        return $changed;
    }
}
