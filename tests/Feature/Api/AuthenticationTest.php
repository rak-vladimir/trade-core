<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'password' => 'password',
        ]);

        $this->withHeader('Origin', 'http://localhost')
            ->postJson('/api/v1/login', [
                'email'    => $user->email,
                'password' => 'password',
            ])
            ->assertOk()
            ->assertJsonPath('id', $user->id)
            ->assertJsonPath('email', $user->email);

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'password' => 'password',
        ]);

        $this->withHeader('Origin', 'http://localhost')
            ->postJson('/api/v1/login', [
                'email'    => $user->email,
                'password' => 'wrong-password',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertGuest();
    }

    public function test_login_requires_email_and_password(): void
    {
        $this->postJson('/api/v1/login', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'email',
                'password',
            ]);
    }

    public function test_authenticated_user_can_get_current_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/user')
            ->assertOk()
            ->assertJsonPath('id', $user->id)
            ->assertJsonPath('email', $user->email);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withHeader('Origin', 'http://localhost')
            ->postJson('/api/v1/logout')
            ->assertNoContent();

        $this->assertGuest('web');
    }

    public function test_login_returns_validation_error_in_requested_locale(): void
    {
        User::factory()->create([
            'email'    => 'user@example.com',
            'password' => 'correct-password',
        ]);

        $response = $this->withHeader('Accept-Language', 'ru')
            ->postJson('/api/v1/login', [
                'email'    => 'user@example.com',
                'password' => 'wrong-password',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('email')
            ->assertJsonPath(
                'errors.email.0',
                'Неверный адрес электронной почты или пароль.',
            );
    }

    public function test_login_is_rate_limited_after_five_failed_attempts(): void
    {
        $this->withServerVariables([
            'REMOTE_ADDR' => '192.0.2.10',
        ]);

        $credentials = [
            'email'    => 'user@example.com',
            'password' => 'wrong-password',
        ];

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/v1/login', $credentials)
                ->assertUnprocessable();
        }

        $this->postJson('/api/v1/login', $credentials)
            ->assertTooManyRequests();
    }
}
