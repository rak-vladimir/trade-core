<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);
    }

    protected function statefulRequest(): static
    {
        return $this->withHeader('Origin', 'http://localhost');
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email'    => 'test@example.com',
            'password' => 'password',
        ]);

        $response = $this->statefulRequest()->postJson('/api/v1/login', [
            'email'    => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('id', $user->id)
            ->assertJsonPath('email', $user->email);

        $this->assertAuthenticatedAs($user, 'web');
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email'    => 'test@example.com',
            'password' => 'password',
        ]);

        $response = $this->statefulRequest()->postJson('/api/v1/login', [
            'email'    => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertGuest('web');
    }

    public function test_authenticated_user_can_login_again_as_another_user(): void
    {
        $firstUser = User::factory()->create();

        $secondUser = User::factory()->create([
            'email'    => 'second@example.com',
            'password' => 'password',
        ]);

        $this->actingAs($firstUser, 'web');

        $this->assertAuthenticatedAs($firstUser, 'web');

        $response = $this->statefulRequest()->postJson('/api/v1/login', [
            'email'    => 'second@example.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('id', $secondUser->id)
            ->assertJsonPath('email', $secondUser->email);

        $this->assertAuthenticatedAs($secondUser, 'web');
    }

    public function test_user_can_logout_and_session_is_invalidated(): void
    {
        $user = User::factory()->create();

        $this->withSession([
            'session_marker' => 'test-value',
        ]);

        $this->actingAs($user, 'web');

        $this->assertAuthenticatedAs($user, 'web');

        $response = $this->statefulRequest()->postJson('/api/v1/logout');
        $response->assertNoContent();

        $this->assertGuest('web');
        $this->assertFalse(
            session()->has('session_marker'),
            'The session marker should be removed after logout.'
        );
    }

    public function test_guest_cannot_access_protected_routes(): void
    {
        $this->getJson('/api/v1/user')->assertUnauthorized();
        $this->getJson('/api/v1/categories')->assertUnauthorized();
    }

    public function test_authenticated_user_can_access_protected_routes(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum');

        $this->getJson('/api/v1/user')
            ->assertOk()
            ->assertJsonPath('id', $user->id);
    }
}
