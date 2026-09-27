<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Otp;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_demo_login_rejects_other_phone_numbers(): void
    {
        config()->set('auth.demo', [
            'enabled' => true,
            'phone_number' => '+8801700000000',
            'otp' => '2580',
        ]);

        $this->postJson('/api/login', ['phoneNumber' => '+8801800000000'])
            ->assertForbidden()
            ->assertJsonPath('message', 'demo_phone_required');
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
