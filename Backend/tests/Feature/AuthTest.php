<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_login_use_the_token_and_logout(): void
    {
        User::factory()->create([
            'email' => 'kasir@angkringan.test',
            'password' => 'secret123',
        ]);

        $login = $this->postJson('/api/auth/login', [
            'email' => 'kasir@angkringan.test',
            'password' => 'secret123',
        ]);

        $login->assertOk()
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.role', 'cashier')
            ->assertJsonStructure(['data' => ['token']]);

        $token = (string) $login->json('data.token');

        $this->withToken($token)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'kasir@angkringan.test');

        $this->withToken($token)
            ->postJson('/api/auth/logout')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);

        // The in-memory test application keeps Sanctum's RequestGuard user
        // cached between requests; a real request starts with a fresh guard.
        $this->app['auth']->forgetGuards();

        $this->withToken($token)
            ->getJson('/api/auth/me')
            ->assertUnauthorized();
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'kasir@angkringan.test',
            'password' => 'secret123',
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'kasir@angkringan.test',
            'password' => 'wrong-password',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }
}
