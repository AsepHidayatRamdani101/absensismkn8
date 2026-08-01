<?php

namespace Tests\Feature\Api;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiRateLimitBehaviorTest extends TestCase
{
    #[Test]
    public function web_login_is_rate_limited_after_five_attempts_per_minute(): void
    {
        $email = 'web-limit-' . uniqid() . '@example.com';
        $response = null;

        for ($i = 1; $i <= 6; $i++) {
            $response = $this->post('/login', [
                'email' => $email,
                'password' => 'salah-password',
            ]);
        }

        $this->assertNotNull($response);
        $response->assertStatus(429);
    }

    #[Test]
    public function api_login_is_rate_limited_after_ten_attempts_per_minute(): void
    {
        $email = 'api-limit-' . uniqid() . '@example.com';
        $response = null;

        for ($i = 1; $i <= 11; $i++) {
            $response = $this->postJson('/api/v1/login', [
                'email' => $email,
                'password' => 'salah-password',
            ]);
        }

        $this->assertNotNull($response);
        $response->assertStatus(429);
    }
}
