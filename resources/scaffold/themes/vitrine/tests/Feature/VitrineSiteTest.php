<?php

namespace Tests\Feature;

use Niang\Core\Csrf;
use Niang\Core\Testing\TestCase;

/**
 * Livré avec le thème « vitrine » : chaque page visiteur répond, le formulaire de contact est
 * protégé, et le thème respecte ses engagements (aucune ressource externe, aucun script inline
 * — la CSP par défaut les bloquerait —, des alternatives textuelles).
 */
class VitrineSiteTest extends TestCase
{
    /** @return array<string, array{0: string, 1: string}> */
    public static function pages(): array
    {
        return [
            'accueil' => ['/', 'Des intérieurs qui vous'],
            'à propos' => ['/a-propos', 'Notre histoire'],
            'services' => ['/services', 'Conception intérieure'],
            'réalisations' => ['/realisations', 'Loft de la Manufacture'],
            'faq' => ['/faq', 'Questions fréquentes'],
            'contact' => ['/contact', 'Envoyer le message'],
            'mentions légales' => ['/mentions-legales', 'Éditeur du site'],
            'confidentialité' => ['/politique-de-confidentialite', 'Vos droits'],
        ];
    }

    /** @dataProvider pages */
    public function test_every_public_page_responds_with_its_content(string $uri, string $expected): void
    {
        $this->get($uri)->assertOk()->assertSee($expected);
    }

    /** @dataProvider pages */
    public function test_every_page_shares_the_same_landmarks_and_stylesheets(string $uri): void
    {
        $html = $this->get($uri)->content();

        foreach (['<header class="site-header">', '<main id="contenu"', '<footer class="site-footer">', 'href="#contenu"', '/css/niang.css', '/css/theme.css', '<html lang="fr">'] as $expected) {
            $this->assertStringContainsString($expected, $html);
        }
    }

    /** @dataProvider pages */
    public function test_pages_load_nothing_from_an_external_host_and_have_no_inline_script_or_image_without_alt(string $uri): void
    {
        $html = $this->get($uri)->content();

        $this->assertDoesNotMatchRegularExpression('#(?:src|href)="https?://#', $html, 'Ressource externe (CDN) détectée.');
        $this->assertDoesNotMatchRegularExpression('#<script(?![^>]*\bsrc=)#', $html, 'Script inline : bloqué par la CSP par défaut.');
        $this->assertDoesNotMatchRegularExpression('#<img(?![^>]*\balt=)#', $html, 'Image sans attribut alt.');
    }

    public function test_the_current_page_is_marked_in_the_navigation(): void
    {
        $html = $this->get('/services')->content();

        $this->assertMatchesRegularExpression('#<a href="/services" aria-current="page">Services</a>#', $html);
        $this->assertStringNotContainsString('<a href="/a-propos" aria-current="page">', $html);
    }

    public function test_unknown_pages_show_the_themed_404(): void
    {
        $this->get('/page-inexistante')->assertStatus(404)->assertSee('Cette page n\'existe pas')->assertSee('site-header');
    }

    public function test_the_health_endpoint_is_still_available(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_contact_form_rejects_invalid_input_and_keeps_the_user_on_the_form(): void
    {
        $this->post('/contact', ['_token' => Csrf::token(), 'name' => 'A'])->assertRedirect();
    }

    public function test_contact_form_accepts_a_valid_message(): void
    {
        $this->post('/contact', [
            '_token' => Csrf::token(),
            'name' => 'Camille',
            'email' => 'camille@example.test',
            'message' => 'Bonjour, je souhaite rénover mon appartement.',
        ])->assertRedirect('/contact');
    }

    public function test_contact_form_requires_a_csrf_token(): void
    {
        $this->post('/contact', ['name' => 'Camille'])->assertStatus(419);
    }
}
