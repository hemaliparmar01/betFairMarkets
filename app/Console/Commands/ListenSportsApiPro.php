<?php

namespace App\Console\Commands;

use Amp\Websocket\Client\WebsocketHandshake;
use App\Events\FootballSportsScoreUpdated;
use Illuminate\Console\Command;
use Revolt\EventLoop;
use Throwable;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use function Amp\delay;
use function Amp\Websocket\Client\connect;

class ListenSportsApiPro extends Command
{
    protected $signature = 'sports:listen';
    protected $description = 'Listen to SportsAPIPro football updates';

    public function handle(): int
    {
        $key = GetSportApiProKey();

        if (! $key) {
            $this->error('SPORTS_API_PRO_KEY is missing.');
            return self::FAILURE;
        }

        $backoff = 1;

        while (true) {
            $pingTimer = null;
            $subscriptionRefreshTimer = null;
            $incidentSubscriptions = [];
            $statsSubscriptions = [];

            try {
                $handshake = (new WebsocketHandshake('wss://api.sportsapipro.com/v2/football/ws'))->withHeader('x-api-key', $key);

                $socket = connect($handshake);
                $backoff = 1;

                $socket->sendText(json_encode([
                    'action' => 'subscribe',
                    'channel' => 'live-scores',
                ], JSON_THROW_ON_ERROR));

                $this->info('Connected; subscribed to live-scores.');

                $pingTimer = EventLoop::repeat(30, function () use ($socket): void {
                    $socket->sendText(json_encode([
                        'action' => 'ping',
                        'channel' => 'live-scores',
                    ], JSON_THROW_ON_ERROR));
                });

                $subscriptionRefreshTimer = EventLoop::repeat(30, function () use ($socket, &$incidentSubscriptions, &$statsSubscriptions): void {
                    $staleBefore = time() - 90;

                    foreach (['incidents' => &$incidentSubscriptions, 'stats' => &$statsSubscriptions] as $suffix => &$subscriptions) {
                        foreach ($subscriptions as $matchId => $lastFrameAt) {
                            if ($lastFrameAt > $staleBefore) {
                                continue;
                            }

                            try {
                                $channel = "match:{$matchId}:{$suffix}";
                                $socket->sendText(json_encode([
                                    'action' => 'unsubscribe',
                                    'channel' => $channel,
                                ], JSON_THROW_ON_ERROR));
                                $socket->sendText(json_encode([
                                    'action' => 'subscribe',
                                    'channel' => $channel,
                                ], JSON_THROW_ON_ERROR));
                                $subscriptions[$matchId] = time();
                            } catch (Throwable $e) {
                                Log::warning('SportsAPI Pro stale subscription refresh failed', [
                                    'match_id' => $matchId,
                                    'channel' => $suffix,
                                    'message' => $e->getMessage(),
                                ]);
                            }
                        }
                    }
                });

                foreach ($socket as $message) {
                    $frame = json_decode($message->buffer(), true);

                    if (! is_array($frame)) {
                        continue;
                    }

                    $type = $frame['type'] ?? 'unknown';
                    Log::info("TYPE :  ".$type);

                    if (in_array($type, ['snapshot', 'update'], true)) {
                        $frameData = $frame['data'] ?? null;
                        $channel = $frame['channel'] ?? '';

                        Log::info('SportsAPI Pro WebSocket event received', [
                            'type' => $type,
                            'channel' => $channel,
                            'timestamp' => $frame['timestamp'] ?? null,
                            'data' => $frameData,
                        ]);

                        if (preg_match('/^match:(\d+):incidents$/', $channel, $channelMatch)) {
                            $incidentSubscriptions[$channelMatch[1]] = time();
                        } elseif (preg_match('/^match:(\d+):stats$/', $channel, $channelMatch)) {
                            $statsSubscriptions[$channelMatch[1]] = time();
                        }

                        $liveEvents = str_starts_with($channel, 'live-scores')
                            ? $this->normalizeLiveEvents($frameData)
                            : [];
                        $syncedMatchIds = $this->syncedMatchIds();

                        foreach ($liveEvents as $liveEvent) {
                            $matchId = $liveEvent['id'] ?? $liveEvent['eventId'] ?? null;
                            if ($matchId === null || ! isset($syncedMatchIds[(string) $matchId])) {
                                continue;
                            }

                            if ($this->eventValue($liveEvent, 'status.code') == 100) {
                                foreach (["match:{$matchId}:incidents", "match:{$matchId}:stats"] as $subscriptionChannel) {
                                    $socket->sendText(json_encode([
                                        'action' => 'unsubscribe',
                                        'channel' => $subscriptionChannel,
                                    ], JSON_THROW_ON_ERROR));
                                }
                                unset($incidentSubscriptions[$matchId], $statsSubscriptions[$matchId]);
                                continue;
                            }

                            if (! isset($incidentSubscriptions[$matchId])) {
                                $socket->sendText(json_encode([
                                    'action' => 'subscribe',
                                    'channel' => "match:{$matchId}:incidents",
                                ], JSON_THROW_ON_ERROR));
                                $incidentSubscriptions[$matchId] = time();
                            }

                            if (! isset($statsSubscriptions[$matchId])) {
                                $socket->sendText(json_encode([
                                    'action' => 'subscribe',
                                    'channel' => "match:{$matchId}:stats",
                                ], JSON_THROW_ON_ERROR));
                                $statsSubscriptions[$matchId] = time();
                            }
                        }

                        $this->updateFootballCache($channel, $frameData);

                        Event::dispatch(new FootballSportsScoreUpdated());
                    }
                }
            } catch (Throwable $e) {
                $this->error('WebSocket disconnected: '.$e->getMessage());
            } finally {
                if ($pingTimer !== null) {
                    EventLoop::cancel($pingTimer);
                }
                if ($subscriptionRefreshTimer !== null) {
                    EventLoop::cancel($subscriptionRefreshTimer);
                }
            }

            delay($backoff);
            $backoff = min($backoff * 2, 30);
        }
    }

