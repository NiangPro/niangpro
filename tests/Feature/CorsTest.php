<?php

namespace Tests\Feature;

use Niang\Core\Testing\TestCase;

class CorsTest extends TestCase
{
    public function test_preflight_options_request_is_short_circuited_with_204(): void
    {
        $response = $this->call('OPTIONS', '/api/posts', [], [
            'Origin' => 'https://app.example.com',
            'Access-Control-Request-Method' => 'GET',
        ]);

        $response->assertStatus(204);
        // config/cors.php par défaut : allowed_origins ['*'], supports_credentials false -> '*' littéral.
        $this->assertSame('*', $response->header('Access-Control-Allow-Origin'));
        $this->assertNotNull($response->header('Access-Control-Allow-Methods'));
        $this->assertNotNull($response->header('Access-Control-Allow-Headers'));
    }

    public function test_actual_request_carries_cors_headers_and_runs_normally(): void
    {
        $response = $this->get('/api/posts', ['Origin' => 'https://app.example.com']);

        $response->assertOk();
        $this->assertSame('*', $response->header('Access-Control-Allow-Origin'));
        $this->assertSame('Origin', $response->header('Vary'));
    }

    public function test_no_origin_header_means_no_cors_headers(): void
    {
        $response = $this->get('/api/posts');

        $response->assertOk();
        $this->assertNull($response->header('Access-Control-Allow-Origin'));
    }

    public function test_non_api_route_is_unaffected_by_cors_middleware(): void
    {
        $response = $this->get('/posts', ['Origin' => 'https://app.example.com']);

        $response->assertOk();
        $this->assertNull($response->header('Access-Control-Allow-Origin'));
    }
}
