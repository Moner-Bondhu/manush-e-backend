<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Otp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

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