    private function normalizeLiveEvents(mixed $frameData): array
    {
        if (! is_array($frameData)) {
            return [];
        }

        if (isset($frameData['id'])) {
            return [$frameData];
        }

        if (isset($frameData['events']) && is_array($frameData['events'])) {
            return $frameData['events'];
        }

        return array_is_list($frameData) ? $frameData : [];
    }

    private function updateFootballCache(string $channel, mixed $frameData): void
    {
        $matches = getInRedis('Football:matches');
        if (empty($matches) || ! is_array($frameData)) {
            return;
        }

        if (str_starts_with($channel, 'live-scores')) {
            foreach ($this->normalizeLiveEvents($frameData) as $event) {
                $this->updateLiveMatch($matches, $event, $channel);
            }
        } elseif (preg_match('/^match:(\d+):incidents$/', $channel, $channelMatch)) {
            $this->updateMatchIncidents($matches, $channelMatch[1], $frameData, $channel);
        } elseif (preg_match('/^match:(\d+):stats$/', $channel, $channelMatch)) {
            $this->updateMatchCorners($matches, $channelMatch[1], $frameData, $channel);
        }

        foreach ($matches as &$match) {
            unset($match['events'], $match['goals'], $match['goal_counts'], $match['status_description']);
            if (($match['minutes_status'] ?? null) === 'Finished') {
                $match['result_status'] = 'Finished';
            } elseif (! in_array($match['result_status'] ?? null, ['Live', 'Prematch', 'Finished'], true)) {
                $match['result_status'] = match ($match['minutes_status'] ?? null) {
                    'Prematch' => 'Prematch',
                    'Finished' => 'Finished',
                    default => 'Live',
                };
            }

            $match['minutes_status'] = match ($match['result_status']) {
                'Finished' => 'Finished',
                'Prematch' => 'Prematch',
                'Live' => in_array($match['minutes_status'] ?? null, ['Live', '1st half', '2nd half'], true)
                    ? $match['minutes_status']
                    : 'Live',
            };
        }
        unset($match);

        storeInRedis('Football:matches', $matches, 10);
    }

