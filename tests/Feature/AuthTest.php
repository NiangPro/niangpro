<?php

namespace Tests\Feature;

use App\Models\User;
use Niang\Core\Csrf;
use Niang\Core\Hash;
use Niang\Core\Testing\RefreshDatabase;
use Niang\Core\Testing\TestCase;

/**
 * Démontre RefreshDatabase : le second test vérifierait un état pollué par le premier si la
 * transaction n'était pas annulée entre les deux (la base :memory: survit tout le run PHPUnit).
 */
class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_a_user_and_logs_them_in(): void
    {
        $this->post('/register', [
            '_token' => Csrf::token(),
            'name' => 'Awa Diop',
            'email' => 'awa@example.test',
            'password' => 'motdepasse123',
            'password_confirmation' => 'motdepasse123',
        ])->assertRedirect('/');

        $this->assertNotEmpty(User::where('email', 'awa@example.test'));
    }

    public function test_previous_test_left_no_trace_thanks_to_refresh_database(): void
    {
        $this->assertEmpty(User::where('email', 'awa@example.test'));
    }

    public function test_login_fails_with_wrong_password(): void
    {
        // Créé directement via le modèle (pas via /register) pour ne pas déclencher la connexion
        // automatique : /login est protégée par RedirectIfAuthenticated et redirigerait vers '/'
        // avant même de vérifier les identifiants si l'on était déjà connecté.
        User::create([
            'name' => 'Fatou',
            'email' => 'fatou@example.test',
            'password' => Hash::make('motdepasse123'),
        ]);

        $this->post('/login', [
            '_token' => Csrf::token(),
            'email' => 'fatou@example.test',
            'password' => 'mauvais-mot-de-passe',
        ])->assertRedirect('/login');
    }
}
