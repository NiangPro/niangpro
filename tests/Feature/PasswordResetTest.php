<?php

namespace Tests\Feature;

use App\Mailables\ResetPasswordMailable;
use App\Models\User;
use Niang\Core\Csrf;
use Niang\Core\Hash;
use Niang\Core\Mail;
use Niang\Core\Testing\RefreshDatabase;
use Niang\Core\Testing\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mail::reset();
        parent::tearDown();
    }

    public function test_a_user_can_reset_their_password_end_to_end(): void
    {
        Mail::fake();

        User::create([
            'name' => 'Awa',
            'email' => 'awa@example.test',
            'password' => Hash::make('ancien-mdp-1234'),
        ]);

        $this->post('/forgot-password', [
            '_token' => Csrf::token(),
            'email' => 'awa@example.test',
        ])->assertRedirect('/forgot-password');

        $sent = Mail::sent();
        $this->assertCount(1, $sent);
        $this->assertSame('awa@example.test', $sent[0]['to']);
        $this->assertInstanceOf(ResetPasswordMailable::class, $sent[0]['mailable']);

        $signedUrl = $this->extractSignedUrl($sent[0]['mailable']->body());

        $this->post($signedUrl, [
            '_token' => Csrf::token(),
            'password' => 'nouveau-mdp-5678',
            'password_confirmation' => 'nouveau-mdp-5678',
        ])->assertRedirect('/login');

        // L'ancien mot de passe ne fonctionne plus.
        $this->post('/login', [
            '_token' => Csrf::token(),
            'email' => 'awa@example.test',
            'password' => 'ancien-mdp-1234',
        ])->assertRedirect('/login');

        // Le nouveau, si.
        $this->post('/login', [
            '_token' => Csrf::token(),
            'email' => 'awa@example.test',
            'password' => 'nouveau-mdp-5678',
        ])->assertRedirect('/');
    }

    public function test_a_used_reset_link_cannot_be_reused(): void
    {
        Mail::fake();

        User::create([
            'name' => 'Awa',
            'email' => 'awa@example.test',
            'password' => Hash::make('ancien-mdp-1234'),
        ]);

        $this->post('/forgot-password', ['_token' => Csrf::token(), 'email' => 'awa@example.test']);
        $signedUrl = $this->extractSignedUrl(Mail::sent()[0]['mailable']->body());

        $this->post($signedUrl, [
            '_token' => Csrf::token(),
            'password' => 'nouveau-mdp-5678',
            'password_confirmation' => 'nouveau-mdp-5678',
        ])->assertRedirect('/login');

        // Rejoué : le jeton a déjà été consommé, même si la signature de l'URL est toujours valide.
        $this->post($signedUrl, [
            '_token' => Csrf::token(),
            'password' => 'encore-un-autre-1234',
            'password_confirmation' => 'encore-un-autre-1234',
        ])->assertRedirect('/forgot-password');

        // Le mot de passe issu du premier reset est toujours celui en vigueur.
        $this->post('/login', [
            '_token' => Csrf::token(),
            'email' => 'awa@example.test',
            'password' => 'nouveau-mdp-5678',
        ])->assertRedirect('/');
    }

    public function test_the_response_is_identical_whether_or_not_the_account_exists(): void
    {
        Mail::fake();

        User::create([
            'name' => 'Awa',
            'email' => 'awa@example.test',
            'password' => Hash::make('mot-de-passe-1234'),
        ]);

        $existing = $this->post('/forgot-password', ['_token' => Csrf::token(), 'email' => 'awa@example.test']);
        $unknown = $this->post('/forgot-password', ['_token' => Csrf::token(), 'email' => 'personne@example.test']);

        $this->assertSame($existing->status(), $unknown->status());
        $this->assertSame($existing->header('Location'), $unknown->header('Location'));

        // Un seul email envoyé : rien pour l'adresse inconnue.
        $this->assertCount(1, Mail::sent());
    }

    public function test_a_tampered_reset_link_is_rejected(): void
    {
        Mail::fake();

        User::create([
            'name' => 'Awa',
            'email' => 'awa@example.test',
            'password' => Hash::make('ancien-mdp-1234'),
        ]);

        $this->post('/forgot-password', ['_token' => Csrf::token(), 'email' => 'awa@example.test']);
        $signedUrl = $this->extractSignedUrl(Mail::sent()[0]['mailable']->body());
        $tampered = str_replace('expires=', 'expires=9', $signedUrl);

        $this->post($tampered, [
            '_token' => Csrf::token(),
            'password' => 'nouveau-mdp-5678',
            'password_confirmation' => 'nouveau-mdp-5678',
        ])->assertStatus(403);
    }

    private function extractSignedUrl(string $body): string
    {
        preg_match('#/reset-password/\S+#', $body, $matches);

        return $matches[0];
    }
}
