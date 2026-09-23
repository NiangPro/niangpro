<?php

namespace Tests\Unit;

use Niang\Core\Config;
use Niang\Core\Cors;
use Niang\Core\Http\Request;
use PHPUnit\Framework\TestCase;

/**
 * Logique pure de Cors::headersFor() (voir tests/Feature/CorsTest.php pour l'exécution via le
 * middleware HandleCors). Config est un singleton statique partagé entre tous les tests du run :
 * on l'écrit directement par Reflection (pas de fichier temporaire à nettoyer) et on la restaure
 * dans tearDown(), sinon les tests suivants liraient la config truquée par celui-ci.
 */
class CorsTest extends TestCase
{
    protected function tearDown(): void
    {
        Config::load(base_path());
        parent::tearDown();
    }

    private function setCorsConfig(array $cors): void
    {
        $property = new \ReflectionProperty(Config::class, 'items');
        $property->setAccessible(true);
        $property->setValue(null, ['cors' => $cors]);
    }

    private function requestFrom(string $origin): Request
    {
        return new Request('GET', '/', headers: ['Origin' => $origin]);
    }

    public function test_wildcard_without_credentials_returns_a_literal_star(): void
    {
        $this->setCorsConfig([
            'allowed_origins' => ['*'],
            'allowed_methods' => ['GET'],
            'allowed_headers' => ['Content-Type'],
            'supports_credentials' => false,
        ]);

        $headers = Cors::headersFor($this->requestFrom('https://a.example.com'));

        $this->assertSame('*', $headers['Access-Control-Allow-Origin']);
        $this->assertArrayNotHasKey('Access-Control-Allow-Credentials', $headers);
    }

    /**
     * Corrigé (pas supprimé) : ce test affirmait auparavant que allowed_origins ['*'] +
     * supports_credentials true devait REFLÉTER n'importe quelle origine — exactement le trou de
     * sécurité que config/cors.php déconseille déjà en commentaire ("supports_credentials=true
     * seulement avec des origines explicites") sans jamais l'empêcher réellement : n'importe quel
     * site tiers pouvait alors faire des requêtes avec les identifiants (cookies, Authorization)
     * de l'utilisateur, la protection CORS étant vidée de son sens pour toute origine tierce.
     */
    public function test_wildcard_with_credentials_matches_no_origin_at_all(): void
    {
        $this->setCorsConfig([
            'allowed_origins' => ['*'],
            'allowed_methods' => ['GET'],
            'allowed_headers' => ['Content-Type'],
            'supports_credentials' => true,
        ]);

        $this->assertSame([], Cors::headersFor($this->requestFrom('https://a.example.com')));
        $this->assertSame([], Cors::headersFor($this->requestFrom('https://n-importe-quel-site.example')));
    }

    /** La combinaison sûre et documentée : une origine explicite avec les identifiants activés. */
    public function test_an_explicit_origin_with_credentials_is_reflected_exactly(): void
    {
        $this->setCorsConfig([
            'allowed_origins' => ['https://a.example.com'],
            'allowed_methods' => ['GET'],
            'allowed_headers' => ['Content-Type'],
            'supports_credentials' => true,
        ]);

        $headers = Cors::headersFor($this->requestFrom('https://a.example.com'));

        $this->assertSame('https://a.example.com', $headers['Access-Control-Allow-Origin']);
        $this->assertSame('true', $headers['Access-Control-Allow-Credentials']);

        $this->assertSame([], Cors::headersFor($this->requestFrom('https://evil.example.com')));
    }

    public function test_origin_outside_the_allow_list_gets_no_headers(): void
    {
        $this->setCorsConfig([
            'allowed_origins' => ['https://a.example.com'],
            'allowed_methods' => ['GET'],
            'allowed_headers' => ['Content-Type'],
            'supports_credentials' => false,
        ]);

        $this->assertSame([], Cors::headersFor($this->requestFrom('https://evil.example.com')));
    }

    public function test_no_origin_header_gets_no_headers(): void
    {
        $this->setCorsConfig(['allowed_origins' => ['*']]);

        $request = new Request('GET', '/');

        $this->assertSame([], Cors::headersFor($request));
    }

    public function test_exposed_headers_and_max_age_are_added_when_configured(): void
    {
        $this->setCorsConfig([
            'allowed_origins' => ['*'],
            'allowed_methods' => ['GET'],
            'allowed_headers' => ['Content-Type'],
            'exposed_headers' => ['X-Total-Count'],
            'max_age' => 3600,
        ]);

        $headers = Cors::headersFor($this->requestFrom('https://a.example.com'));

        $this->assertSame('X-Total-Count', $headers['Access-Control-Expose-Headers']);
        $this->assertSame('3600', $headers['Access-Control-Max-Age']);
    }
}
