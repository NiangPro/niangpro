<?php

namespace Tests\Security;

use App\Models\User;
use Niang\Core\Csrf;
use Niang\Core\Exceptions\MassAssignmentException;
use Niang\Core\Notification;
use Niang\Core\Testing\RefreshDatabase;
use Niang\Core\Testing\TestCase;

/** Roadmap §55 : affectation de masse, IDOR, limitation de débit, en-têtes de sécurité. */
class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_cannot_grant_a_role_or_verify_an_email(): void
    {
        $this->post('/register', [
            '_token' => Csrf::token(),
            'name' => 'Pirate',
            'email' => 'pirate@example.test',
            'password' => 'motdepasse123',
            'password_confirmation' => 'motdepasse123',
            'role' => 'admin',
            'email_verified_at' => '2026-01-01 00:00:00',
            'two_factor_confirmed_at' => '2026-01-01 00:00:00',
        ]);

        $user = User::where('email', 'pirate@example.test')[0];
        $this->assertNull($user['email_verified_at']);
        $this->assertNull($user['two_factor_confirmed_at']);
        $this->assertArrayNotHasKey('role', array_filter($user, fn ($v) => $v === 'admin'));
    }

    public function test_columns_outside_fillable_are_never_written(): void
    {
        $id = User::create(['name' => 'x', 'email' => 'x@example.test', 'password' => 'x', 'email_verified_at' => '2026-01-01 00:00:00']);

        $this->assertNull(User::find($id)['email_verified_at']);
    }

    public function test_a_model_without_fillable_refuses_create(): void
    {
        $this->expectException(MassAssignmentException::class);
        AccessControlTestUnguarded::create(['name' => 'x']);
    }

    public function test_a_user_cannot_act_on_another_users_notification(): void
    {
        $awa = ['id' => 1];
        $modou = ['id' => 2];
        \Niang\Core\Database\DB::statement(
            'INSERT INTO notifications (notifiable_type, notifiable_id, type, data, created_at) VALUES (?, ?, ?, ?, ?)',
            [Notification::notifiableType(), '1', 'x', '{}', date('Y-m-d H:i:s')]
        );
        $id = Notification::for($awa)[0]['id'];

        $this->assertFalse(Notification::markAsRead($modou, $id));
        $this->assertSame([], Notification::for($modou));
    }

    public function test_login_is_rate_limited(): void
    {
        $statuses = [];

        for ($i = 0; $i < 12; $i++) {
            $statuses[] = $this->post('/login', ['_token' => Csrf::token(), 'email' => 'x@example.test', 'password' => 'faux'])->status();
        }

        $this->assertSame(429, end($statuses), 'au-delà de 10 tentatives par minute');
        $this->assertNotContains(429, array_slice($statuses, 0, 10));
    }

    public function test_security_headers_are_sent_on_every_response(): void
    {
        $response = $this->get('/');

        $this->assertSame('nosniff', $response->header('X-Content-Type-Options'));
        $this->assertNotNull($response->header('X-Frame-Options') ?? $response->header('Content-Security-Policy'), 'protection contre l\'inclusion en iframe');
        $this->assertNotNull($response->header('Referrer-Policy'));
    }
}

class AccessControlTestUnguarded extends \Niang\Core\Database\Model
{
    protected static string $table = 'users';
}
