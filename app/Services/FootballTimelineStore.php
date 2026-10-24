<?php

namespace App\Services;

use App\Jobs\PersistFootballTimeline;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class FootballTimelineStore
{
    public function stage(array $match): void
    {
        $id = (string) ($match['sportsApiPro_match_id'] ?? '');
        $timeline = $match['timeline'] ?? [];
        if (! ctype_digit($id) || (int) $id <= 0 || ! $this->usable($timeline)) {
            return;
        }
        $snapshot = $this->widgetSnapshot([
            'timeline' => $timeline,
            'context' => array_intersect_key($match, array_flip(['result_status', 'timestamp', 'minutes_status', 'added_time', 'current_period_start_timestamp'])),
            'version' => (int) round(max((float) ($timeline['clock_updated_at'] ?? 0), (float) ($timeline['incidents_updated_at'] ?? 0), microtime(true)) * 1000000),
        ]);
        try {
            $pending = Cache::store('redis')->get('Football:timeline:pending:'.$id);
            if (is_array($pending) && $this->fingerprint($pending) === $this->fingerprint($snapshot)) {
                return;
            }
            Cache::store('redis')->put('Football:timeline:pending:'.$id, $snapshot, 600);
            PersistFootballTimeline::dispatch($id, $snapshot)->onQueue('default')->delay(now()->addSeconds(10));
        } catch (Throwable $exception) {
            // Redis/queue failure must not discard the received timeline or interrupt the listener.
            try {
                $this->persist($id, $snapshot);
            } catch (Throwable $failure) {
                Log::warning('Football timeline persistence failed', ['match_id' => $id, 'exception' => $failure::class]);
            }
        }
    }

    /** Keep only the normalized timeline widget fields, including legacy queued snapshots. */
    private function widgetSnapshot(array $snapshot): array
    {
        $timeline = array_intersect_key($snapshot['timeline'] ?? [], array_flip(['events', 'clock', 'removed', 'initialized', 'finalized', 'unplaced_card_counts', 'clock_updated_at', 'incidents_updated_at']));
        $timeline['clock'] = array_intersect_key($timeline['clock'] ?? [], array_flip(['status_code', 'status_type', 'period_start', 'initial', 'max', 'finished', 'paused_at']));
        if (($snapshot['context']['result_status'] ?? '') === 'Finished') {
            $timeline['clock']['finished'] = true;
        }
        if (isset($timeline['events'])) {
            $goals = array_filter($timeline['events'], static fn (array $event): bool => ($event['kind'] ?? '') === 'goal' && isset($event['home_score'], $event['away_score']));
            uasort($goals, static fn (array $first, array $second): int => (($first['seconds'] ?? ($first['minute'] ?? 0) * 60) <=> ($second['seconds'] ?? ($second['minute'] ?? 0) * 60)) ?: strcmp($first['id'], $second['id']));
            $previousScore = [0, 0];
            foreach ($goals as $key => $goal) {
                $score = [(int) $goal['home_score'], (int) $goal['away_score']];
                if ($score[0] === $previousScore[0] + 1 && $score[1] === $previousScore[1]) {
                    $timeline['events'][$key]['is_home'] = true;
                } elseif ($score[1] === $previousScore[1] + 1 && $score[0] === $previousScore[0]) {
                    $timeline['events'][$key]['is_home'] = false;
                }
                $previousScore = $score;
            }
            $eventFields = array_flip(['id', 'kind', 'incident_class', 'minute', 'time_known', 'added_time', 'seconds', 'is_home', 'source', 'player', 'player_surname']);
            $timeline['events'] = array_map(static fn (array $event): array => array_intersect_key($event, $eventFields), $timeline['events']);
        }
        $context = array_intersect_key($snapshot['context'] ?? [], array_flip(['timestamp', 'minutes_status', 'current_period_start_timestamp']));
        if (isset($snapshot['context']['added_time'])) {
            $context['added_time'] = array_intersect_key($snapshot['context']['added_time'], array_flip(['first_half', 'second_half']));
        }

        return ['timeline' => $timeline, 'context' => $context, 'version' => $snapshot['version'] ?? 0];
    }

    private function fingerprint(array $snapshot): string
    {
        $meaningful = array_intersect_key($snapshot, array_flip(['timeline', 'context']));
        unset($meaningful['timeline']['clock_updated_at'], $meaningful['timeline']['incidents_updated_at'], $meaningful['timeline']['change_timestamps']);

        return hash('sha256', json_encode($meaningful, JSON_THROW_ON_ERROR));
    }

    public function usable(mixed $timeline): bool
    {
        return is_array($timeline)
            && (! isset($timeline['events']) || is_array($timeline['events']))
            && (! isset($timeline['clock']) || is_array($timeline['clock']))
            && (is_array($timeline['events'] ?? null) || ! empty($timeline['clock']));
    }

    public function persist(string $id, array $snapshot): void
    {
        if (! ctype_digit($id) || (int) $id <= 0 || ! is_numeric($snapshot['version'] ?? null) || (int) $snapshot['version'] <= 0 || ! $this->usable($snapshot['timeline'] ?? null)) {
            return;
        }
        $snapshot = $this->widgetSnapshot($snapshot);
        DB::transaction(function () use ($id, $snapshot): void {
            $stored = DB::table('football_match_timelines')->where('match_id', $id)->lockForUpdate()->first();
            if ($stored !== null && (int) $stored->version > (int) $snapshot['version']) {
                return;
            }
            if ($stored !== null && $stored->finished_at !== null
                && ! ($snapshot['timeline']['clock']['finished'] ?? false)) {
                return;
            }
            $data = ['timeline' => $snapshot['timeline'], 'context' => $snapshot['context'] ?? []];
            if ($stored !== null) {
                $previous = $this->widgetSnapshot(json_decode($stored->timeline_data, true, 512, JSON_THROW_ON_ERROR));
                $data['timeline'] = array_replace($previous['timeline'] ?? [], $data['timeline']);
                if (isset($previous['timeline']['removed']) || isset($data['timeline']['removed'])) {
                    $data['timeline']['removed'] = array_replace($previous['timeline']['removed'] ?? [], $data['timeline']['removed'] ?? []);
                }
                $data['timeline']['events'] = array_replace($previous['timeline']['events'] ?? [], $data['timeline']['events'] ?? []);
                foreach (array_keys($data['timeline']['removed'] ?? []) as $eventId) {
                    unset($data['timeline']['events'][$eventId]);
                }
                $data['timeline']['clock'] = array_replace($previous['timeline']['clock'] ?? [], $data['timeline']['clock'] ?? []);
                $data['context'] = array_replace($previous['context'] ?? [], $data['context']);
                if ($stored->finished_at !== null) {
                    $data['timeline']['clock']['finished'] = true;
                }
            }
            $hash = $this->fingerprint($data);
            if ($stored !== null && $stored->content_hash === $hash) {
                return;
            }
            $finished = $data['timeline']['clock']['finished'] ?? false;
            $finishedAt = $stored->finished_at ?? ($finished ? now()->toDateTimeString() : null);
            $record = ['timeline_data' => json_encode($data, JSON_THROW_ON_ERROR), 'content_hash' => $hash, 'version' => (int) $snapshot['version'], 'finished_at' => $finishedAt, 'expires_at' => $finishedAt === null ? null : Carbon::parse($finishedAt)->addDays(3)->toDateTimeString(), 'updated_at' => now()];
            if ($stored === null) {
                if (DB::table('football_match_timelines')->insertOrIgnore(['match_id' => $id, 'created_at' => now(), ...$record]) === 0) {
                    $this->persist($id, $snapshot);
                }
            } else {
                DB::table('football_match_timelines')->where('match_id', $id)->where('version', '<=', $snapshot['version'])->update($record);
            }
        }, 3);
    }

    /** Batch fallback, never writes or enqueues work during rendering. */
    public function resolveMany(array $matches): array
    {
        $result = [];
        $missing = [];
        foreach ($matches as $match) {
            $id = (string) ($match['sportsApiPro_match_id'] ?? '');
            if ($this->usable($match['timeline'] ?? null)) {
                $result[$id] = $match['timeline'];
            } elseif ($id !== '') {
                $missing[] = $id;
            }
        }
        if ($missing !== []) {
            try {
                foreach (DB::table('football_match_timelines')->whereIn('match_id', array_unique($missing))->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))->get(['match_id', 'timeline_data']) as $row) {
                    $backup = $this->widgetSnapshot(json_decode($row->timeline_data, true));
                    $result[$row->match_id] = array_merge($backup['timeline'] ?? [], ['render_context' => $backup['context'] ?? []]);
                }
            } catch (Throwable $exception) {
                Log::warning('Football timeline backup unavailable', ['exception' => $exception::class]);
            }
        }

        return $result;
    }

    public function resolve(string $id): array
    {
        try {
            $matches = getInRedis('Football:matches') ?? [];
            $match = array_find($matches, static fn (array $match): bool => (string) ($match['sportsApiPro_match_id'] ?? '') === $id);
        } catch (Throwable) {
            $match = null;
        }

        return $this->resolveMany([$match ?? ['sportsApiPro_match_id' => $id]])[$id] ?? [];
    }

    public function cleanup(): int
    {
        $deleted = 0;
        do {
            $ids = DB::table('football_match_timelines')->whereNotNull('finished_at')->where('expires_at', '<=', now())->limit(500)->pluck('id');
            if ($ids->isEmpty()) {
                break;
            }
            $deleted += DB::table('football_match_timelines')->whereIn('id', $ids)->whereNotNull('finished_at')->where('expires_at', '<=', now())->delete();
        } while ($ids->count() === 500);

        return $deleted;
    }
}
