<?php

namespace Tests\Feature;

use App\Models\User;
use Niang\Core\Auth;
use Niang\Core\Hash;
use Niang\Core\Testing\RefreshDatabase;
use Niang\Core\Testing\TestCase;

/** Livré avec le starter « auth » : accueil, espace membre, et ce que voit un visiteur ou un membre. */
class AuthStarterTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_visitor_is_invited_to_sign_up(): void
    {
        $this->get('/')->assertOk()->assertSee('href="/register"')->assertSee('href="/login"')->assertDontSee('Déconnexion');
    }

    public function test_a_member_sees_the_dashboard_and_account_links(): void
    {
        $id = User::create(['name' => 'Awa Diop', 'email' => 'awa@example.com', 'password' => Hash::make('secret123')]);
        Auth::login(User::find($id));

        $this->get('/')->assertOk()->assertSee('Aller au tableau de bord')->assertSee('Déconnexion');
        $this->get('/tableau-de-bord')->assertOk()->assertSee('Bonjour Awa Diop')->assertSee('href="/compte"');
    }

    public function test_health_probes_answer(): void
    {
        $this->get('/health/live')->assertOk();
    }
}
