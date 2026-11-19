<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_routes_are_not_available(): void
    {
        $this->get('/forgot-password')->assertNotFound();
        $this->post('/forgot-password', ['email' => 'siapa@example.com'])->assertNotFound();
        $this->get('/reset-password/token-palsu')->assertNotFound();
        $this->post('/reset-password', [])->assertNotFound();
    }
}
