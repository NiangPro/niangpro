<?php

namespace Tests\Feature;

use App\Models\User;
use Niang\Core\Auth;
use Niang\Core\Crypt;
use Niang\Core\Csrf;
use Niang\Core\Hash;
use Niang\Core\Session;
use Niang\Core\Testing\RefreshDatabase;
use Niang\Core\Testing\TestCase;
use Niang\Core\Totp;
use Niang\Core\TwoFactor;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    private function user(): array
    {
        $id = User::create(['name' => 'Awa', 'email' => 'awa@example.test', 'password' => Hash::make('motdepasse123')]);

        return User::find($id);
    }

    /** @return array{0: array, 1: array{secret: string, uri: string, recovery_codes: list<string>}} */
    private function userWithTwoFactor(): array
    {
        $user = $this->user();
        $setup = TwoFactor::enable($user);
        // Code de la période précédente : celui de la période courante reste libre pour le test.
        $this->assertTrue(TwoFactor::confirm($user, Totp::code($setup['secret'], time() - 30)));

        return [User::find($user['id']), $setup];
    }

    private function login(array $extra = []): \Niang\Core\Testing\TestResponse
    {
        return $this->post('/login', ['_token' => Csrf::token(), 'email' => 'awa@example.test', 'password' => 'motdepasse123'] + $extra);
    }

    private function challenge(string $code): \Niang\Core\Testing\TestResponse
    {
        return $this->post('/two-factor-challenge', ['_token' => Csrf::token(), 'code' => $code]);
    }

    public function test_without_two_factor_login_is_unchanged(): void
    {
        $this->user();

        $this->login()->assertRedirect('/');
        $this->assertTrue(Auth::check());
    }

    public function test_an_unconfirmed_setup_is_not_required_at_login(): void
    {
        TwoFactor::enable($this->user());

        $this->login()->assertRedirect('/');
        $this->assertTrue(Auth::check());
    }

    public function test_the_password_alone_does_not_log_in_when_enabled(): void
    {
        $this->userWithTwoFactor();

        $this->login()->assertRedirect('/two-factor-challenge');

        $this->assertFalse(Auth::check());
        $this->assertTrue(Auth::twoFactorPending());
        $this->get('/two-factor-challenge')->assertOk()->assertSee('code');
    }

    public function test_a_valid_code_completes_the_login_and_cannot_be_replayed(): void
    {
        [$user, $setup] = $this->userWithTwoFactor();
        $this->login();

        $this->challenge('000000')->assertRedirect('/two-factor-challenge');
        $this->assertFalse(Auth::check());

        $code = Totp::code($setup['secret']);
        $this->challenge($code)->assertRedirect('/');
        $this->assertSame($user['id'], Auth::id());
        $this->assertFalse(Auth::twoFactorPending());

        // Même code, nouvelle connexion : refusé (anti-rejeu).
        Auth::logout();
        $this->login();
        $this->challenge($code)->assertRedirect('/two-factor-challenge');
        $this->assertFalse(Auth::check());
    }

    public function test_a_recovery_code_works_once(): void
    {
        [$user, $setup] = $this->userWithTwoFactor();
        $recovery = strtoupper($setup['recovery_codes'][0]);

        $this->login();
        $this->challenge($recovery)->assertRedirect('/');
        $this->assertTrue(Auth::check());
        $this->assertSame(TwoFactor::RECOVERY_CODES - 1, TwoFactor::remainingRecoveryCodes($user));

        Auth::logout();
        $this->login();
        $this->challenge($recovery)->assertRedirect('/two-factor-challenge');
        $this->assertFalse(Auth::check());
    }

    public function test_the_pending_state_expires(): void
    {
        [, $setup] = $this->userWithTwoFactor();
        $this->login();

        $pending = Session::get('_auth_two_factor');
        Session::put('_auth_two_factor', ['at' => time() - Auth::TWO_FACTOR_TIMEOUT - 1] + $pending);

        $this->assertFalse(Auth::completeTwoFactor(Totp::code($setup['secret'])));
        $this->assertFalse(Auth::check());
        $this->get('/two-factor-challenge')->assertRedirect('/login');
    }

    public function test_remember_me_survives_the_challenge(): void
    {
        [, $setup] = $this->userWithTwoFactor();

        $this->assertNull($this->login(['remember' => '1'])->cookie(Auth::REMEMBER_COOKIE), 'pas de cookie avant le code');
        $this->assertNotNull($this->challenge(Totp::code($setup['secret']))->cookie(Auth::REMEMBER_COOKIE));
    }

    public function test_the_secret_is_encrypted_in_the_database(): void
    {
        [$user, $setup] = $this->userWithTwoFactor();

        $this->assertStringNotContainsString($setup['secret'], (string) $user['two_factor_secret']);
        $this->assertSame($setup['secret'], Crypt::decrypt($user['two_factor_secret'], 'two-factor'));
        foreach ($setup['recovery_codes'] as $code) {
            $this->assertStringNotContainsString($code, (string) $user['two_factor_recovery_codes']);
        }
    }

    public function test_api_tokens_require_the_code_too(): void
    {
        [, $setup] = $this->userWithTwoFactor();
        $credentials = ['email' => 'awa@example.test', 'password' => 'motdepasse123', 'device_name' => 'mobile'];

        $refused = $this->post('/api/tokens', $credentials);
        $refused->assertStatus(401);
        $this->assertTrue($refused->json()['two_factor']);

        $this->post('/api/tokens', $credentials + ['code' => Totp::code($setup['secret'])])->assertStatus(201);
    }

    public function test_settings_page_enable_confirm_and_disable(): void
    {
        $user = $this->user();
        Auth::login($user);

        $this->get('/user/two-factor')->assertOk()->assertSee('Activer');
        $this->post('/user/two-factor', ['_token' => Csrf::token(), 'password' => 'mauvais'])->assertStatus(403);

        $setup = $this->post('/user/two-factor', ['_token' => Csrf::token(), 'password' => 'motdepasse123']);
        $setup->assertOk();
        preg_match('/secret=([A-Z2-7]+)/', $setup->content(), $matches);
        $secret = $matches[1] ?? '';

        $this->post('/user/two-factor/confirm', ['_token' => Csrf::token(), 'code' => '000000'])->assertRedirect('/user/two-factor');
        $this->assertFalse(TwoFactor::enabled(User::find($user['id'])));

        $this->post('/user/two-factor/confirm', ['_token' => Csrf::token(), 'code' => Totp::code($secret)])->assertRedirect('/user/two-factor');
        $this->assertTrue(TwoFactor::enabled(User::find($user['id'])));

        $this->post('/user/two-factor/disable', ['_token' => Csrf::token(), 'password' => 'mauvais'])->assertStatus(403);
        $this->assertTrue(TwoFactor::enabled(User::find($user['id'])));
        $this->post('/user/two-factor/disable', ['_token' => Csrf::token(), 'password' => 'motdepasse123'])->assertRedirect('/user/two-factor');
        $this->assertFalse(TwoFactor::enabled(User::find($user['id'])));
    }

    public function test_settings_require_authentication(): void
    {
        $this->get('/user/two-factor')->assertRedirect('/login');
    }
}
