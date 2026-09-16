<?php

namespace App\Games;

use App\Models\GameDefinition;
use App\Models\GameLevel;
use App\Models\GameProgress;
use App\Models\GameSession;

class GameProgressionService
{
    public function start(GameDefinition $game, int $profileId): array
    {
        $progress = GameProgress::firstOrCreate(
            ['profile_id' => $profileId, 'game_definition_id' => $game->id],
            ['current_level' => 1, 'unlocked_level' => 1]
        );
        if (!$progress->current_level) {
            $progress->update(['current_level' => 1, 'unlocked_level' => 1]);
            $progress->refresh();
        }
        $level = GameLevel::where('game_definition_id', $game->id)->where('number', $progress->current_level)->first()
            ?? GameLevel::where('game_definition_id', $game->id)->orderBy('number')->first();
        return [$progress, $level];
    }

    public function complete(GameSession $session): ?GameProgress
    {
        $progress = GameProgress::firstOrCreate(
            ['profile_id' => $session->profile_id, 'game_definition_id' => $session->game_definition_id],
            ['current_level' => 1, 'unlocked_level' => 1]
        );
        if (!$progress->current_level) {
            $progress->update(['current_level' => 1, 'unlocked_level' => 1]);
            $progress->refresh();
        }
        $rule = GameLevel::where('game_definition_id', $session->game_definition_id)->where('number', $session->level)->value('mastery_rule') ?? [];
        $metrics = $session->metrics ?? [];
        $attempts = max(1, (int) ($metrics['attempted_rounds'] ?? 0));
        $accuracy = ((int) ($metrics['correct_choices'] ?? 0)) / $attempts;
        $history = array_slice(array_merge($progress->mastery_history ?? [], [[
            'session_id' => $session->id, 'level' => $session->level, 'accuracy' => round($accuracy, 3), 'completed_at' => now()->toIso8601String(),
        ]]), -3);
        $threshold = (float) ($rule['accuracy'] ?? 0.75);
        $wins = collect($history)->where('level', $session->level)->filter(fn ($entry) => $entry['accuracy'] >= $threshold)->count();
        $maxLevel = (int) GameLevel::where('game_definition_id', $session->game_definition_id)->max('number');
        $advance = $wins >= 2 && $session->level < $maxLevel;
        $progress->update([
            'current_level' => $advance ? $session->level + 1 : $session->level,
            'unlocked_level' => max($progress->unlocked_level, $advance ? $session->level + 1 : $session->level),
            'mastery_history' => $history,
            'last_played_at' => now(),
        ]);
        return $progress->fresh();
    }
}
