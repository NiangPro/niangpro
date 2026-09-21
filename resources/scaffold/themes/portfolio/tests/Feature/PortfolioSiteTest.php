<?php

namespace Tests\Feature;

use Niang\Core\Csrf;
use Niang\Core\Testing\TestCase;

/**
 * Livré avec le thème « portfolio » : chaque page visiteur répond, chaque projet de config/site.php a
 * sa page (avec galerie et navigation), et le thème respecte ses engagements (aucune ressource
 * externe, aucun script inline, des alternatives textuelles).
 */
class PortfolioSiteTest extends TestCase
{
    /** @return array<string, array{0: string, 1: string}> */
    public static function pages(): array
    {
        return [
            'accueil' => ['/', 'Projets phares'],
            'projets' => ['/projets', 'Prisme'],
            'un projet' => ['/projets/prisme-design-system', 'Le défi'],
            'à propos' => ['/a-propos', 'Compétences'],
            'contact' => ['/contact', 'Envoyer le message'],
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

    public function test_every_configured_project_has_a_page_with_its_gallery_and_results(): void
    {
        $projects = config('site.projects');
        $this->assertNotEmpty($projects);

        foreach ($projects as $project) {
            $response = $this->get('/projets/' . $project['slug'])->assertOk();

            $response->assertSee('Le défi')->assertSee('Résultats');

            foreach ($project['gallery'] as $caption) {
                $response->assertSee(htmlspecialchars($caption, ENT_QUOTES, 'UTF-8'));
            }
        }
    }

    public function test_project_pages_link_to_the_previous_and_next_projects_circularly(): void
    {
        $projects = config('site.projects');
        $first = $projects[0];
        $last = $projects[count($projects) - 1];

        $this->get('/projets/' . $first['slug'])->assertSee('href="/projets/' . $last['slug'] . '" rel="prev"')->assertSee('href="/projets/' . $projects[1]['slug'] . '" rel="next"');
        $this->get('/projets/' . $last['slug'])->assertSee('href="/projets/' . $projects[count($projects) - 2]['slug'] . '" rel="prev"')->assertSee('href="/projets/' . $first['slug'] . '" rel="next"');
    }

    public function test_projects_can_be_filtered_by_category(): void
    {
        $this->call('GET', '/projets', ['categorie' => 'mobile'])->assertOk()->assertSee('Halte')->assertDontSee('Prisme');
    }

    public function test_an_unknown_category_is_ignored_instead_of_hiding_everything(): void
    {
        $this->call('GET', '/projets', ['categorie' => 'nimporte-quoi'])->assertOk()->assertSee('Prisme')->assertSee('Halte');
    }

    public function test_an_unknown_project_is_a_themed_404(): void
    {
        $this->get('/projets/inexistant')->assertStatus(404)->assertSee('site-header');
    }

    public function test_the_health_endpoint_is_still_available(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_the_current_section_is_marked_in_the_navigation_including_on_a_project_page(): void
    {
        $this->assertMatchesRegularExpression('#<a href="/projets" aria-current="page">Projets</a>#', $this->get('/projets/prisme-design-system')->content());
    }

    public function test_contact_form_accepts_a_valid_message_and_requires_a_csrf_token(): void
    {
        $this->post('/contact', ['_token' => Csrf::token(), 'name' => 'Awa', 'email' => 'awa@example.test', 'message' => 'Bonjour, parlons de mon projet.'])->assertRedirect('/contact');
        $this->post('/contact', ['name' => 'Awa'])->assertStatus(419);
    }

    public function test_unknown_pages_show_the_themed_404(): void
    {
        $this->get('/page-inexistante')->assertStatus(404)->assertSee('Cette page n\'existe pas');
    }
}
