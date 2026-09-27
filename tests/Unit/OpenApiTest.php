<?php

namespace Tests\Unit;

use App\Middleware\Authenticate;
use App\Middleware\AuthenticateWithToken;
use App\Middleware\Authorize;
use App\Middleware\ThrottleRequests;
use Niang\Core\Database\Model;
use Niang\Core\Http\Response;
use Niang\Core\OpenApi;
use Niang\Core\Router;
use Niang\Core\Validation\FormRequest;
use PHPUnit\Framework\TestCase;

class OpenApiTest extends TestCase
{
    private function router(): Router
    {
        $router = new Router();
        $router->get('/api/orders', [OpenApiTestController::class, 'index'], [AuthenticateWithToken::class])->name('orders.index');
        $router->post('/api/orders', [OpenApiTestController::class, 'store'], [AuthenticateWithToken::class, ThrottleRequests::class]);
        $router->get('/api/orders/{id}', [OpenApiTestController::class, 'show'])->where(['id' => '[0-9]+']);
        $router->delete('/api/orders/{order}', [OpenApiTestController::class, 'destroy'], [Authorize::class . ':orders.delete'])
            ->bind(['order' => OpenApiTestOrder::class . ':reference']);
        $router->post('/api/avatar', [OpenApiTestController::class, 'avatar'], [Authenticate::class]);
        $router->options('/api/orders', fn () => Response::html('', 204));
        $router->get('/accueil', fn () => 'hors API');

        return $router;
    }

    public function test_paths_methods_and_prefix_filter(): void
    {
        $doc = OpenApi::generate($this->router(), ['title' => 'Boutique', 'version' => '2.1.0', 'server' => 'https://boutique.test/']);

        $this->assertSame('3.0.3', $doc['openapi']);
        $this->assertSame(['title' => 'Boutique', 'version' => '2.1.0'], $doc['info']);
        $this->assertSame([['url' => 'https://boutique.test']], $doc['servers']);
        $this->assertSame(['/api/avatar', '/api/orders', '/api/orders/{id}', '/api/orders/{order}'], array_keys($doc['paths']));
        $this->assertSame(['get', 'post'], array_keys($doc['paths']['/api/orders']), 'OPTIONS ignoré');
        $this->assertSame('orders.index', $doc['paths']['/api/orders']['get']['operationId']);
        $this->assertSame(['orders'], $doc['paths']['/api/orders']['get']['tags']);
    }

    public function test_docblock_becomes_summary_and_description(): void
    {
        $get = OpenApi::generate($this->router())['paths']['/api/orders']['get'];

        $this->assertSame('Liste des commandes du client.', $get['summary']);
        $this->assertSame("Les plus récentes d'abord.", $get['description']);
    }

    public function test_path_parameters_types_and_model_bindings(): void
    {
        $paths = OpenApi::generate($this->router())['paths'];

        $this->assertSame(['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']], $paths['/api/orders/{id}']['get']['parameters'][0]);
        $order = $paths['/api/orders/{order}']['delete'];
        $this->assertSame('string', $order['parameters'][0]['schema']['type']);
        $this->assertSame('OpenApiTestOrder (colonne reference)', $order['parameters'][0]['description']);
        $this->assertArrayHasKey('404', $order['responses']);
        $this->assertArrayHasKey('403', $order['responses']);
    }

    public function test_request_body_from_form_request_rules(): void
    {
        $post = OpenApi::generate($this->router())['paths']['/api/orders']['post'];
        $schema = $post['requestBody']['content']['application/json']['schema'];

        $this->assertTrue($post['requestBody']['required']);
        $this->assertSame(['email', 'items'], $schema['required']);
        $this->assertSame(['type' => 'string', 'format' => 'email'], $schema['properties']['email']);
        $this->assertSame(['type' => 'string', 'enum' => ['wave', 'orange_money', 'carte']], $schema['properties']['payment']);
        $this->assertSame(['type' => 'string', 'nullable' => true, 'maxLength' => 500], $schema['properties']['note']);
        $this->assertSame(['type' => 'integer', 'minimum' => 1, 'maximum' => 10], $schema['properties']['coupons']);
        $this->assertSame('array', $schema['properties']['items']['type']);
        $this->assertSame(1, $schema['properties']['items']['minItems']);
        $this->assertSame(['product_id', 'quantity'], $schema['properties']['items']['items']['required']);
        $this->assertSame('integer', $schema['properties']['items']['items']['properties']['quantity']['type']);
        $this->assertSame(['200', '401', '422', '429'], array_map('strval', array_keys($post['responses'])));
    }

    public function test_file_uploads_use_multipart_and_security_schemes_are_declared(): void
    {
        $doc = OpenApi::generate($this->router());
        $avatar = $doc['paths']['/api/avatar']['post'];

        $this->assertSame(['multipart/form-data'], array_keys($avatar['requestBody']['content']));
        $this->assertSame('binary', $avatar['requestBody']['content']['multipart/form-data']['schema']['properties']['photo']['format']);
        $this->assertSame([['sessionAuth' => []]], $avatar['security']);
        $this->assertSame([['bearerAuth' => []]], $doc['paths']['/api/orders']['post']['security']);
        $this->assertSame(['bearerAuth', 'sessionAuth'], array_keys($doc['components']['securitySchemes']));
    }

    public function test_an_empty_prefix_describes_every_route(): void
    {
        $this->assertArrayHasKey('/accueil', OpenApi::generate($this->router(), ['prefix' => ''])['paths']);
    }

    public function test_the_generated_document_is_written_by_the_command(): void
    {
        $output = sys_get_temp_dir() . '/np-openapi-' . uniqid() . '.json';
        $commander = new \Niang\Core\Console\Commander(base_path());
        $method = new \ReflectionMethod($commander, 'openApi');

        ob_start();
        $method->invoke($commander, ["--output=$output"]);
        $echo = (string) ob_get_clean();

        $this->assertStringContainsString('chemin(s)', $echo);
        $this->assertSame('3.0.3', json_decode((string) file_get_contents($output), true)['openapi']);
        unlink($output);
    }
}

class OpenApiTestOrder extends Model
{
}

class OpenApiTestStoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'payment' => 'in:wave,orange_money,carte',
            'note' => 'nullable|string|max:500',
            'coupons' => 'integer|min:1|max:10',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}

class OpenApiTestAvatarRequest extends FormRequest
{
    public function rules(): array
    {
        return ['photo' => 'required|image|max:2048'];
    }
}

class OpenApiTestController
{
    /**
     * Liste des commandes du client.
     *
     * Les plus récentes d'abord.
     *
     * @return Response
     */
    public function index(): Response
    {
        return Response::json([]);
    }

    public function store(OpenApiTestStoreRequest $request): Response
    {
        return Response::json([], 201);
    }

    public function show(int $id): Response
    {
        return Response::json([]);
    }

    public function destroy(array $order): Response
    {
        return Response::json([]);
    }

    public function avatar(OpenApiTestAvatarRequest $request): Response
    {
        return Response::json([]);
    }
}
