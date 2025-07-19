<?php

namespace Tests\Unit;

use App\Models\Demography;
use App\Models\Option;
use App\Models\Otp;
use App\Models\Profile;
use App\Models\Question;
use App\Models\Response;
use App\Models\Scale;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function demography_belongs_to_profile()
    {
        $user = User::factory()->create();
        $profile = Profile::factory()->create([
            'user_id' => $user->id,
        ]);
        $demography = Demography::factory()->create([
            'profile_id' => $profile->id,
            'dob' => now()->subYears(10)->toDateString(),
            'gender' => 'male',
            'grade' => '5',
        ]);

        $this->assertInstanceOf(Profile::class, $demography->profile);
        $this->assertEquals($profile->id, $demography->profile->id);
    }

    /** @test */
    public function option_relationships()
    {
        $scale = Scale::factory()->create();
        $question = Question::factory()->create([
            'scale_id' => $scale->id,
        ]);

        $option = Option::factory()->create([
            'question_id' => $question->id,
            'text' => 'Option Text',
            'is_image' => false,
            'value' => 1,
            'order' => 1,
        ]);

        $response = Response::factory()->create([
            'option_id' => $option->id,
        ]);

        $this->assertInstanceOf(Question::class, $option->question);
        $this->assertInstanceOf(Response::class, $option->responses->first());
        $this->assertEquals($option->id, $response->option->id);
    }

    /** @test */
    public function profile_relationships()
    {
        $user = User::factory()->create();

        $profile = Profile::factory()->create([
            'user_id' => $user->id,
            'type' => 'child',
            'full_name' => 'Test User',
            'relation_type' => 'child',
        ]);

        $demography = Demography::factory()->create([
            'profile_id' => $profile->id,
            'dob' => now()->subYears(10)->toDateString(),
            'gender' => 'female',
            'grade' => '7',
        ]);

        $response = Response::factory()->create([
            'profile_id' => $profile->id,
        ]);

        $this->assertInstanceOf(User::class, $profile->user);
        $this->assertInstanceOf(Demography::class, $profile->demography);
        $this->assertTrue($profile->responses->contains($response));
    }

    /** @test */
    public function question_relationships()
    {
        $scale = Scale::factory()->create();

        $question = Question::factory()->create([
            'scale_id' => $scale->id,
            'text' => 'Sample question?',
            'type' => 'multiple-choice',
            'order' => 1,
        ]);

        $option = Option::factory()->create([
            'question_id' => $question->id,
            'text' => 'Yes',
            'value' => 1,
        ]);

        $response = Response::factory()->create([
            'question_id' => $question->id,
            'option_id' => $option->id,
        ]);

        $this->assertInstanceOf(Scale::class, $question->scale);
        $this->assertTrue($question->options->contains($option));
        $this->assertTrue($question->responses->contains($response));
    }

    /** @test */
    public function response_relationships()
    {
        $user = User::factory()->create();
        $profile = Profile::factory()->create(['user_id' => $user->id]);
        $scale = Scale::factory()->create();
        $question = Question::factory()->create(['scale_id' => $scale->id]);
        $option = Option::factory()->create(['question_id' => $question->id]);

        $response = Response::factory()->create([
            'profile_id' => $profile->id,
            'question_id' => $question->id,
            'option_id' => $option->id,
            'text_answer' => 'My answer',
            'numeric_answer' => 5,
        ]);

        $this->assertInstanceOf(Option::class, $response->option);
        $this->assertInstanceOf(Profile::class, $response->profile);
        $this->assertInstanceOf(Question::class, $response->question);
    }

    /** @test */
    public function scale_has_many_questions()
    {
        $scale = Scale::factory()->create();

        $question1 = Question::factory()->create(['scale_id' => $scale->id]);
        $question2 = Question::factory()->create(['scale_id' => $scale->id]);

        $this->assertCount(2, $scale->questions);
        $this->assertTrue($scale->questions->contains($question1));
        $this->assertTrue($scale->questions->contains($question2));
    }

    /** @test */
    public function user_get_valid_otp()
    {
        $user = User::factory()->create();

        $otp = Otp::factory()->create([
            'user_id' => $user->id,
            'is_valid' => true,
            'attempts_left' => 3,
            'expires_at' => now()->addMinutes(15),
        ]);

        $validOtp = $user->get_valid_otp();

        $this->assertNotFalse($validOtp);
        $this->assertEquals($otp->id, $validOtp->id);
    }

    /** @test */
    public function user_profiles_relationship()
    {
        $user = User::factory()->create();

        $profile1 = Profile::factory()->create(['user_id' => $user->id]);
        $profile2 = Profile::factory()->create(['user_id' => $user->id]);

        $this->assertCount(2, $user->profiles);
        $this->assertTrue($user->profiles->contains($profile1));
        $this->assertTrue($user->profiles->contains($profile2));
    }
}
