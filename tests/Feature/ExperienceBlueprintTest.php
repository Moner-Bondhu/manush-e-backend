<?php

namespace Tests\Feature;

use App\Models\LearningGroup;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExperienceBlueprintTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_create_and_publish_an_experience_blueprint(): void
    {
        $teacher = User::factory()->create(['user_type' => 'teacher']);
        $studentOwner = User::factory()->create();
        $student = Profile::create(['user_id' => $studentOwner->id, 'type' => 'child', 'full_name' => 'Avery Student', 'relation_type' => 'child']);
        $group = LearningGroup::create(['teacher_user_id' => $teacher->id, 'name' => 'Grade 6 Maths']);
        $group->profiles()->attach($student->id);

        $payload = [
            'learning_group_id' => $group->id,
            'title' => 'Math confidence journey',
            'subject' => 'Mathematics',
            'grade_band' => 'Grade 6',
            'focus_area' => 'confidence and persistence',
            'objective' => 'Students will use visual ratio strategies and persist through multi-step problems.',
            'duration_weeks' => 6,
            'success_criteria' => ['Students explain one useful strategy.'],
            'blocks' => [[
                'week_number' => 1,
                'block_type' => 'learn',
                'title' => 'Start with strengths',
                'description' => 'A low-stakes visual challenge.',
                'student_prompt' => 'Choose a way to explore the challenge.',
                'teacher_note' => 'Notice which supports help students begin.',
                'duration_minutes' => 35,
                'personalization' => ['format' => 'visual or collaborative'],
            ]],
        ];

        $response = $this->actingAs($teacher, 'sanctum')->postJson('/api/experience-blueprints', $payload);

        $response->assertOk()->assertJsonPath('data.title', 'Math confidence journey');
        $this->assertDatabaseHas('experience_blueprints', ['title' => 'Math confidence journey', 'status' => 'draft']);
        $blueprintId = $response->json('data.id');
        $this->assertDatabaseHas('experience_blocks', ['experience_blueprint_id' => $blueprintId, 'title' => 'Start with strengths']);

        $this->actingAs($teacher, 'sanctum')->postJson("/api/experience-blueprints/{$blueprintId}/publish")
            ->assertOk()->assertJsonPath('data.status', 'published');
    }

    public function test_non_teacher_cannot_access_experience_studio(): void
    {
        $user = User::factory()->create(['user_type' => 'user']);

        $this->actingAs($user, 'sanctum')->getJson('/api/experience-blueprints')->assertForbidden();
    }
}
