<?php

namespace Tests\Feature;

use App\Jobs\SendSms;
use App\Models\User;
use App\Models\Otp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_login_issues_a_real_api_token_without_sms(): void
    {
        config()->set('auth.demo', [
            'enabled' => true,
            'phone_number' => '+8801700000000',
            'otp' => '2580',
        ]);

        $this->postJson('/api/login', ['phoneNumber' => '+8801700000000'])
            ->assertOk();

        $this->postJson('/api/otp', [
            'phoneNumber' => '+8801700000000',
            'otp' => 2580,
        ])->assertOk()->assertJsonPath('message', 'authenticated');
    }

    public function test_non_demo_phone_uses_the_sms_otp_flow(): void
    {
        Bus::fake();

        config()->set('auth.demo', [
            'enabled' => true,
            'phone_number' => '+8801700000000',
            'otp' => '2580',
        ]);

        $this->postJson('/api/login', ['phoneNumber' => '+8801800000000'])
            ->assertOk();

        Bus::assertDispatched(SendSms::class);
    }

    public function test_login_with_otp_returns_token_and_user()
    {
        $phoneNumber = '01700000000';

        $user = User::factory()->create(['phone_number' => $phoneNumber]);

        $otp = Otp::factory()->create([
            'user_id' => $user->id,
            'otp' => 1234,
            'is_valid' => true,
        ]);

        $response = $this->postJson('/api/otp', [
            'phoneNumber' => $phoneNumber,
            'otp' => 1234,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'token',
                'user' => [
                    'id',
                    'phone_number',

                ],
            ],
        ]);
    }
}
