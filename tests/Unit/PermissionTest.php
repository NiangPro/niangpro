<?php

namespace Tests\Unit;

use App\Middleware\Authorize;
use Niang\Core\Config;
use Niang\Core\Container;
use Niang\Core\Gate;
use Niang\Core\Http\Request;
use Niang\Core\Http\Response;
use Niang\Core\Middleware;
use Niang\Core\Permission;
use Niang\Core\Router;
use PHPUnit\Framework\TestCase;

class PermissionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::load(base_path());
        Config::set('permissions.roles', [
            'admin' => ['*'],
            'editor' => ['posts.*', 'comments.moderate'],
            'user' => [],
        ]);
    }

    protected function tearDown(): void
    {
        Gate::reset();
        Config::load(base_path());
        parent::tearDown();
    }

    public function test_roles_and_wildcards(): void
    {
        $admin = ['id' => 1, 'role' => 'admin'];
        $editor = ['id' => 2, 'role' => 'editor'];
        $user = ['id' => 3, 'role' => 'user'];

        $this->assertTrue(Permission::can($admin, 'nimporte.quoi'));
        $this->assertTrue(Permission::can($editor, 'posts.delete'));
        $this->assertTrue(Permission::can($editor, 'comments.moderate'));
        $this->assertFalse(Permission::can($editor, 'comments.delete'));
        $this->assertFalse(Permission::can($editor, 'postsx.delete'), '« posts.* » ne couvre pas « postsx. »');
        $this->assertFalse(Permission::can($user, 'posts.create'));
        $this->assertFalse(Permission::can(null, 'posts.create'), 'invité');
        $this->assertFalse(Permission::can(['id' => 4, 'role' => 'inconnu'], 'posts.create'));
        $this->assertFalse(Permission::can(['id' => 5], 'posts.create'), 'sans colonne role');

        $this->assertTrue(Permission::hasRole($editor, ['admin', 'editor']));
        $this->assertFalse(Permission::hasRole($user, 'admin'));
        $this->assertSame(['posts.*', 'comments.moderate'], Permission::permissions($editor));
    }

    public function test_the_role_column_is_configurable(): void
    {
        Config::set('permissions.column', 'profil');

        $this->assertTrue(Permission::hasRole(['profil' => 'admin'], 'admin'));
        $this->assertFalse(Permission::hasRole(['role' => 'admin'], 'admin'));
    }

    public function test_route_middleware_receives_arguments(): void
    {
        $router = new Router();
        $router->get('/x', fn () => Response::html('ok'), [PermissionTestRecorder::class . ':a,b']);
        $router->get('/y', fn () => Response::html('ok'), [PermissionTestRecorder::class]);

        $router->dispatch(Request::create('GET', '/x'), new Container());
        $this->assertSame(['a', 'b'], PermissionTestRecorder::$arguments);

        $router->dispatch(Request::create('GET', '/y'), new Container());
        $this->assertSame([], PermissionTestRecorder::$arguments);
    }

    public function test_authorize_redirects_guests(): void
    {
        // Indépendant de l'ordre des tests : aucune session ni jeton d'un test précédent.
        \Niang\Core\Auth::resolveViaToken(null);
        \Niang\Core\Session::forget('_auth_user_id');

        $response = (new Authorize())->handle(Request::create('GET', '/admin'), fn () => Response::html('ok'), 'posts.delete');

        $this->assertSame(302, $response->getStatus());
    }

    public function test_gate_explicit_rules_win_over_role_permissions(): void
    {
        Gate::define('posts.publish', fn () => false);

        $this->assertFalse(Gate::allows('posts.publish'), 'une règle define() l\'emporte, même pour un admin');
    }
}

class PermissionTestRecorder implements Middleware
{
    /** @var list<string> */
    public static array $arguments = [];

    public function handle(Request $request, \Closure $next, string ...$arguments): Response
    {
        self::$arguments = $arguments;
        return $next($request);
    }
}
