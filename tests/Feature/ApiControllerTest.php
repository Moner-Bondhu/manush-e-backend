<?php

namespace Tests\Feature;

use App\Models\Option;
use App\Models\Otp;
use App\Models\Profile;
use App\Models\Question;
use App\Models\Response;
use App\Models\Scale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiControllerTest extends TestCase
{
    use RefreshDatabase;

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
}