    private function updateLiveMatch(array &$matches, array $event, string $channel): void
    {
        $matchId = $event['id'] ?? $event['eventId'] ?? null;
        if ($matchId === null) {
            return;
        }

        foreach ($matches as &$match) {
            if (($match['data_sync_complete'] ?? false) !== true || ($match['sportsApiPro_match_id'] ?? null) != $matchId) {
                continue;
            }

            if ($this->isDuplicateChange($match, $event, $channel)) {
                return;
            }

            $homeScore = $this->eventValue($event, 'homeScore.current');
            if ($homeScore !== null) {
                $match['home_score'] = $homeScore;
            }

            $awayScore = $this->eventValue($event, 'awayScore.current');
            if ($awayScore !== null) {
                $match['away_score'] = $awayScore;
            }

            $statusCode = $this->eventValue($event, 'status.code');
            $statusType = $this->eventValue($event, 'status.type');
            if ($statusCode == 6 && ($match['result_status'] ?? null) !== 'Finished') {
                $match['minutes_status'] = '1st half';
                $match['result_status'] = 'Live';
            } elseif ($statusCode == 7 && ($match['result_status'] ?? null) !== 'Finished') {
                $match['minutes_status'] = '2nd half';
                $match['result_status'] = 'Live';
            } elseif ($statusCode == 31 && ($match['result_status'] ?? null) !== 'Finished') {
                $match['minutes_status'] = 'Live';
                $match['result_status'] = 'Live';
            } elseif ($statusCode == 100 || $statusType === 'finished') {
                $match['minutes_status'] = 'Finished';
                $match['result_status'] = 'Finished';
            } elseif ($statusCode !== null && ! in_array((int) $statusCode, [6, 7, 31], true)) {
                if (! in_array($match['minutes_status'] ?? null, ['Live', 'Prematch', 'Finished', '1st half', '2nd half'], true)) {
                    $match['minutes_status'] = 'Live';
                }
                Log::warning('Unhandled SportsAPI Pro football status', [
                    'match_id' => $matchId,
                    'status_code' => $statusCode,
                    // 'status_type' => $statusType,
                ]);
            }

            // $match['status_type'] = $statusType;
            unset($match['status_description']);
            return;
        }
        unset($match);
    }

    private function updateMatchIncidents(array &$matches, int|string $matchId, array $frameData, string $channel): void
    {
        foreach ($matches as &$match) {
            if (($match['data_sync_complete'] ?? false) !== true || ($match['sportsApiPro_match_id'] ?? null) != $matchId) {
                continue;
            }

            if ($this->isDuplicateChange($match, $frameData, $channel)) {
                return;
            }

            $incidents = $this->normalizeIncidents($frameData);
            $cards = ['home' => null, 'away' => null];
            $cardCounts = [
                'home' => ['yellow' => 0, 'direct_red' => 0, 'second_yellow' => 0, 'total_red' => 0],
                'away' => ['yellow' => 0, 'direct_red' => 0, 'second_yellow' => 0, 'total_red' => 0],
            ];
            $addedTime = ['first_half' => null, 'second_half' => null];
            $latestGoal = null;

            foreach ($incidents as $incident) {
                $incidentType = $incident['incidentType'] ?? null;

                if ($incidentType === 'goal') {
                    if ($latestGoal === null || ($incident['timeSeconds'] ?? 0) > ($latestGoal['timeSeconds'] ?? 0)) {
                        $latestGoal = $incident;
                    }
                    continue;
                }

                if ($incidentType === 'injuryTime' && isset($incident['length'], $incident['time'])) {
                    $period = $incident['time'] <= 45 ? 'first_half' : 'second_half';
                    $addedTime[$period] = (int) $incident['length'];
                    continue;
                }

                if ($incidentType !== 'card' || ! array_key_exists('isHome', $incident)) {
                    continue;
                }

                $incidentClass = $incident['incidentClass'] ?? null;
                if (! in_array($incidentClass, ['yellow', 'red', 'yellowRed'], true)) {
                    continue;
                }

                $side = $incident['isHome'] ? 'home' : 'away';
                if ($incidentClass === 'yellow') {
                    $cardCounts[$side]['yellow']++;
                } elseif ($incidentClass === 'red') {
                    $cardCounts[$side]['direct_red']++;
                    $cardCounts[$side]['total_red']++;
                } else {
                    $cardCounts[$side]['second_yellow']++;
                    $cardCounts[$side]['total_red']++;
                }

                if (in_array($incidentClass, ['red', 'yellowRed'], true)) {
                    $cards[$side] = 'red';
                } elseif ($cards[$side] === null) {
                    $cards[$side] = $incidentClass;
                }
            }

            if ($latestGoal !== null) {
                $latestGoalId = (string) ($latestGoal['id'] ?? implode(':', [
                    $latestGoal['timeSeconds'] ?? 0,
                    $latestGoal['homeScore'] ?? 0,
                    $latestGoal['awayScore'] ?? 0,
                    ! empty($latestGoal['isHome']) ? 'home' : 'away',
                ]));
                $previousGoal = $match['last_goal'] ?? null;
                $previousScoreTotal = ($previousGoal['home_score'] ?? 0) + ($previousGoal['away_score'] ?? 0);
                $latestScoreTotal = ($latestGoal['homeScore'] ?? 0) + ($latestGoal['awayScore'] ?? 0);

                if (
                    ($match['incidents_initialized'] ?? false) === true
                    && ($previousGoal['id'] ?? null) !== $latestGoalId
                    && ($previousGoal === null || $latestScoreTotal > $previousScoreTotal)
                ) {
                    $match['goal_highlight_until'] = time() + 40;
                }

                $match['last_goal'] = [
                    'id' => $latestGoalId,
                    'is_home' => (bool) ($latestGoal['isHome'] ?? false),
                    'time' => $latestGoal['time'] ?? null,
                    'home_score' => $latestGoal['homeScore'] ?? null,
                    'away_score' => $latestGoal['awayScore'] ?? null,
                ];

                if (isset($latestGoal['homeScore'])) {
                    $match['home_score'] = $latestGoal['homeScore'];
                }
                if (isset($latestGoal['awayScore'])) {
                    $match['away_score'] = $latestGoal['awayScore'];
                }
            } else {
                unset($match['last_goal']);
            }

            $match['incidents_initialized'] = true;
            $match['cards'] = $cards;
            $match['card_counts'] = $cardCounts;
            $match['added_time'] = $addedTime;
            return;
        }
        unset($match);
    }

