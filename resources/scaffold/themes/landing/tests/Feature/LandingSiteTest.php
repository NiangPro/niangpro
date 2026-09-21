<?php

namespace Tests\Feature;

use Niang\Core\Testing\TestCase;

/**
 * Livré avec le thème « landing » : la page unique contient toutes ses sections, chaque lien de
 * navigation mène à une section qui existe, et le thème respecte ses engagements (aucune
 * ressource externe, aucun script inline, des alternatives textuelles).
 */
class LandingSiteTest extends TestCase
{
    private const SECTIONS = ['fonctionnalites', 'fonctionnement', 'temoignages', 'tarifs', 'faq'];

    /** @return array<string, array{0: string, 1: string}> */
    public static function pages(): array
    {
        return [
            'landing' => ['/', 'Planifiez la semaine'],
            'mentions légales' => ['/mentions-legales', 'Éditeur du site'],
        ];
    }

    /** @dataProvider pages */
    public function test_every_public_page_responds_with_its_content(string $uri, string $expected): void
    {
        $this->get($uri)->assertOk()->assertSee($expected);
    }

    /** @dataProvider pages */
    public function test_pages_load_nothing_from_an_external_host_and_have_no_inline_script_or_image_without_alt(string $uri): void
    {
        $html = $this->get($uri)->content();

        $this->assertDoesNotMatchRegularExpression('#(?:src|href)="https?://#', $html, 'Ressource externe (CDN) détectée.');
        $this->assertDoesNotMatchRegularExpression('#<script(?![^>]*\bsrc=)#', $html, 'Script inline : bloqué par la CSP par défaut.');
        $this->assertDoesNotMatchRegularExpression('#<img(?![^>]*\balt=)#', $html, 'Image sans attribut alt.');
    }

    public function test_the_page_has_every_anchored_section(): void
    {
        $html = $this->get('/')->content();

        foreach (self::SECTIONS as $id) {
            $this->assertStringContainsString("id=\"$id\"", $html, "Section #$id absente.");
        }
    }

    public function test_every_navigation_link_points_to_an_existing_section(): void
    {
        $html = $this->get('/')->content();

        foreach (config('site.nav') as $item) {
            $this->assertStringStartsWith('/#', $item['href'], 'Un lien en « #… » seul ne fonctionnerait pas depuis les mentions légales.');
            $this->assertStringContainsString('id="' . substr($item['href'], 2) . '"', $html, "Le lien « {$item['label']} » mène à une section inexistante.");
        }
    }

    public function test_navigation_links_also_work_from_the_legal_page(): void
    {
        $this->get('/mentions-legales')->assertOk()->assertSee('href="/#tarifs"');
    }

    public function test_pricing_shows_every_plan_with_monthly_and_yearly_prices(): void
    {
        $html = $this->get('/')->assertOk()->content();

        foreach (config('site.plans') as $plan) {
            $this->assertStringContainsString($plan['name'], $html);
        }

        $this->assertStringContainsString('data-price="monthly"', $html);
        $this->assertStringContainsString('data-price="yearly" hidden', $html, 'Le prix annuel doit rester caché sans JavaScript.');
        $this->assertStringContainsString('Le plus choisi', $html);
    }

    public function test_the_billing_toggle_is_hidden_until_javascript_reveals_it(): void
    {
        $this->assertMatchesRegularExpression('#<div class="billing" data-billing hidden>#', $this->get('/')->content());
    }

    public function test_every_faq_question_is_rendered(): void
    {
        $html = $this->get('/')->content();

        foreach (config('site.faq') as $item) {
            $this->assertStringContainsString(htmlspecialchars($item['q'], ENT_QUOTES, 'UTF-8'), $html);
        }
    }

    public function test_the_health_endpoint_is_still_available(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_unknown_pages_show_the_themed_404(): void
    {
        $this->get('/page-inexistante')->assertStatus(404)->assertSee('Cette page n\'existe pas')->assertSee('site-header');
    }

    public function test_there_is_no_contact_route_in_a_single_page_theme(): void
    {
        $this->get('/contact')->assertStatus(404);
    }
}
