<?php

namespace Tests\Unit;

use App\Games\GameFeatureExtractor;
use App\Models\GameDefinition;
use App\Models\GameEvent;
use App\Models\GameSession;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameFeatureExtractorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_extracts_multi_domain_attention_features(): void
    {
        $profile = Profile::factory()->create(['user_id' => User::factory()->create()->id]);
        $game = GameDefinition::create([
            'slug' => 'focus-lab',
            'title' => 'Focus Lab',
            'description' => 'Test game',
            'skill_tags' => ['attention'],
        ]);
        $session = GameSession::create([
            'game_definition_id' => $game->id,
            'profile_id' => $profile->id,
            'status' => 'completed',
            'duration_ms' => 8000,
            'metrics' => ['attempted_rounds' => 8, 'correct_choices' => 6],
            'started_at' => now()->subSeconds(8),
            'completed_at' => now(),
        ]);

        $rounds = [
            ['light', true, 900], ['thorn', true, 1000], ['light', false, 1400], ['thorn', true, 1100],
            ['light', true, 1200], ['thorn', false, 1700], ['light', true, 1300], ['thorn', true, 1250],
        ];
        foreach ($rounds as $index => [$prompt, $correct, $responseMs]) {
            GameEvent::create([
                'game_session_id' => $session->id,
                'event_type' => 'response',
                'payload' => ['round' => $index + 1, 'prompt' => $prompt, 'correct' => $correct, 'response_ms' => $responseMs],
                'occurred_at' => now(),
            ]);
        }

        app(GameFeatureExtractor::class)->capture($session->load('game'));

        $this->assertDatabaseHas('child_feature_snapshots', ['domain' => 'attention', 'feature' => 'task_accuracy', 'value' => 75]);
        $this->assertDatabaseHas('child_feature_snapshots', ['domain' => 'inhibitory_control', 'feature' => 'commission_rate', 'value' => 25]);
        $this->assertDatabaseHas('child_feature_snapshots', ['domain' => 'attention', 'feature' => 'omission_rate', 'value' => 25]);
        $this->assertDatabaseHas('child_feature_snapshots', ['domain' => 'attention', 'feature' => 'median_response_ms', 'value' => 1225]);
        $this->assertDatabaseMissing('child_feature_snapshots', ['domain' => 'inhibitory_control', 'feature' => 'task_accuracy']);
    }

    public function test_it_ignores_implausible_latency_values(): void
    {
        $profile = Profile::factory()->create(['user_id' => User::factory()->create()->id]);
        $game = GameDefinition::create([
            'slug' => 'sky-scout',
            'title' => 'Sky Scout',
            'description' => 'Test game',
            'skill_tags' => ['attention'],
        ]);
        $session = GameSession::create([
            'game_definition_id' => $game->id,
            'profile_id' => $profile->id,
            'status' => 'completed',
            'duration_ms' => 2000,
            'metrics' => ['attempted_rounds' => 4, 'correct_choices' => 4],
            'started_at' => now()->subSecond(),
            'completed_at' => now(),
        ]);
        foreach ([20, 40000, 40, 50000] as $index => $responseMs) {
            GameEvent::create([
                'game_session_id' => $session->id,
                'event_type' => 'response',
                'payload' => ['round' => $index + 1, 'correct' => true, 'response_ms' => $responseMs],
                'occurred_at' => now(),
            ]);
        }

        app(GameFeatureExtractor::class)->capture($session->load('game'));

        $this->assertDatabaseMissing('child_feature_snapshots', ['source_id' => $session->id, 'feature' => 'median_response_ms']);
        $this->assertDatabaseHas('child_feature_snapshots', ['source_id' => $session->id, 'domain' => 'selective_attention', 'feature' => 'task_accuracy']);
    }

    public function test_it_does_not_capture_too_little_interaction_data(): void
    {
        $profile = Profile::factory()->create(['user_id' => User::factory()->create()->id]);
        $game = GameDefinition::create([
            'slug' => 'sky-scout', 'title' => 'Sky Scout', 'description' => 'Test game', 'skill_tags' => ['attention'],
        ]);
        $session = GameSession::create([
            'game_definition_id' => $game->id, 'profile_id' => $profile->id, 'status' => 'completed',
            'duration_ms' => 800, 'metrics' => ['attempted_rounds' => 1, 'correct_choices' => 1],
            'started_at' => now()->subSecond(), 'completed_at' => now(),
        ]);
        GameEvent::create([
            'game_session_id' => $session->id, 'event_type' => 'response',
            'payload' => ['round' => 1, 'correct' => true, 'response_ms' => 500], 'occurred_at' => now(),
        ]);

        app(GameFeatureExtractor::class)->capture($session->load('game'));

        $this->assertDatabaseMissing('child_feature_snapshots', ['source_id' => $session->id]);
    }
}
