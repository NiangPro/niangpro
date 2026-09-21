<?php

namespace Tests\Feature;

use App\Models\Post;
use Niang\Core\Csrf;
use Niang\Core\Database\Seeder;
use Niang\Core\Testing\RefreshDatabase;
use Niang\Core\Testing\TestCase;

/**
 * Livré avec le thème « blog » : chaque page visiteur répond, la liste est paginée (PostController::page),
 * les articles sont accessibles par leur slug, et le corps des articles ne peut pas injecter de HTML.
 */
class BlogSiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var Seeder $seeder */
        $seeder = require base_path('database/seeders/DatabaseSeeder.php');
        $seeder->run();
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function pages(): array
    {
        return [
            'accueil' => ['/', 'À la une'],
            'articles' => ['/blog', 'Articles'],
            'article' => ['/blog/pourquoi-la-simplicite-gagne', 'Le coût caché de chaque option'],
            'thèmes' => ['/tags', 'Catégories et thèmes'],
            'un thème' => ['/tags/accessibilite', '#accessibilite'],
            'une catégorie' => ['/categories/guides', 'Guides'],
            'à propos' => ['/a-propos', 'Écrire pour comprendre'],
            'contact' => ['/contact', 'Envoyer le message'],
            'api des tags' => ['/api/tags', 'accessibilite'],
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
        $content = $this->get($uri)->content();

        if (str_starts_with($uri, '/api/')) {
            $this->addToAssertionCount(1);

            return;
        }

        $this->assertDoesNotMatchRegularExpression('#(?:src|href)="https?://#', $content, 'Ressource externe (CDN) détectée.');
        $this->assertDoesNotMatchRegularExpression('#<script(?![^>]*\bsrc=)#', $content, 'Script inline : bloqué par la CSP par défaut.');
        $this->assertDoesNotMatchRegularExpression('#<img(?![^>]*\balt=)#', $content, 'Image sans attribut alt.');
    }

    public function test_the_seeder_is_idempotent(): void
    {
        $count = count(Post::all());
        $this->assertSame(8, $count);

        /** @var Seeder $seeder */
        $seeder = require base_path('database/seeders/DatabaseSeeder.php');
        $seeder->run();

        $this->assertCount($count, Post::all());
    }

    public function test_the_list_is_paginated_newest_first_using_the_configured_page_size(): void
    {
        $first = $this->get('/blog')->assertOk();
        $this->assertStringContainsString('Pourquoi la simplicité finit toujours par gagner', $first->content());
        $this->assertStringNotContainsString('Choisir ses couleurs', $first->content());
        $this->assertStringContainsString('page 1 sur 2', $first->content());
        $this->assertStringContainsString('class="pagination"', $first->content());

        $this->call('GET', '/blog', ['page' => 2])->assertOk()->assertSee('Choisir ses couleurs sans sacrifier le contraste')->assertDontSee('Pourquoi la simplicité finit toujours par gagner');
    }

    public function test_an_article_shows_its_tags_reading_time_and_related_articles(): void
    {
        $response = $this->get('/blog/accessibilite-par-ou-commencer')->assertOk();

        $response->assertSee('#accessibilite')->assertSee('#html')->assertSee('min de lecture')->assertSee('À lire ensuite')->assertSee('7 septembre 2026');
        $this->assertStringContainsString('<title>Accessibilité : par où commencer', $response->content());
    }

    public function test_an_unknown_article_tag_or_category_is_a_themed_404(): void
    {
        $this->get('/blog/inexistant')->assertStatus(404)->assertSee('site-header');
        $this->get('/tags/inexistant')->assertStatus(404);
        $this->get('/categories/inexistante')->assertStatus(404);
    }

    public function test_a_tag_page_lists_only_its_articles(): void
    {
        $this->get('/tags/php')->assertOk()->assertSee('Du PHP simple, sans magie')->assertDontSee('Écrire pour le web');
    }

    public function test_a_category_page_lists_only_its_articles(): void
    {
        $this->get('/categories/coulisses')->assertOk()->assertSee('la refonte de notre site')->assertDontSee('Du PHP simple, sans magie');
    }

    public function test_the_body_of_an_article_cannot_inject_html(): void
    {
        Post::create([
            'title' => 'Article piégé',
            'slug' => 'article-piege',
            'body' => "Bonjour <script>alert('xss')</script>\n\n## <img src=x onerror=alert(1)>",
            'category' => 'idees',
        ]);

        $html = $this->get('/blog/article-piege')->assertOk()->content();

        $this->assertStringNotContainsString("<script>alert('xss')", $html);
        $this->assertStringNotContainsString('<img src=x', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_a_post_without_slug_is_still_listed_without_breaking_the_page(): void
    {
        Post::create(['title' => 'Créé à la main', 'body' => 'Contenu court.']);

        $this->get('/blog')->assertOk()->assertSee('Créé à la main');
        $this->get('/')->assertOk();
    }

    public function test_the_tags_api_is_read_only(): void
    {
        $this->get('/api/tags')->assertOk();
        // Une route qui existe pour GET seulement répond 405 aux autres méthodes.
        $this->post('/api/tags', ['name' => 'intrus'])->assertStatus(405);
        $this->delete('/api/tags/1')->assertStatus(404);
    }

    public function test_the_health_endpoint_is_still_available(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_contact_form_accepts_a_valid_message_and_requires_a_csrf_token(): void
    {
        $this->post('/contact', ['_token' => Csrf::token(), 'name' => 'Awa', 'email' => 'awa@example.test', 'message' => 'Merci pour cet article !'])->assertRedirect('/contact');
        $this->post('/contact', ['name' => 'Awa'])->assertStatus(419);
    }

    public function test_unknown_pages_show_the_themed_404(): void
    {
        $this->get('/page-inexistante')->assertStatus(404)->assertSee('Cette page n\'existe pas');
    }
}
