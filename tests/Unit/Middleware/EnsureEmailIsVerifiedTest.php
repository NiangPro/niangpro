<?php

namespace Tests\Unit\Middleware;

use App\Middleware\EnsureEmailIsVerified;
use App\Models\User;
use Niang\Core\Auth;
use Niang\Core\Exceptions\AuthenticationException;
use Niang\Core\Exceptions\HttpException;
use Niang\Core\Hash;
use Niang\Core\Http\Request;
use Niang\Core\Http\Response;
use Niang\Core\Testing\RefreshDatabase;
use Niang\Core\Testing\TestCase;

class EnsureEmailIsVerifiedTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejects_a_guest(): void
    {
        $this->expectException(AuthenticationException::class);

        (new EnsureEmailIsVerified())->handle(new Request('GET', '/'), fn (Request $r) => Response::html('ok'));
    }

    public function test_rejects_a_user_without_a_verified_email(): void
    {
        $userId = User::create(['name' => 'Awa', 'email' => 'awa@example.test', 'password' => Hash::make('secret1234')]);
        Auth::login(User::find($userId));

        $this->expectException(HttpException::class);

        (new EnsureEmailIsVerified())->handle(new Request('GET', '/'), fn (Request $r) => Response::html('ok'));
    }

    public function test_lets_a_user_with_a_verified_email_through(): void
    {
        $userId = User::create(['name' => 'Awa', 'email' => 'awa@example.test', 'password' => Hash::make('secret1234')]);
        User::update($userId, ['email_verified_at' => date('Y-m-d H:i:s')]);
        Auth::login(User::find($userId));

        $response = (new EnsureEmailIsVerified())->handle(new Request('GET', '/'), fn (Request $r) => Response::html('ok'));

        $this->assertSame('ok', $response->getContent());
    }
}
