<?php

namespace Tests\Unit;

use Niang\Core\DebugToolbar;
use Niang\Core\Http\Response;
use PHPUnit\Framework\TestCase;

class DebugToolbarTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv('APP_DEBUG'); // efface la surcharge : revient à la vraie valeur (.env/.env.testing)
        parent::tearDown();
    }

    public function test_injects_into_an_html_response_with_a_closing_body_tag(): void
    {
        putenv('APP_DEBUG=true');

        $response = Response::html('<html><body>Bonjour</body></html>');
        $result = DebugToolbar::inject($response, hrtime(true));

        $this->assertStringContainsString('requête(s) SQL', $result->getContent());
        $this->assertStringEndsWith('</body></html>', $result->getContent());
    }

    public function test_does_not_inject_when_app_debug_is_false(): void
    {
        putenv('APP_DEBUG=false');

        $response = Response::html('<html><body>Bonjour</body></html>');
        $result = DebugToolbar::inject($response, hrtime(true));

        $this->assertSame($response->getContent(), $result->getContent());
    }

    public function test_does_not_inject_into_a_json_response(): void
    {
        putenv('APP_DEBUG=true');

        $response = Response::json(['ok' => true]);
        $result = DebugToolbar::inject($response, hrtime(true));

        $this->assertSame($response->getContent(), $result->getContent());
    }

    public function test_does_not_inject_without_a_closing_body_tag(): void
    {
        putenv('APP_DEBUG=true');

        $response = Response::html('<p>Fragment sans balise body</p>');
        $result = DebugToolbar::inject($response, hrtime(true));

        $this->assertSame($response->getContent(), $result->getContent());
    }

    public function test_original_content_is_preserved(): void
    {
        putenv('APP_DEBUG=true');

        $response = Response::html('<html><body><h1>Contenu de la page</h1></body></html>');
        $result = DebugToolbar::inject($response, hrtime(true));

        $this->assertStringContainsString('<h1>Contenu de la page</h1>', $result->getContent());
    }
}
