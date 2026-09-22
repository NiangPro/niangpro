<?php

namespace Tests\Feature;

use App\Models\User;
use Niang\Core\Csrf;
use Niang\Core\Hash;
use Niang\Core\Testing\RefreshDatabase;
use Niang\Core\Testing\TestCase;

class ApiTokenAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_token_can_be_issued_and_used_to_reach_a_protected_route(): void
    {
        User::create(['name' => 'Awa', 'email' => 'awa@example.test', 'password' => Hash::make('motdepasse123')]);

        $issued = $this->post('/api/tokens', [
            'email' => 'awa@example.test',
            'password' => 'motdepasse123',
            'device_name' => 'mobile',
        ]);

        $issued->assertStatus(201);
        $token = $issued->json()['token'];
        $this->assertIsString($token);

        $me = $this->get('/api/me', ['Authorization' => "Bearer $token"]);

        $me->assertOk();
        $this->assertSame('awa@example.test', $me->json()['user']['email']);
    }

    public function test_token_issuance_rejects_wrong_credentials(): void
    {
        User::create(['name' => 'Awa', 'email' => 'awa@example.test', 'password' => Hash::make('motdepasse123')]);

        $this->post('/api/tokens', [
            'email' => 'awa@example.test',
            'password' => 'mauvais-mot-de-passe',
            'device_name' => 'mobile',
        ])->assertStatus(401);
    }

    public function test_a_protected_route_rejects_a_missing_token(): void
    {
        $this->get('/api/me')->assertStatus(401);
    }

    public function test_a_protected_route_rejects_an_invalid_token(): void
    {
        $this->get('/api/me', ['Authorization' => 'Bearer ne-correspond-a-rien'])->assertStatus(401);
    }

    public function test_token_authentication_never_touches_the_browser_session(): void
    {
        User::create(['name' => 'Awa', 'email' => 'awa@example.test', 'password' => Hash::make('motdepasse123')]);
        User::create(['name' => 'Fatou', 'email' => 'fatou@example.test', 'password' => Hash::make('motdepasse123')]);

        // Session navigateur : Awa.
        $this->post('/login', [
            '_token' => Csrf::token(),
            'email' => 'awa@example.test',
            'password' => 'motdepasse123',
        ])->assertRedirect('/');

        // Appel API par jeton : Fatou.
        $issued = $this->post('/api/tokens', [
            'email' => 'fatou@example.test',
            'password' => 'motdepasse123',
            'device_name' => 'mobile',
        ]);
        $token = $issued->json()['token'];

        $me = $this->get('/api/me', ['Authorization' => "Bearer $token"]);
        $this->assertSame('fatou@example.test', $me->json()['user']['email']);

        // La session reste celle d'Awa : /login redirige toujours (RedirectIfAuthenticated).
        $this->get('/login')->assertRedirect('/');
    }
}
