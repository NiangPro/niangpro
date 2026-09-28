<?php

namespace Tests\Security;

use App\Models\User;
use Niang\Core\Csrf;
use Niang\Core\Hash;
use Niang\Core\Mail;
use Niang\Core\Testing\RefreshDatabase;
use Niang\Core\Testing\TestCase;

/** Roadmap §55 : CSRF, redirection ouverte, attaques par l'en-tête Host. */
class RequestForgeryTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mail::reset();
        putenv('APP_URL');
        parent::tearDown();
    }

    public function test_state_changing_requests_require_a_valid_csrf_token(): void
    {
        $this->post('/login', ['email' => 'a@b.c', 'password' => 'x'])->assertStatus(419);
        $this->post('/login', ['_token' => 'forgé', 'email' => 'a@b.c', 'password' => 'x'])->assertStatus(419);
        $this->post('/logout')->assertStatus(419);
        $this->post('/contact', ['name' => 'x'])->assertStatus(419);
    }

    public function test_login_never_redirects_to_a_url_chosen_by_the_visitor(): void
    {
        User::create(['name' => 'Awa', 'email' => 'awa@example.test', 'password' => Hash::make('motdepasse123')]);

        foreach (['redirect', 'next', 'return_to', 'intended'] as $field) {
            $response = $this->post("/login?$field=https://evil.test", [
                '_token' => Csrf::token(), 'email' => 'awa@example.test', 'password' => 'motdepasse123', $field => 'https://evil.test',
            ]);
            $this->assertStringNotContainsString('evil.test', (string) $response->header('Location'));
            \Niang\Core\Auth::logout();
        }
    }

    public function test_emailed_links_use_app_url_not_the_host_header(): void
    {
        putenv('APP_URL=https://boutique.example');
        Mail::fake();
        User::create(['name' => 'Awa', 'email' => 'awa@example.test', 'password' => Hash::make('motdepasse123')]);

        $this->post('/forgot-password', ['_token' => Csrf::token(), 'email' => 'awa@example.test'], [
            'Host' => 'evil.test',
            'X-Forwarded-Host' => 'evil.test',
        ]);

        $body = Mail::sent()[0]['mailable']->body();
        $this->assertMatchesRegularExpression('#https://boutique\.example/reset-password/\S+signature=#', $body, 'lien absolu et cliquable');
        $this->assertStringNotContainsString('evil.test', $body);
    }

    public function test_url_helper_builds_absolute_urls_from_app_url(): void
    {
        putenv('APP_URL=https://site.example/');

        $this->assertSame('https://site.example/a/b', url('/a/b'));
        $this->assertSame('https://site.example/a', url('a'));
        $this->assertSame('https://autre.example/x', url('https://autre.example/x'), 'URL déjà absolue inchangée');
    }
}
