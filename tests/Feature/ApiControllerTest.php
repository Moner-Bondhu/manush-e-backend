<?php

namespace Tests\Feature;

use App\Models\Option;
use App\Models\Otp;
use App\Models\Profile;
use App\Models\Question;
use App\Models\LearningGroup;
use App\Models\ProfileUserAssignment;
use App\Models\Response;
use App\Models\Scale;
use App\Models\User;
use Database\Seeders\MeasurementLibrarySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_protected_api_returns_json_unauthorized_without_accept_header(): void
    {
        $this->get('/api/assessment-library')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_authenticated_user_can_view_measurement_library_metadata()
    {
        $this->seed(MeasurementLibrarySeeder::class);
        Sanctum::actingAs(User::factory()->create(['user_type' => 'teacher']));

        $response = $this->getJson('/api/assessment-library');

        $response->assertOk()
            ->assertJsonPath('data.summary.packs', 12)
            ->assertJsonPath('data.summary.questions', 57)
            ->assertJsonFragment([
                'code' => 'CHILD-WELLBEING-PULSE',
                'domain' => 'mental_wellbeing',
                'instrument_status' => 'original_research_informed',
            ])
            ->assertJsonFragment([
                'code' => 'CHILD-OPEN-THINKING-STUDIO',
                'domain' => 'student_potential',
                'instrument_status' => 'original_research_informed',
            ]);
    }

    public function test_authenticated_user_can_fetch_self()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/user');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'fetched',
            ]);
    }

    public function test_user_can_create_profile()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $payload = [
            'fullName' => 'Test Child',
            'type' => 'child',
            'relationType' => 'child',
            'dob' => '2010-01-01',
            'gender' => 'male',
            'grade' => '5',
        ];

        $response = $this->postJson('/api/profile/create', $payload);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'created',
            ]);
    }

    public function test_user_can_complete_onboarding()
    {
        $user = User::factory()->create();
        $child = Profile::factory()->create(['user_id' => $user->id, 'type' => 'child']);
        $parent = Profile::factory()->create(['user_id' => $user->id, 'type' => 'parent']);
        $child->demography()->create(['gender' => 'male', 'dob' => '2010-01-01', 'grade' => '5']);
        $parent->demography()->create(['gender' => 'male', 'dob' => '1980-01-01', 'grade' => '1']);

        Sanctum::actingAs($user);
        $response = $this->postJson('/api/user/onboard');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'onboarded',
            ]);
        $this->assertTrue($user->fresh()->is_onboarded);
    }

    public function test_user_can_view_scales()
    {
        $user = User::factory()->create();
        $profile = Profile::factory()->create(['user_id' => $user->id, 'type' => 'child']);
        $scale = Scale::factory()->create(['visible_to' => 'child']);
        Sanctum::actingAs($user);

        $response = $this->getJson("/api/scales/child");
        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_user_can_view_scale_detail()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $scale = Scale::factory()->create();

        $response = $this->getJson("/api/scale/{$scale->id}");
        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_user_can_store_response_for_question()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $scale = Scale::factory()->create(['visible_to' => 'child']);
        $question = Question::factory()->create(['scale_id' => $scale->id]);
        $option = Option::factory()->create(['question_id' => $question->id]);

        $profile = Profile::factory()->create([
            'user_id' => $user->id,
            'type' => $scale->visible_to,
        ]);

        $payload = [
            'option_id' => $option->id,
            'text_answer' => 'This is my answer',
            'numeric_answer' => 7,
        ];

        $response = $this->postJson("/api/question/respond/{$question->id}", $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'created',
            ]);

        $this->assertDatabaseHas('responses', [
            'profile_id' => $profile->id,
            'question_id' => $question->id,
            'option_id' => $option->id,
            'text_answer' => 'This is my answer',
            'numeric_answer' => 7,
        ]);
    }

    public function test_user_can_update_existing_response_for_question()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $scale = Scale::factory()->create(['visible_to' => 'child']);
        $question = Question::factory()->create(['scale_id' => $scale->id]);
        $option1 = Option::factory()->create(['question_id' => $question->id]);
        $option2 = Option::factory()->create(['question_id' => $question->id]);

        $profile = Profile::factory()->create([
            'user_id' => $user->id,
            'type' => $scale->visible_to,
        ]);

        $existingResponse = Response::factory()->create([
            'profile_id' => $profile->id,
            'question_id' => $question->id,
            'option_id' => $option1->id,
            'text_answer' => 'Old answer',
            'numeric_answer' => 3,
        ]);

        $payload = [
            'option_id' => $option2->id,
            'text_answer' => 'Updated answer',
            'numeric_answer' => 8,
        ];

        $response = $this->postJson("/api/question/respond/{$question->id}", $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'created',
            ]);

        $this->assertDatabaseHas('responses', [
            'id' => $existingResponse->id,
            'option_id' => $option2->id,
            'text_answer' => 'Updated answer',
            'numeric_answer' => 8,
        ]);
    }

    public function test_user_can_store_an_open_ended_response(): void
    {
        $user = User::factory()->create();
        $scale = Scale::factory()->create(['visible_to' => 'child']);
        $question = Question::factory()->create(['scale_id' => $scale->id, 'type' => 'text']);
        $profile = Profile::factory()->create(['user_id' => $user->id, 'type' => 'child']);
        Sanctum::actingAs($user);

        $this->postJson("/api/question/respond/{$question->id}", [
            'text_answer' => 'I would draw a map first and explain the route.',
        ])->assertOk();

        $this->assertDatabaseHas('responses', [
            'profile_id' => $profile->id,
            'question_id' => $question->id,
            'text_answer' => 'I would draw a map first and explain the route.',
        ]);
    }

    public function test_user_cannot_submit_an_option_from_another_question(): void
    {
        $user = User::factory()->create();
        $scale = Scale::factory()->create(['visible_to' => 'child']);
        $question = Question::factory()->create(['scale_id' => $scale->id, 'type' => 'select_one']);
        $otherQuestion = Question::factory()->create(['scale_id' => $scale->id, 'type' => 'select_one']);
        $wrongOption = Option::factory()->create(['question_id' => $otherQuestion->id]);
        Profile::factory()->create(['user_id' => $user->id, 'type' => 'child']);
        Sanctum::actingAs($user);

        $this->postJson("/api/question/respond/{$question->id}", [
            'option_id' => $wrongOption->id,
        ])->assertUnprocessable();
    }

    public function test_teacher_can_open_and_advance_an_assigned_teacher_activity(): void
    {
        $teacher = User::factory()->create(['user_type' => 'teacher']);
        $family = User::factory()->create();
        $profile = Profile::factory()->create(['user_id' => $family->id, 'type' => 'child']);
        $group = LearningGroup::create(['teacher_user_id' => $teacher->id, 'name' => 'Demo Class']);
        $group->profiles()->attach($profile);
        $scale = Scale::factory()->create(['visible_to' => 'teacher', 'code' => 'TEACHER-OBSERVATION', 'is_published' => true]);
        $question = Question::factory()->create(['scale_id' => $scale->id]);
        $option = Option::factory()->create(['question_id' => $question->id]);
        Sanctum::actingAs($teacher);

        $this->getJson("/api/scales/teacher?profile_id={$profile->id}")
            ->assertOk()
            ->assertJsonPath('data.0.response_count', 0);

        $this->postJson("/api/question/respond/{$question->id}", [
            'profile_id' => $profile->id,
            'option_id' => $option->id,
        ])->assertOk();

        $activity = collect($this->getJson("/api/scales/teacher?profile_id={$profile->id}")
            ->assertOk()->json('data'))->firstWhere('id', $scale->id);
        $this->assertSame(1, $activity['response_count']);
        $this->assertSame('complete', $activity['status']);
    }

    public function test_teacher_cannot_submit_student_questionnaire_answers(): void
    {
        $teacher = User::factory()->create(['user_type' => 'teacher', 'portal_role' => 'teacher']);
        $profile = Profile::factory()->create(['type' => 'child']);
        $group = LearningGroup::create(['teacher_user_id' => $teacher->id, 'name' => 'Demo Class']);
        $group->profiles()->attach($profile);
        $scale = Scale::factory()->create(['visible_to' => 'child', 'code' => 'CHILD-ONLY', 'is_published' => true]);
        $question = Question::factory()->create(['scale_id' => $scale->id]);
        $option = Option::factory()->create(['question_id' => $question->id]);

        $this->actingAs($teacher, 'sanctum')
            ->getJson("/api/scales/child?profile_id={$profile->id}")
            ->assertForbidden();

        $this->actingAs($teacher, 'sanctum')
            ->postJson("/api/question/respond/{$question->id}", ['profile_id' => $profile->id, 'option_id' => $option->id])
            ->assertForbidden();
    }

    public function test_parent_support_review_is_adult_only_and_includes_referral_for_priority_case(): void
    {
        $parent = User::factory()->create(['portal_role' => 'parent']);
        $student = User::factory()->create(['portal_role' => 'student']);
        $profile = Profile::factory()->create([
            'user_id' => $parent->id,
            'type' => 'child',
            'details' => ['support_review' => [
                'priority' => 'priority',
                'summary' => 'Human review recommended / মানবিক পর্যালোচনা প্রস্তাবিত',
            ]],
        ]);
        ProfileUserAssignment::create(['profile_id' => $profile->id, 'user_id' => $parent->id, 'role' => 'parent']);
        ProfileUserAssignment::create(['profile_id' => $profile->id, 'user_id' => $student->id, 'role' => 'student']);

        $this->actingAs($parent, 'sanctum')->getJson('/api/portal/parent')
            ->assertOk()
            ->assertJsonPath('data.support_review.priority', 'priority')
            ->assertJsonPath('data.support_review.expert_referral.provider', 'Moner Bondhu');

        $this->actingAs($student, 'sanctum')->getJson('/api/user')
            ->assertOk()
            ->assertJsonMissing(['support_review']);
    }

    public function test_admin_review_dashboard_summarizes_profile_and_task_coverage(): void
    {
        $admin = User::factory()->create(['user_type' => 'admin', 'portal_role' => 'expert']);
        $teacher = User::factory()->create(['user_type' => 'teacher']);
        $profile = Profile::factory()->create(['details' => [
            'date_of_birth' => '2015-01-01', 'grade_level' => '5', 'school_name' => 'School',
            'curriculum' => 'National', 'primary_language' => 'English', 'sleep_hours' => 9,
            'activity_days' => 5, 'interests' => ['Art'],
        ]]);
        $group = \App\Models\LearningGroup::create(['teacher_user_id' => $teacher->id, 'name' => 'Class A', 'school_name' => 'School']);
        $group->profiles()->attach($profile->id);

        $this->actingAs($admin, 'sanctum')->getJson('/api/portal/review')
            ->assertOk()
            ->assertJsonPath('data.viewer_role', 'expert')
            ->assertJsonPath('data.summary.students', 1)
            ->assertJsonPath('data.students.0.profile_completion', 100);
    }

    public function test_non_admin_cannot_open_review_dashboard(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum')->getJson('/api/portal/review')->assertForbidden();
    }

    public function test_expert_can_open_an_enrolled_student_case_with_source_coverage(): void
    {
        $expert = User::factory()->create(['user_type' => 'admin', 'portal_role' => 'expert']);
        $teacher = User::factory()->create(['user_type' => 'teacher']);
        $profile = Profile::factory()->create(['type' => 'child']);
        $group = LearningGroup::create(['teacher_user_id' => $teacher->id, 'name' => 'Class A']);
        $group->profiles()->attach($profile);

        $this->actingAs($expert, 'sanctum')->getJson("/api/portal/review/{$profile->id}")
            ->assertOk()
            ->assertJsonStructure(['data' => ['source_coverage' => ['student', 'parent', 'teacher'], 'source_tasks']]);
    }
}
