<?php

namespace Tests\Feature;

use Niang\Core\Testing\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_responses_carry_the_headers_declared_in_config(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $this->assertSame('DENY', $response->header('X-Frame-Options'));
        $this->assertSame('nosniff', $response->header('X-Content-Type-Options'));
        $this->assertSame('strict-origin-when-cross-origin', $response->header('Referrer-Policy'));
        $this->assertStringContainsString("default-src 'self'", (string) $response->header('Content-Security-Policy'));
    }

    public function test_session_cookie_is_http_only_and_same_site_lax(): void
    {
        // Vérifie le câblage réel de Session::start() (session_set_cookie_params), pas une valeur
        // codée en dur : ces valeurs viennent de config/session.php.
        $params = session_get_cookie_params();

        $this->assertTrue($params['httponly']);
        $this->assertSame('Lax', $params['samesite']);
        $this->assertSame(120 * 60, $params['lifetime']);
    }
}
