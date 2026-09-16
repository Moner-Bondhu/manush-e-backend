<?php

namespace App\Games;

use App\Models\ChildFeatureSnapshot;
use App\Models\GameSession;

class GameFeatureExtractor
{
    public function capture(GameSession $session): void
    {
        $metrics = $session->metrics ?? [];
        $attempts = max((int) ($metrics['attempted_rounds'] ?? 0), 1);
        $correct = min(max((int) ($metrics['correct_choices'] ?? 0), 0), $attempts);
        $accuracy = round(($correct / $attempts) * 100, 3);
        $responses = $session->events()->where('event_type', 'response')->get();
        $latencies = $responses->pluck('payload')->map(fn ($payload) => $payload['response_ms'] ?? null)->filter(fn ($value) => is_numeric($value))->values();
        $consistency = $this->responseConsistency($latencies);
        $domain = match ($session->game->slug) {
            'memory-market' => 'working_memory',
            'mission-maker' => 'planning',
            'goal-street' => 'planning',
            'pet-village' => 'social_emotional',
            'sky-scout' => 'attention',
            'rhythm-river' => 'timing',
            default => 'attention',
        };

        $snapshots = [[
            'profile_id' => $session->profile_id,
            'domain' => $domain,
            'feature' => 'task_accuracy',
            'value' => $accuracy,
            'source_type' => 'game_session',
            'source_id' => $session->id,
            'measured_at' => $session->completed_at,
            'created_at' => now(),
            'updated_at' => now(),
        ]];
        if ($consistency !== null) {
            $snapshots[] = [
                'profile_id' => $session->profile_id,
                'domain' => $domain,
                'feature' => 'response_consistency',
                'value' => $consistency,
                'source_type' => 'game_session',
                'source_id' => $session->id,
                'measured_at' => $session->completed_at,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        ChildFeatureSnapshot::insert($snapshots);
    }

    private function responseConsistency($latencies): ?float
    {
        if ($latencies->count() < 2) {
            return null;
        }
        $average = $latencies->avg();
        $variance = $latencies->map(fn ($latency) => ($latency - $average) ** 2)->avg();

        return round(max(0, 100 - min(100, (sqrt($variance) / max($average, 1)) * 100)), 3);
    }
}
