<?php

namespace Tests\Feature;

use App\Mailables\VerifyEmailMailable;
use App\Models\User;
use Niang\Core\Csrf;
use Niang\Core\Mail;
use Niang\Core\Testing\RefreshDatabase;
use Niang\Core\Testing\TestCase;
use Niang\Core\UrlSignature;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mail::reset();
        parent::tearDown();
    }

    public function test_registering_sends_a_signed_verification_link_and_visiting_it_verifies_the_account(): void
    {
        Mail::fake();

        $this->post('/register', [
            '_token' => Csrf::token(),
            'name' => 'Awa Diop',
            'email' => 'awa@example.test',
            'password' => 'motdepasse123',
            'password_confirmation' => 'motdepasse123',
        ])->assertRedirect('/');

        $sent = Mail::sent();
        $this->assertCount(1, $sent);
        $this->assertInstanceOf(VerifyEmailMailable::class, $sent[0]['mailable']);

        $user = User::where('email', 'awa@example.test')[0];
        $this->assertNull($user['email_verified_at']);

        preg_match('#/verify-email/\S+#', $sent[0]['mailable']->body(), $matches);

        $this->get($matches[0])->assertRedirect('/login');

        $verified = User::find($user['id']);
        $this->assertNotNull($verified['email_verified_at']);
    }

    public function test_visiting_an_expired_verification_link_is_rejected(): void
    {
        Mail::fake();

        $this->post('/register', [
            '_token' => Csrf::token(),
            'name' => 'Awa Diop',
            'email' => 'awa@example.test',
            'password' => 'motdepasse123',
            'password_confirmation' => 'motdepasse123',
        ]);

        $user = User::where('email', 'awa@example.test')[0];
        $expired = UrlSignature::sign("/verify-email/{$user['id']}", -1);

        $this->get($expired)->assertStatus(403);
    }
}
