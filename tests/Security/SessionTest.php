<?php

namespace Tests\Security;

use App\Models\User;
use Niang\Core\Auth;
use Niang\Core\Config;
use Niang\Core\Csrf;
use Niang\Core\Hash;
use Niang\Core\Testing\RefreshDatabase;
use Niang\Core\Testing\TestCase;

/** Roadmap §55 : fixation et vol de session. */
class SessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_session_id_changes_at_login_and_logout(): void
    {
        User::create(['name' => 'Awa', 'email' => 'awa@example.test', 'password' => Hash::make('motdepasse123')]);
        $before = session_id();

        $this->post('/login', ['_token' => Csrf::token(), 'email' => 'awa@example.test', 'password' => 'motdepasse123']);
        $afterLogin = session_id();
        $this->assertTrue(Auth::check());
        $this->assertNotSame($before, $afterLogin, 'fixation : un identifiant connu avant la connexion ne doit pas servir après');

        Auth::logout();
        $this->assertNotSame($afterLogin, session_id());
        $this->assertFalse(Auth::check());
    }

    public function test_session_cookies_are_http_only_and_same_site(): void
    {
        $params = session_get_cookie_params();

        $this->assertTrue($params['httponly'], 'illisible en JavaScript (vol par XSS)');
        $this->assertContains(strtolower((string) $params['samesite']), ['lax', 'strict']);
    }

    public function test_the_csrf_token_is_bound_to_the_session(): void
    {
        $token = Csrf::token();
        session_regenerate_id(true);
        $_SESSION = [];

        $this->assertNotSame($token, Csrf::token(), 'un jeton volé dans une autre session ne sert à rien');
    }

    public function test_session_lifetime_is_configured(): void
    {
        $this->assertGreaterThan(0, (int) Config::get('session.lifetime'));
    }
}
