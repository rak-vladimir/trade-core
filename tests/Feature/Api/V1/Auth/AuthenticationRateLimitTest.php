<?php

namespace Tests\Feature\Api\V1\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_is_throttled_after_five_attempts(): void
    {
        // Отдельный IP, чтобы не зависеть от счётчиков других тестов
        // и предыдущих запусков.
        $ip = '198.51.100.' . random_int(1, 254);

        $credentials = [
            'email'    => 'test@example.com',
            'password' => 'wrong-password',
        ];

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->withServerVariables([
                'REMOTE_ADDR' => $ip,
            ])->postJson('/api/v1/login', $credentials)
                ->assertUnprocessable()
                ->assertJsonValidationErrors('email');
        }

        $this->postJson('/api/v1/login', $credentials)
            ->assertTooManyRequests();
    }
}