    private function normalizeIncidents(array $frameData): array
    {
        if (isset($frameData['incidents']) && is_array($frameData['incidents'])) {
            return $frameData['incidents'];
        }

        if (isset($frameData['events']) && is_array($frameData['events'])) {
            return $frameData['events'];
        }

        if (array_is_list($frameData)) {
            return $frameData;
        }

        if (! isset($frameData['incidentType'])) {
            return [];
        }

        return [$frameData];
    }

    private function updateMatchCorners(array &$matches, int|string $matchId, array $frameData, string $channel): void
    {
        foreach ($matches as &$match) {
            if (($match['data_sync_complete'] ?? false) !== true || ($match['sportsApiPro_match_id'] ?? null) != $matchId) {
                continue;
            }

            if ($this->isDuplicateChange($match, $frameData, $channel)) {
                return;
            }

            $corners = $this->findCorners($frameData);
            if ($corners !== null) {
                $match['corners'] = $corners;
            }
            return;
        }
        unset($match);
    }

    private function findCorners(array $data): ?array
    {
        $name = strtolower((string) ($data['name'] ?? $data['statisticName'] ?? ''));
        if (str_contains($name, 'corner')) {
            $home = $data['home'] ?? $data['homeValue'] ?? null;
            $away = $data['away'] ?? $data['awayValue'] ?? null;

            if (is_numeric($home) && is_numeric($away)) {
                return ['home' => (int) $home, 'away' => (int) $away];
            }
        }

        foreach ($data as $value) {
            if (! is_array($value)) {
                continue;
            }

            $corners = $this->findCorners($value);
            if ($corners !== null) {
                return $corners;
            }
        }

        return null;
    }

    private function syncedMatchIds(): array
    {
        $matches = getInRedis('Football:matches');
        $matchIds = [];

        foreach ($matches ?? [] as $match) {
            if (($match['data_sync_complete'] ?? false) !== true || ! isset($match['sportsApiPro_match_id'])) {
                continue;
            }
            $matchIds[(string) $match['sportsApiPro_match_id']] = true;
        }

        return $matchIds;
    }

    private function eventValue(array $event, string $key): mixed
    {
        if (array_key_exists($key, $event)) {
            return $event[$key];
        }

        $value = $event;
        foreach (explode('.', $key) as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return null;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    private function isDuplicateChange(array &$match, array $data, string $channel): bool
    {
        $changeTimestamp = $this->eventValue($data, 'changes.changeTimestamp');
        if ($changeTimestamp === null) {
            return false;
        }

        $lastChangeTimestamp = $match['websocket_change_timestamps'][$channel] ?? null;
        if ($lastChangeTimestamp !== null && $changeTimestamp <= $lastChangeTimestamp) {
            return true;
        }

        $match['websocket_change_timestamps'][$channel] = $changeTimestamp;
        return false;
    }
}
