<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\AdminMenu;
use Niang\Core\Auth;
use Niang\Core\Csrf;
use Niang\Core\Database\Seeder;
use Niang\Core\Hash;
use Niang\Core\Testing\RefreshDatabase;
use Niang\Core\Testing\TestCase;

/**
 * Livré avec le module d'administration (thèmes boutique et blog) : le compte de test existe après
 * `niang db:seed`, un administrateur arrive sur /admin après connexion, personne d'autre n'y entre,
 * et chaque section de la barre latérale répond.
 */
class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var Seeder $seeder */
        $seeder = require base_path('database/seeders/DatabaseSeeder.php');
        $seeder->run();
    }

    private function admin(): array
    {
        return User::query()->where('email', 'admin@example.com')->first();
    }

    public function test_the_seeder_creates_the_documented_test_admin_account_once(): void
    {
        $admin = $this->admin();

        $this->assertSame('admin', $admin['role']);
        $this->assertTrue(Hash::check('admin1234', $admin['password']));

        (require base_path('database/seeders/AdminUserSeeder.php'))->run();
        $this->assertCount(1, User::where('email', 'admin@example.com'));
    }

    public function test_an_admin_lands_on_the_dashboard_after_login(): void
    {
        $this->post('/login', ['_token' => Csrf::token(), 'email' => 'admin@example.com', 'password' => 'admin1234'])
            ->assertRedirect('/admin');

        $this->get('/admin')->assertOk()->assertSee('Tableau de bord')->assertSee('Administration');

        // Déjà connecté : /login renvoie lui aussi vers le tableau de bord.
        $this->get('/login')->assertRedirect('/admin');
    }

    public function test_guests_are_sent_to_the_login_page(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->get('/admin/parametres')->assertRedirect('/login');
    }

    public function test_a_regular_account_is_refused_and_stays_on_the_public_site(): void
    {
        User::create(['name' => 'Lecteur', 'email' => 'lecteur@example.test', 'password' => Hash::make('motdepasse123')]);

        $this->post('/login', ['_token' => Csrf::token(), 'email' => 'lecteur@example.test', 'password' => 'motdepasse123'])
            ->assertRedirect('/');

        $this->get('/admin')->assertStatus(403);
        $this->get('/admin/parametres')->assertStatus(403);
    }

    public function test_every_sidebar_section_responds(): void
    {
        Auth::login($this->admin());

        foreach (AdminMenu::sections() as $section) {
            foreach ($section['items'] as $item) {
                $this->get($item['href'])->assertOk()->assertSee(e($item['label']));
            }
        }
    }

    public function test_the_admin_can_change_the_test_password(): void
    {
        Auth::login($this->admin());

        $this->post('/admin/parametres/mot-de-passe', [
            '_token' => Csrf::token(),
            'current_password' => 'mauvais',
            'password' => 'nouveau-secret',
            'password_confirmation' => 'nouveau-secret',
        ])->assertRedirect('/admin/parametres');
        $this->assertTrue(Hash::check('admin1234', $this->admin()['password']));

        $this->post('/admin/parametres/mot-de-passe', [
            '_token' => Csrf::token(),
            'current_password' => 'admin1234',
            'password' => 'nouveau-secret',
            'password_confirmation' => 'nouveau-secret',
        ])->assertRedirect('/admin/parametres');
        $this->assertTrue(Hash::check('nouveau-secret', $this->admin()['password']));
    }

    public function test_admin_forms_require_a_csrf_token(): void
    {
        Auth::login($this->admin());

        $this->post('/admin/parametres/profil', ['name' => 'Pirate', 'email' => 'pirate@example.test'])->assertStatus(419);
        $this->assertSame('admin@example.com', $this->admin()['email']);
    }
}
