<?php

namespace Tests\Feature;

use Niang\Core\Testing\TestCase;

class ErrorHandlingTest extends TestCase
{
    public function test_unknown_route_returns_404_html_page(): void
    {
        $this->get('/route-inexistante')
            ->assertStatus(404)
            ->assertSee('404');
    }

    public function test_unknown_route_returns_404_json_when_requested(): void
    {
        $response = $this->call('GET', '/route-inexistante', [], ['Accept' => 'application/json']);

        $response->assertStatus(404);
        $this->assertArrayHasKey('message', $response->json());
    }

    public function test_wrong_http_method_returns_405(): void
    {
        // /hello/{name} n'accepte que GET.
        $this->call('POST', '/hello/Awa')->assertStatus(405);
    }

    public function test_tag_not_found_returns_404_via_abort_helper(): void
    {
        $this->get('/tags/999999')->assertStatus(404);
    }

    public function test_missing_csrf_token_returns_419(): void
    {
        $this->post('/contact', ['name' => 'Awa'])->assertStatus(419);
    }

    public function test_error_responses_still_carry_security_headers(): void
    {
        $response = $this->get('/route-inexistante');

        $response->assertStatus(404);
        $this->assertSame('DENY', $response->header('X-Frame-Options'));
    }
}
