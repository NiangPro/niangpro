<?php

namespace Tests\Unit;

use App\Models\User;
use Niang\Core\ApiToken;
use Niang\Core\Database\QueryBuilder;
use Niang\Core\Hash;
use Niang\Core\Testing\RefreshDatabase;
use Niang\Core\Testing\TestCase;

class ApiTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_issue_then_resolve_returns_the_owning_user(): void
    {
        $user = $this->makeUser();
        $token = ApiToken::issue($user, 'mobile');

        $resolved = ApiToken::resolve($token);

        $this->assertNotNull($resolved);
        $this->assertSame($user['id'], $resolved['id']);
    }

    public function test_resolve_returns_null_for_an_unknown_token(): void
    {
        $this->assertNull(ApiToken::resolve('ne-correspond-a-aucun-jeton'));
    }

    public function test_resolve_returns_null_for_a_revoked_token(): void
    {
        $user = $this->makeUser();
        $token = ApiToken::issue($user, 'mobile');

        // Révoquer un jeton, c'est simplement supprimer sa ligne : pas de mécanisme dédié.
        $this->tokenTable()->where('user_id', $user['id'])->delete();

        $this->assertNull(ApiToken::resolve($token));
    }

    public function test_revoke_removes_only_that_token(): void
    {
        $user = $this->makeUser();
        $phone = ApiToken::issue($user, 'mobile');
        $laptop = ApiToken::issue($user, 'ordinateur');

        $this->assertTrue(ApiToken::revoke($phone));
        $this->assertFalse(ApiToken::revoke($phone));

        $this->assertNull(ApiToken::resolve($phone));
        $this->assertNotNull(ApiToken::resolve($laptop));
    }

    public function test_revoke_all_removes_every_token_of_the_user(): void
    {
        $user = $this->makeUser();
        $tokens = [ApiToken::issue($user, 'a'), ApiToken::issue($user, 'b')];

        $this->assertSame(2, ApiToken::revokeAll($user));

        foreach ($tokens as $token) {
            $this->assertNull(ApiToken::resolve($token));
        }
    }

    public function test_resolve_updates_last_used_at(): void
    {
        $user = $this->makeUser();
        $token = ApiToken::issue($user, 'mobile');

        $before = $this->tokenTable()->where('user_id', $user['id'])->first();
        $this->assertNull($before['last_used_at']);

        ApiToken::resolve($token);

        $after = $this->tokenTable()->where('user_id', $user['id'])->first();
        $this->assertNotNull($after['last_used_at']);
    }

    public function test_only_a_hash_is_stored_never_the_plaintext_token(): void
    {
        $user = $this->makeUser();
        $token = ApiToken::issue($user, 'mobile');

        $record = $this->tokenTable()->where('user_id', $user['id'])->first();

        $this->assertNotSame($token, $record['token_hash']);
        $this->assertSame(64, strlen($record['token_hash'])); // HMAC-SHA256 en hexadécimal
    }

    private function makeUser(): array
    {
        $id = User::create(['name' => 'Awa', 'email' => 'awa@example.test', 'password' => Hash::make('secret1234')]);

        return User::find($id);
    }

    private function tokenTable(): QueryBuilder
    {
        return new QueryBuilder('personal_access_tokens');
    }
}
