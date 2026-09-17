<?php

namespace Tests\Feature;

use Niang\Core\Testing\TestCase;

/** Vérifie le branchement réel dans Application::handle() (voir tests/Unit/DebugToolbarTest.php pour la logique pure). */
class DebugToolbarTest extends TestCase
{
    public function test_toolbar_appears_on_an_html_page_with_app_debug_true(): void
    {
        // .env.testing fixe APP_DEBUG=true.
        $response = $this->get('/contact');

        $response->assertOk();
        $response->assertSee('requête(s) SQL');
    }

    public function test_toolbar_does_not_appear_on_a_json_response(): void
    {
        $response = $this->get('/posts');

        $response->assertOk();
        $response->assertDontSee('requête(s) SQL');
    }
}
