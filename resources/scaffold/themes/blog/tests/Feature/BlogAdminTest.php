<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Niang\Core\Auth;
use Niang\Core\Csrf;
use Niang\Core\Database\DB;
use Niang\Core\Database\Seeder;
use Niang\Core\Testing\RefreshDatabase;
use Niang\Core\Testing\TestCase;

/**
 * Livré avec le thème « blog » : l'espace rédaction (connexion) existe, et un administrateur gère
 * articles, tags et comptes. L'accès lui-même est couvert par AdminAccessTest.
 */
class BlogAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var Seeder $seeder */
        $seeder = require base_path('database/seeders/DatabaseSeeder.php');
        $seeder->run();
    }

    private function loginAsAdmin(): void
    {
        Auth::login(User::query()->where('email', 'admin@example.com')->first());
    }

    /** @return array<string, mixed> */
    private function postForm(array $overrides = []): array
    {
        return [
            '_token' => Csrf::token(),
            'title' => 'Écrire des tests qui se lisent',
            'slug' => '',
            'excerpt' => 'Un test est aussi une documentation.',
            'body' => "Un bon test raconte une histoire.\n\n## Nommer le comportement\n\nLe nom du test dit ce qui doit arriver.",
            'category' => 'guides',
            'author' => '',
            'published_at' => '',
            ...$overrides,
        ];
    }

    public function test_the_login_page_is_linked_from_the_footer(): void
    {
        $this->get('/')->assertOk()->assertSee('href="/login"');
        $this->get('/login')->assertOk()->assertSee('Espace rédaction');
    }

    public function test_the_dashboard_summarises_the_blog(): void
    {
        $this->loginAsAdmin();

        $this->get('/admin')
            ->assertOk()
            ->assertSee('Articles publiés')
            ->assertSee('Pourquoi la simplicité finit toujours par gagner')
            ->assertSee('#design');
    }

    public function test_an_article_can_be_written_edited_and_deleted(): void
    {
        $this->loginAsAdmin();
        $tag = Tag::query()->where('name', 'php')->first();

        $this->post('/admin/articles', $this->postForm(['tags' => [$tag['id']]]));
        $post = Post::query()->where('slug', 'ecrire-des-tests-qui-se-lisent')->first();

        $this->assertNotNull($post);
        $this->assertSame('Administrateur', $post['author']);
        $this->assertSame([$tag['id']], array_map('intval', array_column(Post::tags($post['id']), 'id')));
        $this->get('/blog/ecrire-des-tests-qui-se-lisent')->assertOk()->assertSee('Nommer le comportement');

        $this->get('/admin/articles/' . $post['id'] . '/modifier')->assertOk()->assertSee('Écrire des tests qui se lisent');
        $this->post('/admin/articles/' . $post['id'], $this->postForm(['title' => 'Des tests lisibles', 'slug' => 'tests-lisibles', 'published_at' => '2026-09-01']))
            ->assertRedirect('/admin/articles/' . $post['id'] . '/modifier');
        $post = Post::find($post['id']);
        $this->assertSame('tests-lisibles', $post['slug']);
        $this->assertStringStartsWith('2026-09-01', $post['created_at']);
        $this->assertSame([], Post::tags($post['id']));

        $this->post('/admin/articles/' . $post['id'] . '/supprimer', ['_token' => Csrf::token()])->assertRedirect('/admin/articles');
        $this->assertNull(Post::find($post['id']));
    }

    public function test_a_slug_already_used_by_another_article_is_refused(): void
    {
        $this->loginAsAdmin();

        $this->post('/admin/articles', $this->postForm(['slug' => 'ecrire-pour-le-web']))->assertRedirect('/admin/articles/nouveau');

        $this->assertCount(1, Post::where('slug', 'ecrire-pour-le-web'));
    }

    public function test_articles_can_be_searched_and_filtered(): void
    {
        $this->loginAsAdmin();

        $this->get('/admin/articles?q=simplicit')->assertOk()->assertSee('Pourquoi la simplicité')->assertDontSee('Un lundi à l');
        $this->get('/admin/articles?categorie=coulisses')->assertOk()->assertSee('Un lundi à l')->assertDontSee('Pourquoi la simplicité');
    }

    public function test_tags_can_be_created_and_deleted(): void
    {
        $this->loginAsAdmin();

        $this->post('/admin/tags', ['_token' => Csrf::token(), 'name' => 'Éco-conception'])->assertRedirect('/admin/tags');
        $this->assertNotNull(Tag::query()->where('name', 'eco-conception')->first());

        $design = Tag::query()->where('name', 'design')->first();
        $this->post('/admin/tags/' . $design['id'] . '/supprimer', ['_token' => Csrf::token()])->assertRedirect('/admin/tags');

        $this->assertNull(Tag::find($design['id']));
        $this->assertSame([], DB::select('SELECT * FROM post_tag WHERE tag_id = ?', [$design['id']]));
        $this->get('/tags')->assertOk();
    }

    public function test_an_admin_can_add_a_member_and_manage_roles_but_not_their_own(): void
    {
        $this->loginAsAdmin();

        $this->post('/admin/utilisateurs', [
            '_token' => Csrf::token(),
            'name' => 'Inès Marchand',
            'email' => 'ines@example.test',
            'password' => 'provisoire123',
            'role' => 'user',
        ])->assertRedirect('/admin/utilisateurs');
        $ines = User::query()->where('email', 'ines@example.test')->first();
        $this->assertSame('user', $ines['role']);

        $this->post('/admin/utilisateurs/' . $ines['id'] . '/role', ['_token' => Csrf::token(), 'role' => 'admin']);
        $this->assertTrue(User::isAdmin(User::find($ines['id'])));

        $me = User::query()->where('email', 'admin@example.com')->first();
        $this->post('/admin/utilisateurs/' . $me['id'] . '/role', ['_token' => Csrf::token(), 'role' => 'user']);
        $this->assertTrue(User::isAdmin(User::find($me['id'])));
    }
}
