<?php

namespace Tests\Unit;

use App\Http\Resources\DemographyResource;
use App\Http\Resources\OptionResource;
use App\Http\Resources\ProfileResource;
use App\Http\Resources\QuestionResource;
use App\Http\Resources\ScaleResource;
use App\Http\Resources\UserResource;
use App\Models\Demography;
use App\Models\Option;
use App\Models\Profile;
use App\Models\Question;
use App\Models\Scale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_demography_resource()
    {
        $demography = Demography::factory()->make([
            'dob' => '2010-01-01',
            'gender' => 'male',
            'grade' => '5',
        ]);

        $resource = new DemographyResource($demography);
        $array = $resource->response()->getData(true)['data'];

        $this->assertEquals('2010-01-01', $array['dob']);
        $this->assertEquals('male', $array['gender']);
        $this->assertEquals('5', $array['grade']);
    }

    public function test_option_resource()
    {
        $option = Option::factory()->make([
            'id' => 1,
            'text' => 'Sample Option',
            'is_image' => false,
            'value' => 3,
            'order' => 1,
        ]);

        $resource = new OptionResource($option);
        $array = $resource->response()->getData(true)['data'];

        $this->assertEquals($option->id, $array['id']);
        $this->assertEquals('Sample Option', $array['text']);
        $this->assertEquals(false, $array['is_image']);
        $this->assertEquals(3, $array['value']);
        $this->assertEquals(1, $array['order']);

        $this->assertArrayNotHasKey('question', $array);
    }

    public function test_profile_resource()
    {
        $user = User::factory()->make();
        $demography = Demography::factory()->make();
        $profile = Profile::factory()->make([
            'full_name' => 'Test Name',
            'type' => 'child',
            'relation_type' => 'child',
        ]);
        $profile->setRelation('demography', $demography);
        $profile->setRelation('user', $user);

        $resource = new ProfileResource($profile);
        $array = $resource->response()->getData(true)['data'];

        $this->assertEquals('Test Name', $array['full_name']);
        $this->assertEquals('child', $array['type']);
        $this->assertEquals('child', $array['relation_type']);
        $this->assertIsArray($array['demography']);
        $this->assertIsArray($array['user']);
    }

    public function test_question_resource()
    {
        $scale = Scale::factory()->make();
        $question = Question::factory()->make([
            'text' => 'Question text',
            'subtext' => 'Question subtext',
            'image' => 'image.jpg',
            'type' => 'multiple-choice',
            'order' => 1,
        ]);
        $question->setRelation('scale', $scale);
        $question->setRelation('options', collect());

        $resource = new QuestionResource($question);
        $array = $resource->response()->getData(true)['data'];

        $this->assertEquals('Question text', $array['text']);
        $this->assertEquals('Question subtext', $array['subtext']);
        $this->assertEquals('image.jpg', $array['image']);
        $this->assertEquals('multiple-choice', $array['type']);
        $this->assertEquals(1, $array['order']);
        $this->assertIsArray($array['scale']);
        $this->assertIsArray($array['options']);
    }

    public function test_scale_resource()
    {
        $scale = Scale::factory()->make([
            'name' => 'Sample Scale',
            'description' => 'Description text',
            'visible_to' => 'child',
        ]);
        $scale->setRelation('questions', collect());

        $resource = new ScaleResource($scale);
        $array = $resource->response()->getData(true)['data'];

        $this->assertEquals('Sample Scale', $array['name']);
        $this->assertEquals('Description text', $array['description']);
        $this->assertEquals('child', $array['visible_to']);
        $this->assertIsArray($array['questions']);
    }

    public function test_user_resource()
    {
        $user = User::factory()->make([
            'id' => 1,
            'phone_number' => '01700000000',
            // no email included here
        ]);

        $resource = new UserResource($user);
        $array = $resource->response()->getData(true)['data'];

        $this->assertEquals(1, $array['id']);
        $this->assertEquals('01700000000', $array['phone_number']);
        $this->assertArrayNotHasKey('email', $array); // make sure email is NOT present
    }
}
