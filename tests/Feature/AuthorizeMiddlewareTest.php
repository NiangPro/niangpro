<?php

namespace Tests\Feature;

use App\Middleware\Authorize;
use Niang\Core\Auth;
use Niang\Core\Config;
use Niang\Core\Container;
use Niang\Core\Exceptions\HttpException;
use Niang\Core\Http\Request;
use Niang\Core\Http\Response;
use Niang\Core\Router;
use Niang\Core\Testing\TestCase;

/** Authorize avec un utilisateur connecté : permissions de rôle via Gate. */
class AuthorizeMiddlewareTest extends TestCase
{
    protected function tearDown(): void
    {
        Config::load(base_path());
        parent::tearDown();
    }

    private function statusFor(array $user, string $middleware): int
    {
        Auth::resolveViaToken($user);

        try {
            $router = new Router();
            $router->get('/admin', fn () => Response::html('ok'), [$middleware]);

            return $router->dispatch(Request::create('GET', '/admin'), new Container())->getStatus();
        } catch (HttpException $e) {
            return $e->getStatusCode();
        } finally {
            Auth::resolveViaToken(null);
        }
    }

    public function test_permissions_of_the_role_are_enforced(): void
    {
        $editor = ['id' => 1, 'role' => 'editor'];
        $user = ['id' => 2, 'role' => 'user'];

        $this->assertSame(200, $this->statusFor($editor, Authorize::class . ':posts.update'));
        $this->assertSame(403, $this->statusFor($user, Authorize::class . ':posts.update'));
        $this->assertSame(403, $this->statusFor($editor, Authorize::class . ':posts.update,users.delete'), 'toutes les abilities sont exigées');
        $this->assertSame(200, $this->statusFor(['id' => 3, 'role' => 'admin'], Authorize::class . ':users.delete'));
    }

    public function test_helpers_on_auth(): void
    {
        Auth::resolveViaToken(['id' => 1, 'role' => 'editor']);

        try {
            $this->assertTrue(Auth::hasRole('editor'));
            $this->assertTrue(Auth::can('posts.create'));
            $this->assertFalse(Auth::can('users.delete'));
        } finally {
            Auth::resolveViaToken(null);
        }
    }
}
