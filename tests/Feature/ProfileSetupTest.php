<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_family_can_save_a_child_profile_progressively(): void
    {
        $user = User::factory()->create();
        $child = Profile::factory()->create(['user_id' => $user->id, 'full_name' => 'Maya']);

        $this->actingAs($user, 'sanctum')->getJson('/api/profile-setup/child')
            ->assertOk()->assertJsonPath('data.subject_name', 'Maya')
            ->assertJsonPath('data.completion.percent', 0);

        $this->actingAs($user, 'sanctum')->putJson('/api/profile-setup/child', [
            'data' => ['school_name' => 'River School', 'interests' => 'drawing, football'],
        ])->assertOk()->assertJsonPath('data.completion.percent', 25);

        $this->assertSame('River School', $child->fresh()->details['school_name']);
    }

    public function test_child_profile_reaches_full_core_completion(): void
    {
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')->putJson('/api/profile-setup/child', ['data' => [
            'date_of_birth' => '2015-04-12', 'grade_level' => 'Grade 5',
            'school_name' => 'River School', 'curriculum' => 'National curriculum',
            'primary_language' => 'English', 'sleep_hours' => 9,
            'activity_days' => 5, 'interests' => 'drawing, football',
        ]])->assertOk()->assertJsonPath('data.completion.percent', 100)
            ->assertJsonPath('data.completion.completed', 8);
    }

    public function test_teacher_can_save_professional_profile(): void
    {
        $teacher = User::factory()->create(['user_type' => 'teacher']);

        $this->actingAs($teacher, 'sanctum')->putJson('/api/profile-setup/teacher', ['data' => [
            'school_name' => 'River School', 'role_title' => 'Class teacher',
            'highest_qualification' => 'BEd', 'years_experience' => 6,
            'subjects' => 'English, Art', 'grade_levels' => 'Grade 5',
            'curriculum' => 'National curriculum', 'typical_class_size' => 28,
        ]])->assertOk()->assertJsonPath('data.completion.percent', 100);

        $this->assertSame('BEd', $teacher->fresh()->professional_details['highest_qualification']);
    }

    public function test_regular_user_cannot_access_teacher_profile(): void
    {
        $user = User::factory()->create(['user_type' => 'user']);
        $this->actingAs($user, 'sanctum')->getJson('/api/profile-setup/teacher')->assertForbidden();
    }

    public function test_user_cannot_access_a_profile_type_they_do_not_own(): void
    {
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id, 'type' => 'child']);
        $this->actingAs($user, 'sanctum')->getJson('/api/profile-setup/parent')->assertNotFound();
    }
}
