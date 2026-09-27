<?php

namespace App\Games;

use App\Models\ChildFeatureSnapshot;
use App\Models\GameSession;

class GameFeatureExtractor
{
    private const GAME_DOMAINS = [
        'focus-lab' => ['attention', 'inhibitory_control'],
        'signal-garden' => ['sustained_attention', 'inhibitory_control'],
        'sky-scout' => ['selective_attention', 'processing_speed'],
        'memory-market' => ['working_memory', 'sequencing'],
        'mission-maker' => ['planning', 'sequencing'],
        'goal-street' => ['planning', 'visual_scanning'],
        'rhythm-river' => ['timing', 'working_memory'],
        'pet-village' => ['social_emotional', 'perspective_taking'],
    ];

    public function capture(GameSession $session): void
    {
        $metrics = $session->metrics ?? [];
        $attempts = max((int) ($metrics['attempted_rounds'] ?? 0), 1);
        $correct = min(max((int) ($metrics['correct_choices'] ?? 0), 0), $attempts);
        $accuracy = round(($correct / $attempts) * 100, 3);
        $responses = $session->events()->where('event_type', 'response')->get();
        $latencies = $responses->pluck('payload')
            ->map(fn ($payload) => $payload['response_ms'] ?? null)
            ->filter(fn ($value) => is_numeric($value) && $value >= 100 && $value <= 30000)
            ->map(fn ($value) => (float) $value)
            ->values();
        $consistency = $this->responseConsistency($latencies);

        $features = ['task_accuracy' => $accuracy];
        if ($latencies->isNotEmpty()) {
            $features['median_response_ms'] = round($latencies->median(), 3);
        }
        if ($consistency !== null) {
            $features['response_consistency'] = $consistency;
        }
        $fatigueSlope = $this->fatigueSlope($responses);
        if ($fatigueSlope !== null) {
            $features['late_round_accuracy_change'] = $fatigueSlope;
        }
        $features = array_merge($features, $this->attentionErrors($responses));

        $snapshots = [];
        foreach (self::GAME_DOMAINS[$session->game->slug] ?? ['attention'] as $domain) {
            foreach ($features as $feature => $value) {
                $snapshots[] = $this->snapshot($session, $domain, $feature, $value);
            }
        }

        ChildFeatureSnapshot::insert($snapshots);
    }

    private function snapshot(GameSession $session, string $domain, string $feature, float $value): array
    {
        return [
            'profile_id' => $session->profile_id,
            'domain' => $domain,
            'feature' => $feature,
            'value' => round($value, 3),
            'source_type' => 'game_session',
            'source_id' => $session->id,
            'measured_at' => $session->completed_at,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function attentionErrors($responses): array
    {
        $signalResponses = $responses->filter(fn ($response) => in_array($response->payload['prompt'] ?? null, ['light', 'thorn'], true));
        if ($signalResponses->isEmpty()) {
            return [];
        }

        $targets = $signalResponses->filter(fn ($response) => $response->payload['prompt'] === 'light');
        $nonTargets = $signalResponses->filter(fn ($response) => $response->payload['prompt'] === 'thorn');
        $features = [];
        if ($targets->isNotEmpty()) {
            $misses = $targets->filter(fn ($response) => ! ($response->payload['correct'] ?? false))->count();
            $features['omission_rate'] = ($misses / $targets->count()) * 100;
        }
        if ($nonTargets->isNotEmpty()) {
            $falseAlarms = $nonTargets->filter(fn ($response) => ! ($response->payload['correct'] ?? false))->count();
            $features['commission_rate'] = ($falseAlarms / $nonTargets->count()) * 100;
        }

        return $features;
    }

    private function fatigueSlope($responses): ?float
    {
        if ($responses->count() < 6) {
            return null;
        }

        $ordered = $responses->sortBy(fn ($response) => (int) ($response->payload['round'] ?? 0))->values();
        $half = intdiv($ordered->count(), 2);
        $early = $ordered->take($half);
        $late = $ordered->slice($half);
        $accuracy = fn ($items) => $items->filter(fn ($response) => (bool) ($response->payload['correct'] ?? false))->count() / max($items->count(), 1);

        return round(($accuracy($late) - $accuracy($early)) * 100, 3);
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
