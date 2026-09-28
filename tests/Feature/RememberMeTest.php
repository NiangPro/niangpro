<?php

namespace Tests\Feature;

use App\Models\User;
use Niang\Core\Auth;
use Niang\Core\Cookie;
use Niang\Core\Csrf;
use Niang\Core\Database\QueryBuilder;
use Niang\Core\Hash;
use Niang\Core\Session;
use Niang\Core\Testing\RefreshDatabase;
use Niang\Core\Testing\TestCase;

class RememberMeTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(string $password = 'motdepasse123'): array
    {
        $id = User::create(['name' => 'Awa', 'email' => 'awa@example.test', 'password' => Hash::make($password)]);

        return User::find($id);
    }

    private function login(bool $remember): \Niang\Core\Testing\TestResponse
    {
        return $this->post('/login', [
            '_token' => Csrf::token(),
            'email' => 'awa@example.test',
            'password' => 'motdepasse123',
            'remember' => $remember ? '1' : null,
        ]);
    }

    /** Ce que le navigateur renverrait à la requête suivante : le cookie chiffré par l'application. */
    private function sendBack(string $name, string $value): void
    {
        $encode = new \ReflectionMethod(Cookie::class, 'encode');
        $encode->setAccessible(true);
        $_COOKIE[$name] = $encode->invoke(null, $name, $value, 60);
    }

    /** Simule l'expiration de la session : seul le cookie de 30 jours reste. */
    private function expireSession(): void
    {
        Session::forget('_auth_user_id');
    }

    public function test_without_remember_no_cookie_is_set(): void
    {
        $this->createUser();

        $response = $this->login(false);

        $response->assertRedirect('/');
        $this->assertNull($response->cookie(Auth::REMEMBER_COOKIE));
    }

    public function test_remember_sets_a_cookie_and_logs_back_in_after_the_session_expired(): void
    {
        $user = $this->createUser();

        $cookie = $this->login(true)->cookie(Auth::REMEMBER_COOKIE);
        $this->assertNotNull($cookie);

        $this->expireSession();
        $this->assertFalse(Auth::check());

        $this->sendBack(Auth::REMEMBER_COOKIE, $cookie);
        $this->assertSame($user['id'], Auth::id());
    }

    public function test_the_token_is_reused_across_devices(): void
    {
        $this->createUser();

        $first = $this->login(true)->cookie(Auth::REMEMBER_COOKIE);
        Auth::logout();
        $second = $this->login(true)->cookie(Auth::REMEMBER_COOKIE);

        $this->assertSame($first, $second, 'se connecter sur un 2e appareil ne doit pas invalider le 1er');
    }

    public function test_a_forged_or_outdated_token_is_refused_and_forgotten(): void
    {
        $user = $this->createUser();
        $this->login(true);
        $this->expireSession();

        $this->sendBack(Auth::REMEMBER_COOKIE, $user['id'] . '|' . str_repeat('0', 64));
        $this->assertFalse(Auth::check());
        $this->assertArrayNotHasKey(Auth::REMEMBER_COOKIE, $_COOKIE, 'le cookie invalide ne doit plus être relu');

        // Valeur en clair (non chiffrée par l'application) : ignorée.
        $_COOKIE[Auth::REMEMBER_COOKIE] = $user['id'] . '|' . User::find($user['id'])['remember_token'];
        $this->assertFalse(Auth::check());
    }

    public function test_logout_forgets_the_cookie_but_keeps_other_devices(): void
    {
        $this->createUser();
        $cookie = (string) $this->login(true)->cookie(Auth::REMEMBER_COOKIE);
        $this->sendBack(Auth::REMEMBER_COOKIE, $cookie);

        $response = $this->post('/logout', ['_token' => Csrf::token()]);

        $response->assertCookieForgotten(Auth::REMEMBER_COOKIE);

        // Un autre appareil avec le même cookie reste connecté.
        $this->sendBack(Auth::REMEMBER_COOKIE, $cookie);
        $this->assertTrue(Auth::check());
    }

    public function test_logout_everywhere_invalidates_every_remembered_device(): void
    {
        $this->createUser();
        $cookie = (string) $this->login(true)->cookie(Auth::REMEMBER_COOKIE);

        Auth::logoutEverywhere();

        $this->sendBack(Auth::REMEMBER_COOKIE, $cookie);
        $this->assertFalse(Auth::check());
    }

    public function test_an_outdated_password_hash_is_upgraded_at_login(): void
    {
        $id = User::create(['name' => 'Awa', 'email' => 'awa@example.test', 'password' => 'x']);
        $old = password_hash('motdepasse123', PASSWORD_BCRYPT, ['cost' => 4]);
        (new QueryBuilder('users'))->where('id', $id)->update(['password' => $old]);
        $this->assertTrue(Hash::needsRehash($old));

        $this->login(false)->assertRedirect('/');

        $new = User::find($id)['password'];
        $this->assertNotSame($old, $new);
        $this->assertFalse(Hash::needsRehash($new));
        $this->assertTrue(Hash::check('motdepasse123', $new));
    }

    public function test_a_current_hash_is_left_untouched(): void
    {
        $user = $this->createUser();

        $this->login(false);

        $this->assertSame($user['password'], User::find($user['id'])['password']);
    }

    public function test_api_me_never_exposes_the_password_hash_or_remember_token(): void
    {
        $this->createUser();
        $token = $this->post('/api/tokens', ['email' => 'awa@example.test', 'password' => 'motdepasse123', 'device_name' => 't'])->json()['token'];

        $user = $this->get('/api/me', ['Authorization' => "Bearer $token"])->json()['user'];

        $this->assertSame('awa@example.test', $user['email']);
        $this->assertArrayNotHasKey('password', $user);
        $this->assertArrayNotHasKey('remember_token', $user);
    }
}
