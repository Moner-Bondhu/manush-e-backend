<?php

namespace Database\Seeders;

use App\Models\Demography;
use App\Models\GameDefinition;
use App\Models\GameLevel;
use App\Models\LearningGroup;
use App\Models\Profile;
use App\Models\Scale;
use App\Models\User;
use Illuminate\Database\Seeder;

class LocalDemoSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['phone_number' => '01700000000'],
            [
                'name' => 'Local Demo User',
                'experiment_tag' => 'local_demo',
                'user_type' => 'user',
                'is_onboarded' => true,
            ]
        );

        $child = Profile::updateOrCreate(
            ['user_id' => $user->id, 'type' => 'child'],
            ['full_name' => 'Demo Child', 'relation_type' => 'child']
        );
        $parent = Profile::updateOrCreate(
            ['user_id' => $user->id, 'type' => 'parent'],
            ['full_name' => 'Demo Parent', 'relation_type' => 'guardian']
        );

        Demography::updateOrCreate(
            ['profile_id' => $child->id],
            ['dob' => '2014-01-01', 'gender' => 'other', 'grade' => 'Demo']
        );
        Demography::updateOrCreate(
            ['profile_id' => $parent->id],
            ['dob' => '1990-01-01', 'gender' => 'other', 'grade' => 'N/A']
        );

        $scale = Scale::updateOrCreate(
            ['name' => 'Local Demo Check-In', 'visible_to' => 'child'],
            ['description' => 'Sample local questions for testing the application flow. Not a scored or diagnostic assessment.']
        );

        $questions = [
            ['text' => 'How was your day today?', 'options' => ['Great', 'Okay', 'Not so good']],
            ['text' => 'Which activity would you like to do?', 'options' => ['Draw', 'Read', 'Play outside', 'Talk with someone']],
            ['text' => 'How easy was it to focus today?', 'options' => ['Easy', 'A little hard', 'Hard']],
            ['text' => 'What would help you feel supported today?', 'options' => ['A break', 'A chat', 'A fun activity', 'Nothing right now']],
        ];

        foreach ($questions as $index => $questionData) {
            $question = $scale->questions()->updateOrCreate(
                ['order' => $index + 1],
                ['text' => $questionData['text'], 'type' => 'select_one', 'subtext' => null, 'image' => null]
            );

            foreach ($questionData['options'] as $optionIndex => $optionText) {
                // Preserve option IDs because submitted responses reference them.
                $question->options()->updateOrCreate(
                    ['order' => $optionIndex + 1],
                    [
                        'text' => $optionText,
                        'is_image' => false,
                        'value' => $optionIndex + 1,
                    ]
                );
            }
        }

        $assessmentPacks = [
            [
                'name' => 'School Moments / স্কুলের মুহূর্ত',
                'description' => 'Situation-based questions about everyday school moments and choices.',
                'questions' => [
                    ['text' => 'During group work, you notice that no one has asked for your idea yet. What would you most likely do? / দলগত কাজের সময় কেউ তোমার মতামত জানতে চায়নি। তুমি কী করবে?', 'type' => 'select_one', 'options' => ['Share one idea / একটি মতামত বলব', 'Ask to join a task / একটি কাজ চাইব', 'Wait quietly / চুপচাপ অপেক্ষা করব', 'Ask an adult for help / বড় কারও সাহায্য চাইব']],
                    ['text' => 'You have a big task due tomorrow and feel stuck. Which first step feels most possible? / আগামীকাল বড় কাজ জমা দিতে হবে এবং তুমি আটকে গেছ। প্রথম কোন কাজটি করা সম্ভব?', 'type' => 'select_one', 'options' => ['Break it into small steps / ছোট ধাপে ভাগ করব', 'Ask someone to explain it / কাউকে বুঝিয়ে বলতে বলব', 'Take a short break then return / একটু বিরতি নিয়ে ফিরব', 'Leave it for later / পরে করব']],
                    ['text' => 'At break time, your friends are playing a game you do not enjoy. What could help? / বিরতিতে বন্ধুরা এমন খেলা খেলছে যা তোমার ভালো লাগে না। কী সাহায্য করতে পারে?', 'type' => 'select_multiple', 'options' => ['Suggest another game / অন্য খেলার কথা বলব', 'Join for a little while / কিছুক্ষণ যোগ দেব', 'Find a quiet activity / শান্ত কোনও কাজ করব', 'Talk to a friend / বন্ধুর সঙ্গে কথা বলব']],
                    ['text' => 'How manageable did school feel this week? / এই সপ্তাহে স্কুল কতটা সামলানো সম্ভব মনে হয়েছে?', 'type' => 'slider', 'options' => []],
                ],
            ],
            [
                'name' => 'Think It Through / ভেবে বলো',
                'description' => 'Open-ended situations that invite ideas, planning, and reflection.',
                'questions' => [
                    ['text' => 'A friend looks upset after a mistake in class. What could you say or do to help? / ক্লাসে ভুল করার পর একজন বন্ধুকে মন খারাপ দেখাচ্ছে। তুমি কী বলতে বা করতে পারো?', 'type' => 'text', 'options' => []],
                    ['text' => 'You need to finish three things before tomorrow. How would you make a simple plan? / আগামীকালের আগে তোমাকে তিনটি কাজ শেষ করতে হবে। তুমি কীভাবে সহজ পরিকল্পনা করবে?', 'type' => 'text', 'options' => []],
                    ['text' => 'Describe a time when you kept trying at something difficult. What helped you continue? / কঠিন কোনও কাজে চেষ্টা চালিয়ে যাওয়ার সময়ের কথা বলো। কী তোমাকে চালিয়ে যেতে সাহায্য করেছিল?', 'type' => 'text', 'options' => []],
                    ['text' => 'When your mind feels busy, what helps you return to one task? / মন ব্যস্ত লাগলে একটি কাজে ফিরতে কী সাহায্য করে?', 'type' => 'text', 'options' => []],
                ],
            ],
            [
                'name' => 'Daily Rhythm / দৈনন্দিন ছন্দ',
                'description' => 'A short reflection on energy, focus, rest, and connection.',
                'questions' => [
                    ['text' => 'How much energy do you have today? / আজ তোমার শক্তি কেমন?', 'type' => 'slider', 'options' => []],
                    ['text' => 'Which part of today felt best? / আজকের কোন অংশটি সবচেয়ে ভালো লেগেছে?', 'type' => 'text', 'options' => []],
                    ['text' => 'What would make tomorrow a little easier? / আগামীকালকে একটু সহজ করতে কী সাহায্য করবে?', 'type' => 'text', 'options' => []],
                ],
            ],
        ];

        foreach ($assessmentPacks as $pack) {
            $assessmentScale = Scale::updateOrCreate(
                ['name' => $pack['name'], 'visible_to' => 'child'],
                ['description' => $pack['description']]
            );

            foreach ($pack['questions'] as $index => $questionData) {
                $question = $assessmentScale->questions()->updateOrCreate(
                    ['order' => $index + 1],
                    ['text' => $questionData['text'], 'type' => $questionData['type'], 'subtext' => null, 'image' => null]
                );

                foreach ($questionData['options'] as $optionIndex => $optionText) {
                    $question->options()->updateOrCreate(
                        ['order' => $optionIndex + 1],
                        ['text' => $optionText, 'is_image' => false, 'value' => $optionIndex + 1]
                    );
                }
            }
        }

        $games = [
            [
                'slug' => 'focus-lab',
                'title' => 'Focus Lab',
                'description' => 'Spot the matching shape in a short attention challenge.',
                'theme' => 'grove',
                'skill_tags' => ['Focus', 'Practice'],
                'duration_seconds' => 180,
                'sort_order' => 1,
            ],
            [
                'slug' => 'memory-market',
                'title' => 'Memory Market',
                'description' => 'Build a delivery list and remember each item on the way.',
                'theme' => 'coral',
                'skill_tags' => ['Memory', 'Strategy'],
                'duration_seconds' => 240,
                'sort_order' => 2,
            ],
            [
                'slug' => 'signal-garden',
                'title' => 'Signal Garden',
                'description' => 'Keep the garden bright by catching signals at the right moment.',
                'theme' => 'violet',
                'skill_tags' => ['Attention', 'Speed'],
                'duration_seconds' => 180,
                'sort_order' => 3,
            ],
            [
                'slug' => 'mission-maker',
                'title' => 'Mission Maker',
                'description' => 'Choose the next tool and keep a tiny project moving.',
                'theme' => 'maker',
                'skill_tags' => ['Planning', 'Strategy'],
                'duration_seconds' => 210,
                'sort_order' => 4,
            ],
            [
                'slug' => 'rhythm-river',
                'title' => 'Rhythm River',
                'description' => 'Follow a flowing rhythm and choose the matching mark.',
                'theme' => 'river',
                'skill_tags' => ['Timing', 'Focus'],
                'duration_seconds' => 180,
                'sort_order' => 5,
            ],
            [
                'slug' => 'goal-street',
                'title' => 'Goal Street',
                'description' => 'Scan the pitch and choose the open passing lane.',
                'theme' => 'pitch',
                'skill_tags' => ['Planning', 'Attention'],
                'duration_seconds' => 180,
                'sort_order' => 6,
            ],
            [
                'slug' => 'sky-scout',
                'title' => 'Sky Scout',
                'description' => 'Scout the sky and find the right signal before it moves on.',
                'theme' => 'sky',
                'skill_tags' => ['Attention', 'Speed'],
                'duration_seconds' => 180,
                'sort_order' => 7,
            ],
            [
                'slug' => 'pet-village',
                'title' => 'Pet Village',
                'description' => 'Notice what village pets need and choose a helpful next step.',
                'theme' => 'pet',
                'skill_tags' => ['Empathy', 'Strategy'],
                'duration_seconds' => 240,
                'sort_order' => 8,
            ],
        ];

        foreach ($games as $game) {
            $definition = GameDefinition::updateOrCreate(['slug' => $game['slug']], $game);
            foreach (range(1, 12) as $level) {
                $chapter = $level <= 4 ? 'explore' : ($level <= 8 ? 'build' : 'master');
                $parameters = match ($game['slug']) {
                    'focus-lab' => ['rounds' => 8 + $level, 'target_ratio' => max(0.35, 0.7 - ($level * 0.02)), 'speed_ms' => max(800, 1900 - ($level * 80))],
                    'memory-market' => ['sequence_length' => min(8, 2 + intdiv($level + 1, 2)), 'interference_items' => intdiv($level, 3)],
                    'signal-garden' => ['rounds' => 8 + $level, 'rule_switches' => intdiv($level, 3), 'target_ratio' => max(0.3, 0.65 - ($level * 0.02))],
                    'mission-maker' => ['steps' => min(7, 2 + intdiv($level + 1, 2)), 'decoy_tools' => intdiv($level, 2)],
                    'goal-street' => ['plays' => 6 + $level, 'open_lanes' => max(1, 3 - intdiv($level, 5)), 'defender_speed' => 1 + intdiv($level, 3)],
                    'sky-scout' => ['scenes' => 6 + $level, 'distractors' => 3 + intdiv($level, 2), 'motion_ms' => max(900, 2200 - ($level * 90))],
                    'pet-village' => ['scenarios' => min(6, 2 + intdiv($level + 1, 2)), 'perspectives' => min(3, 1 + intdiv($level, 4))],
                    default => ['pattern_length' => min(8, 2 + intdiv($level + 1, 2)), 'tempo_bpm' => 70 + ($level * 6)],
                };
                GameLevel::updateOrCreate(
                    ['game_definition_id' => $definition->id, 'number' => $level],
                    ['chapter' => $chapter, 'parameters' => $parameters, 'mastery_rule' => ['accuracy' => min(0.9, 0.7 + ($level * 0.015))]]
                );
            }
        }

        $teacher = User::updateOrCreate(
            ['phone_number' => '01700000001'],
            ['name' => 'Local Teacher', 'experiment_tag' => 'local_demo', 'user_type' => 'teacher', 'is_onboarded' => true]
        );
        $group = LearningGroup::updateOrCreate(
            ['teacher_user_id' => $teacher->id, 'name' => 'Grade 5 Explorers'],
            ['school_name' => 'Demo School']
        );
        $group->profiles()->syncWithoutDetaching([$child->id]);

    }
}
