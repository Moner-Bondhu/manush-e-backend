<?php

namespace Database\Seeders;

use App\Models\GameDefinition;
use App\Models\GameLevel;
use Illuminate\Database\Seeder;

class ExpandedGameLibrarySeeder extends Seeder
{
    public function run(): void
    {
        $games = [
            ['slug' => 'pattern-forge', 'title' => 'Pattern Forge', 'description' => 'Repair changing symbol patterns in a glowing workshop.', 'theme' => 'forge', 'skill_tags' => ['Patterns', 'Reasoning'], 'duration_seconds' => 210, 'sort_order' => 9],
            ['slug' => 'route-builder', 'title' => 'Route Builder', 'description' => 'Choose a route that follows every mission rule.', 'theme' => 'route', 'skill_tags' => ['Planning', 'Reasoning'], 'duration_seconds' => 240, 'sort_order' => 10],
            ['slug' => 'emotion-detective', 'title' => 'Emotion Detective', 'description' => 'Read the clues in everyday moments and explore what someone may feel.', 'theme' => 'detective', 'skill_tags' => ['Emotions', 'Empathy'], 'duration_seconds' => 240, 'sort_order' => 11],
            ['slug' => 'story-lantern', 'title' => 'Story Lantern', 'description' => 'Build thoughtful endings by seeing a story from more than one side.', 'theme' => 'lantern', 'skill_tags' => ['Perspective', 'Imagination'], 'duration_seconds' => 270, 'sort_order' => 12],
            ['slug' => 'number-harbor', 'title' => 'Number Harbor', 'description' => 'Load boats by finding quantities, number bonds, and useful rules.', 'theme' => 'harbor', 'skill_tags' => ['Numbers', 'Reasoning'], 'duration_seconds' => 210, 'sort_order' => 13],
        ];

        foreach ($games as $game) {
            $definition = GameDefinition::updateOrCreate(['slug' => $game['slug']], array_merge($game, ['status' => 'published']));
            foreach (range(1, 12) as $level) {
                $parameters = match ($game['slug']) {
                    'pattern-forge' => ['rounds' => min(8, 4 + intdiv($level, 2)), 'pattern_depth' => min(6, 3 + intdiv($level, 3)), 'rule_changes' => intdiv($level, 4)],
                    'route-builder' => ['rounds' => min(8, 4 + intdiv($level, 2)), 'constraints' => min(3, 1 + intdiv($level, 4)), 'decoy_routes' => min(3, 1 + intdiv($level, 3))],
                    'emotion-detective' => ['scenarios' => min(8, 4 + intdiv($level, 2)), 'clue_count' => min(4, 1 + intdiv($level, 3)), 'mixed_emotions' => $level >= 7],
                    'story-lantern' => ['scenarios' => min(8, 4 + intdiv($level, 2)), 'perspectives' => min(3, 1 + intdiv($level, 4)), 'consequence_depth' => min(3, 1 + intdiv($level, 5))],
                    default => ['rounds' => min(8, 4 + intdiv($level, 2)), 'number_range' => 10 + ($level * 5), 'operations' => min(3, 1 + intdiv($level, 4))],
                };
                GameLevel::updateOrCreate(
                    ['game_definition_id' => $definition->id, 'number' => $level],
                    [
                        'chapter' => $level <= 4 ? 'explore' : ($level <= 8 ? 'build' : 'master'),
                        'parameters' => $parameters,
                        'mastery_rule' => ['accuracy' => min(0.88, 0.68 + ($level * 0.015))],
                    ]
                );
            }
        }
    }
}
