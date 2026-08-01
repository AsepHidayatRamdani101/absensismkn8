<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiResponseContractTest extends TestCase
{
    #[Test]
    public function login_success_response_matches_contract_and_keeps_legacy_keys(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 1;
        $user->name = 'Administrator';
        $user->email = 'admin@example.com';
        $user->shouldReceive('createToken')->once()->with('mobile-app')->andReturn((object) [
            'plainTextToken' => '1|plain_text_token',
        ]);
        $user->shouldReceive('getRoleNames')->andReturn(collect(['admin']));

        Auth::shouldReceive('attempt')->once()->andReturn(true);
        Auth::shouldReceive('user')->once()->andReturn($user);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Login berhasil')
            ->assertJsonStructure([
                'success',
                'message',
                'token',
                'user' => ['id', 'name', 'email', 'role'],
                'data' => [
                    'token',
                    'user' => ['id', 'name', 'email', 'role'],
                ],
            ]);
    }

    #[Test]
    public function login_failure_response_matches_contract(): void
    {
        Auth::shouldReceive('attempt')->once()->andReturn(false);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'kontrak-gagal-' . uniqid() . '@example.com',
            'password' => 'password-salah',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Email atau password salah')
            ->assertJsonPath('data', null);
    }

    #[Test]
    public function profile_returns_standard_envelope_shape(): void
    {
        $this->withoutMiddleware();

        $profile = $this->getJson('/api/v1/profile');

        $profile->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Profil berhasil dimuat')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'name', 'email', 'role'],
            ]);
    }

    #[Test]
    public function device_endpoints_return_standard_envelope(): void
    {
        $this->withoutMiddleware();

        $rfid = $this->postJson('/api/v1/device/rfid', [
            'device_code' => 'RFID-01',
            'student_id' => 1,
            'tag_uid' => 'TAG-001',
        ]);

        $face = $this->postJson('/api/v1/device/face', [
            'student_id' => 1,
            'device_code' => 'FACE-01',
            'confidence' => 0.95,
        ]);

        $rfid->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['device_code', 'student_id', 'tag_uid', 'timestamp'],
            ]);

        $face->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['student_id', 'device_code', 'confidence', 'timestamp'],
            ]);
    }
}
