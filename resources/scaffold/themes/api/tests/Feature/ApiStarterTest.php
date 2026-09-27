<?php

namespace Tests\Feature;

use App\Models\User;
use Niang\Core\ApiToken;
use Niang\Core\Hash;
use Niang\Core\OpenApi;
use Niang\Core\RateLimiter;
use Niang\Core\Testing\RefreshDatabase;
use Niang\Core\Testing\TestCase;

/** Livré avec le starter « api » : comptes, jetons, notes, erreurs JSON, CORS et description OpenAPI. */
class ApiStarterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::reset();
    }

    private function token(string $email = 'awa@example.com'): string
    {
        $id = User::create(['name' => 'Awa', 'email' => $email, 'password' => Hash::make('secret123')]);

        return ApiToken::issue(User::find($id), 'tests');
    }

    /** @return array<string, string> */
    private function bearer(string $token): array
    {
        return ['Authorization' => "Bearer $token", 'Accept' => 'application/json'];
    }

    public function test_the_root_describes_the_api(): void
    {
        $this->get('/')->assertOk()->assertJson(['name' => config('site.name'), 'version' => config('site.version')]);
    }

    public function test_register_then_use_the_token(): void
    {
        $response = $this->post('/api/v1/register', ['name' => 'Moussa', 'email' => 'moussa@example.com', 'password' => 'secret123', 'device_name' => 'mobile'])
            ->assertStatus(201);
        $token = $response->json()['token'];

        $this->assertArrayNotHasKey('password', $response->json()['user']);
        $this->get('/api/v1/me', $this->bearer($token))->assertOk()->assertJson(['data' => ['email' => 'moussa@example.com']]);
    }

    public function test_login_logout(): void
    {
        $this->token();

        $this->post('/api/v1/tokens', ['email' => 'awa@example.com', 'password' => 'faux', 'device_name' => 'x'])->assertStatus(401);
        $token = $this->post('/api/v1/tokens', ['email' => 'awa@example.com', 'password' => 'secret123', 'device_name' => 'x'])
            ->assertStatus(201)->json()['token'];

        $this->delete('/api/v1/tokens/current', [], $this->bearer($token))->assertStatus(204);
        $this->get('/api/v1/me', $this->bearer($token))->assertStatus(401);
    }

    public function test_notes_crud(): void
    {
        $auth = $this->bearer($this->token());

        $id = $this->post('/api/v1/notes', ['title' => 'Acheter du pain', 'done' => false], $auth)->assertStatus(201)->json()['data']['id'];
        $this->get('/api/v1/notes', $auth)->assertOk()->assertJson(['meta' => ['total' => 1]]);
        $this->put("/api/v1/notes/$id", ['title' => 'Acheter du pain', 'done' => true], $auth)->assertOk()->assertJson(['data' => ['done' => true]]);
        $this->get("/api/v1/notes/$id", $auth)->assertOk()->assertJson(['data' => ['title' => 'Acheter du pain']]);
        $this->delete("/api/v1/notes/$id", [], $auth)->assertStatus(204);
        $this->get("/api/v1/notes/$id", $auth)->assertStatus(404);
    }

    public function test_another_accounts_note_does_not_exist(): void
    {
        $id = $this->post('/api/v1/notes', ['title' => 'Privée'], $this->bearer($this->token()))->json()['data']['id'];
        $other = $this->bearer($this->token('intrus@example.com'));

        $this->get("/api/v1/notes/$id", $other)->assertStatus(404);
        $this->put("/api/v1/notes/$id", ['title' => 'Piratée'], $other)->assertStatus(404);
        $this->delete("/api/v1/notes/$id", [], $other)->assertStatus(404);
        $this->get('/api/v1/notes', $other)->assertJson(['meta' => ['total' => 0]]);
    }

    public function test_errors_are_json_even_without_accept(): void
    {
        $this->assertArrayHasKey('message', $this->get('/api/v1/me')->assertStatus(401)->json());

        $validation = $this->post('/api/v1/register', ['email' => 'pas-un-email']);
        $validation->assertStatus(422);
        $this->assertArrayHasKey('email', $validation->json()['errors']);

        $this->assertIsArray($this->get('/api/v1/inconnu')->assertStatus(404)->json());
        $this->assertIsArray($this->get('/inconnu')->assertStatus(404)->json());
    }

    public function test_cors_preflight(): void
    {
        $response = $this->call('OPTIONS', '/api/v1/notes', [], ['Origin' => 'http://localhost:3000', 'Access-Control-Request-Method' => 'POST']);

        $response->assertStatus(204);
    }

    public function test_the_openapi_description_covers_every_endpoint(): void
    {
        $spec = OpenApi::generate($this->app->router, ['prefix' => '/api']);

        foreach (['/api/v1/register', '/api/v1/tokens', '/api/v1/me', '/api/v1/notes', '/api/v1/notes/{id}'] as $path) {
            $this->assertArrayHasKey($path, $spec['paths']);
        }

        $this->assertArrayHasKey('requestBody', $spec['paths']['/api/v1/notes']['post']);
        $this->assertArrayHasKey('security', $spec['paths']['/api/v1/me']['get']);
    }
}
