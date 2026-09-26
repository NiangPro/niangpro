<?php

namespace Tests\Feature;

use App\Models\User;
use Niang\Core\Auth;
use Niang\Core\Config;
use Niang\Core\Hash;
use Niang\Core\Session;
use Niang\Core\Testing\RefreshDatabase;
use Niang\Core\Testing\TestCase;
use Niang\Core\Totp;
use Niang\Core\TwoFactor;
use Tests\Support\FakeHttpServer;

/** Flux complet contre un faux fournisseur (vrai serveur HTTP local, réponses de Google/GitHub). */
class OAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Config::load(base_path());
        parent::tearDown();
    }

    private function configure(string $provider, ?FakeHttpServer $server = null): void
    {
        $base = $server !== null ? "http://127.0.0.1:{$server->port}" : 'http://127.0.0.1:1';

        Config::set("oauth.$provider", [
            'client_id' => 'id-client',
            'client_secret' => 'secret-client',
            'redirect' => "http://localhost/auth/$provider/callback",
            'token_url' => "$base/token",
            'api_url' => $base,
        ]);
    }

    /** Démarre le flux et renvoie le state attendu au retour. */
    private function start(string $provider): string
    {
        $this->get("/auth/$provider/redirect")->assertRedirect();

        return Session::get('_oauth')['state'];
    }

    private function githubServer(bool $verified = true, string $email = 'awa@example.test'): FakeHttpServer
    {
        return new FakeHttpServer(responses: [
            ['status' => 200, 'body' => json_encode(['access_token' => 'gho_jeton', 'token_type' => 'bearer'])],
            ['status' => 200, 'body' => json_encode(['id' => 4242, 'login' => 'awa', 'name' => 'Awa Diop', 'avatar_url' => 'https://avatars.test/awa'])],
            ['status' => 200, 'body' => json_encode([
                ['email' => 'autre@example.test', 'primary' => false, 'verified' => true],
                ['email' => $email, 'primary' => true, 'verified' => $verified],
            ])],
        ]);
    }

    public function test_redirect_sends_state_and_pkce_to_the_provider(): void
    {
        $this->configure('github');

        $location = $this->get('/auth/github/redirect')->header('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
        $pending = Session::get('_oauth');

        $this->assertStringStartsWith('https://github.com/login/oauth/authorize?', $location);
        $this->assertSame('id-client', $query['client_id']);
        $this->assertSame('http://localhost/auth/github/callback', $query['redirect_uri']);
        $this->assertSame('code', $query['response_type']);
        $this->assertSame('read:user user:email', $query['scope']);
        $this->assertSame($pending['state'], $query['state']);
        $this->assertSame('S256', $query['code_challenge_method']);
        $this->assertSame(rtrim(strtr(base64_encode(hash('sha256', $pending['verifier'], true)), '+/', '-_'), '='), $query['code_challenge']);
        $this->assertStringNotContainsString('secret-client', $location);
    }

    public function test_full_github_login_creates_a_verified_user(): void
    {
        $server = $this->githubServer();
        $this->configure('github', $server);
        $state = $this->start('github');

        $this->get("/auth/github/callback?code=code-recu&state=$state")->assertRedirect('/');

        $user = User::where('email', 'awa@example.test')[0];
        $this->assertSame('Awa Diop', $user['name']);
        $this->assertNotNull($user['email_verified_at']);
        $this->assertSame($user['id'], Auth::id());

        [$token, $profile] = $server->all();
        parse_str($token['body'], $sent);
        $this->assertSame('POST /token HTTP/1.1', $token['request']);
        $this->assertSame('code-recu', $sent['code']);
        $this->assertSame('secret-client', $sent['client_secret']);
        $this->assertSame('authorization_code', $sent['grant_type']);
        $this->assertSame(64, strlen($sent['code_verifier']));
        $this->assertSame('GET /user HTTP/1.1', $profile['request']);
        $this->assertSame('Bearer gho_jeton', $profile['headers']['authorization']);
    }

    public function test_an_existing_account_is_reused_by_verified_email(): void
    {
        $id = User::create(['name' => 'Awa', 'email' => 'awa@example.test', 'password' => Hash::make('motdepasse123')]);
        $server = $this->githubServer();
        $this->configure('github', $server);

        $this->get('/auth/github/callback?code=c&state=' . $this->start('github'))->assertRedirect('/');

        $this->assertSame($id, (string) Auth::id());
        $this->assertCount(1, User::where('email', 'awa@example.test'));
    }

    public function test_an_unverified_email_never_logs_into_an_account(): void
    {
        User::create(['name' => 'Victime', 'email' => 'victime@example.test', 'password' => Hash::make('motdepasse123')]);
        $server = $this->githubServer(verified: false, email: 'victime@example.test');
        $this->configure('github', $server);

        $this->get('/auth/github/callback?code=c&state=' . $this->start('github'))->assertRedirect('/login');

        $this->assertFalse(Auth::check());
    }

    public function test_a_forged_or_replayed_state_is_refused(): void
    {
        $this->configure('github');

        $this->get('/auth/github/callback?code=c&state=forge')->assertRedirect('/login');
        $this->assertFalse(Auth::check());

        $state = $this->start('github');
        $this->get('/auth/github/callback?code=c&state=autre-chose')->assertRedirect('/login');
        // L'état est consommé même en cas d'échec : le bon state ne sert plus.
        $this->get("/auth/github/callback?code=c&state=$state")->assertRedirect('/login');
        $this->assertFalse(Auth::check());
    }

    public function test_a_state_from_another_provider_is_refused(): void
    {
        $this->configure('github');
        $this->configure('google');
        $state = $this->start('github');

        $this->get("/auth/google/callback?code=c&state=$state")->assertRedirect('/login');
        $this->assertFalse(Auth::check());
    }

    public function test_the_user_cancelling_at_the_provider_is_handled(): void
    {
        $this->configure('github');
        $state = $this->start('github');

        $this->get("/auth/github/callback?error=access_denied&state=$state")->assertRedirect('/login');
        $this->assertFalse(Auth::check());
    }

    public function test_a_rejected_code_is_handled(): void
    {
        $server = new FakeHttpServer(responses: [['status' => 400, 'body' => json_encode(['error' => 'bad_verification_code'])]]);
        $this->configure('github', $server);

        $this->get('/auth/github/callback?code=c&state=' . $this->start('github'))->assertRedirect('/login');
        $this->assertFalse(Auth::check());
    }

    public function test_full_google_login(): void
    {
        $server = new FakeHttpServer(responses: [
            ['status' => 200, 'body' => json_encode(['access_token' => 'ya29.jeton', 'id_token' => 'x'])],
            ['status' => 200, 'body' => json_encode(['sub' => '1098', 'email' => 'modou@example.test', 'email_verified' => true, 'name' => 'Modou', 'picture' => 'https://img.test/m'])],
        ]);
        $this->configure('google', $server);

        $this->get('/auth/google/callback?code=c&state=' . $this->start('google'))->assertRedirect('/');

        $this->assertSame('modou@example.test', Auth::user()['email']);
        $this->assertSame('GET /v1/userinfo HTTP/1.1', $server->all()[1]['request']);
    }

    public function test_two_factor_is_still_required_after_oauth(): void
    {
        $id = User::create(['name' => 'Awa', 'email' => 'awa@example.test', 'password' => Hash::make('motdepasse123')]);
        $setup = TwoFactor::enable(User::find($id));
        TwoFactor::confirm(User::find($id), Totp::code($setup['secret'], time() - 30));
        $server = $this->githubServer();
        $this->configure('github', $server);

        $this->get('/auth/github/callback?code=c&state=' . $this->start('github'))->assertRedirect('/two-factor-challenge');

        $this->assertFalse(Auth::check());
        $this->assertTrue(Auth::twoFactorPending());
    }

    public function test_an_unconfigured_provider_is_a_404(): void
    {
        $this->get('/auth/github/redirect')->assertStatus(404);
        $this->get('/auth/github/callback?code=c&state=s')->assertStatus(404);
        $this->get('/auth/facebook/redirect')->assertStatus(404);
    }

    public function test_the_login_page_offers_configured_providers_only(): void
    {
        $this->configure('github');

        $page = $this->get('/login');
        $page->assertSee('/auth/github/redirect');
        $page->assertDontSee('/auth/google/redirect');
    }
}
