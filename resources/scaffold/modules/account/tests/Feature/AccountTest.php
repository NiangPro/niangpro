<?php

namespace Tests\Feature;

use App\Models\User;
use Niang\Core\ApiToken;
use Niang\Core\Auth;
use Niang\Core\Csrf;
use Niang\Core\Hash;
use Niang\Core\Testing\RefreshDatabase;
use Niang\Core\Testing\TestCase;

/**
 * Livré avec le module « account » (starters auth et saas) : inscription, connexion, pages du
 * compte (profil, mot de passe, suppression), et chaque page d'authentification répond.
 */
class AccountTest extends TestCase
{
    use RefreshDatabase;

    private function member(string $email = 'awa@example.com'): array
    {
        $id = User::create(['name' => 'Awa Diop', 'email' => $email, 'password' => Hash::make('secret123')]);

        return User::find($id);
    }

    public function test_every_authentication_page_answers(): void
    {
        foreach (['/login' => 'Connexion', '/register' => 'Créer un compte', '/forgot-password' => 'Mot de passe oublié'] as $uri => $text) {
            $this->get($uri)->assertOk()->assertSee($text)->assertSee('name="_token"');
        }
    }

    public function test_registering_signs_the_member_in_and_opens_the_dashboard(): void
    {
        $this->post('/register', [
            '_token' => Csrf::token(),
            'name' => 'Moussa Ndiaye',
            'email' => 'moussa@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertRedirect('/tableau-de-bord');

        $this->assertSame('moussa@example.com', Auth::user()['email']);
        $this->get('/tableau-de-bord')->assertOk()->assertSee('Moussa Ndiaye');
    }

    public function test_logging_in_and_out(): void
    {
        $this->member();

        $this->post('/login', ['_token' => Csrf::token(), 'email' => 'awa@example.com', 'password' => 'mauvais'])->assertRedirect('/login');
        $this->assertNull(Auth::user());

        $this->post('/login', ['_token' => Csrf::token(), 'email' => 'awa@example.com', 'password' => 'secret123'])->assertRedirect('/tableau-de-bord');
        $this->assertNotNull(Auth::user());

        $this->post('/logout', ['_token' => Csrf::token()])->assertRedirect('/');
        $this->assertNull(Auth::user());
    }

    public function test_member_pages_need_a_login(): void
    {
        foreach (['/tableau-de-bord', '/compte', '/user/two-factor'] as $uri) {
            $this->get($uri)->assertRedirect('/login');
        }
    }

    public function test_the_profile_can_be_updated_and_a_new_email_is_no_longer_verified(): void
    {
        $member = $this->member();
        User::forceUpdate($member['id'], ['email_verified_at' => date('Y-m-d H:i:s')]);
        Auth::login(User::find($member['id']));

        $this->get('/compte')->assertOk()->assertSee('awa@example.com');
        $this->post('/compte/profil', ['_token' => Csrf::token(), 'name' => 'Awa D.', 'email' => 'awa.d@example.com'])->assertRedirect('/compte');

        $updated = User::find($member['id']);
        $this->assertSame('Awa D.', $updated['name']);
        $this->assertSame('awa.d@example.com', $updated['email']);
        $this->assertNull($updated['email_verified_at']);
    }

    public function test_an_email_taken_by_another_account_is_refused(): void
    {
        $this->member('pris@example.com');
        Auth::login($this->member());

        $this->post('/compte/profil', ['_token' => Csrf::token(), 'name' => 'Awa', 'email' => 'pris@example.com']);

        $this->assertSame('awa@example.com', Auth::user()['email']);
    }

    public function test_the_password_changes_only_with_the_current_one(): void
    {
        Auth::login($this->member());

        $this->post('/compte/mot-de-passe', ['_token' => Csrf::token(), 'current_password' => 'faux', 'password' => 'nouveau123', 'password_confirmation' => 'nouveau123']);
        $this->assertTrue(Hash::check('secret123', Auth::user()['password']));

        $this->post('/compte/mot-de-passe', ['_token' => Csrf::token(), 'current_password' => 'secret123', 'password' => 'nouveau123', 'password_confirmation' => 'nouveau123'])
            ->assertRedirect('/compte');
        $this->assertTrue(Hash::check('nouveau123', Auth::user()['password']));
    }

    public function test_deleting_the_account_needs_the_password_and_erases_its_tokens(): void
    {
        $member = $this->member();
        $token = ApiToken::issue($member, 'mobile');
        Auth::login($member);

        $this->post('/compte/supprimer', ['_token' => Csrf::token(), 'delete_password' => 'faux']);
        $this->assertNotNull(User::find($member['id']));

        $this->post('/compte/supprimer', ['_token' => Csrf::token(), 'delete_password' => 'secret123'])->assertRedirect('/');

        $this->assertNull(User::find($member['id']));
        $this->assertNull(ApiToken::resolve($token));
        $this->assertNull(Auth::user());
    }

    public function test_account_forms_are_protected_against_csrf(): void
    {
        Auth::login($this->member());

        $this->post('/compte/profil', ['name' => 'Pirate', 'email' => 'pirate@example.com'])->assertStatus(419);
        $this->post('/compte/supprimer', ['delete_password' => 'secret123'])->assertStatus(419);
        $this->assertSame('Awa Diop', Auth::user()['name']);
    }
}
