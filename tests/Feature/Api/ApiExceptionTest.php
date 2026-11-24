<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class ApiExceptionTest extends TestCase
{
    public function test_unknown_api_endpoint_returns_json_404_response(): void
    {
        $this->get('/api/v1/unknown-endpoint')
            ->assertNotFound()
            ->assertJsonStructure([
                'message',
            ]);
    }
}
