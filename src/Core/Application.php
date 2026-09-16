<?php

namespace Niang\Core;

use Niang\Core\Exceptions\Handler;
use Niang\Core\Http\Request;
use Niang\Core\Http\Response;

class Application
{
    public Router $router;
    public Container $container;

    public function __construct(private string $basePath)
    {
        Env::load($this->envFile());
        Config::load($basePath);

        $this->container = new Container();
        $this->router = new Router();

        $this->container->singleton(Router::class, $this->router);
        $this->container->singleton(Container::class, $this->container);
        $this->container->singleton(self::class, $this);

        $this->configureErrorHandling();
        $this->bootProviders();
    }

    /**
     * register() de chaque provider avant tout boot() : un provider peut ainsi dépendre d'un
     * binding enregistré par un autre sans se soucier de l'ordre déclaré dans config/app.php.
     */
    private function bootProviders(): void
    {
        $providers = array_map(
            fn (string $class) => new $class($this),
            Config::get('app.providers', [])
        );

        foreach ($providers as $provider) {
            $provider->register();
        }

        foreach ($providers as $provider) {
            $provider->boot();
        }
    }

    public function basePath(string $path = ''): string
    {
        return $this->basePath . ($path ? '/' . ltrim($path, '/') : '');
    }

    /**
     * `.env.testing` prend le pas sur `.env` quand APP_ENV=testing (positionné par phpunit.xml
     * avant même que cette classe s'exécute) — isole complètement les tests de la base locale.
     */
    private function envFile(): string
    {
        $testingFile = $this->basePath . '/.env.testing';

        if (getenv('APP_ENV') === 'testing' && file_exists($testingFile)) {
            return $testingFile;
        }

        return $this->basePath . '/.env';
    }

    public function loadRoutes(string $file): void
    {
        $cached = RouteCache::load();

        if ($cached !== null) {
            $this->router->loadFromCache($cached['routes'], $cached['named'], $cached['fallback'] ?? null);
            return;
        }

        $router = $this->router;
        require $file;
    }

    public function run(): void
    {
        Session::start();
        $this->handle(Request::capture())->send();
    }

    /**
     * Dispatche une requête et convertit toute exception en réponse — utilisé par run() en
     * production et par Niang\Core\Testing\TestCase pour simuler des requêtes sans serveur HTTP.
     */
    public function handle(Request $request): Response
    {
        $startedAt = hrtime(true);

        try {
            $response = $this->router->dispatch($request, $this->container);
        } catch (\Throwable $e) {
            $response = Handler::render($e, $request, $startedAt);
        }

        $response = $this->applySecurityHeaders($response);

        return $this->compressIfSupported($response, $request);
    }

    /** Compresse en gzip si le client l'accepte et que ça vaut le coût (au-delà d'un petit seuil). */
    private function compressIfSupported(Response $response, Request $request): Response
    {
        $acceptEncoding = $request->header('Accept-Encoding', $request->server['HTTP_ACCEPT_ENCODING'] ?? '');

        if (!str_contains((string) $acceptEncoding, 'gzip') || !function_exists('gzencode')) {
            return $response;
        }

        $content = $response->getContent();

        if (strlen($content) < 860) {
            return $response;
        }

        $compressed = gzencode($content, 6);

        if ($compressed === false) {
            return $response;
        }

        return $response->content($compressed)->header('Content-Encoding', 'gzip');
    }

    /**
     * Appliqués à toutes les réponses : sûr par défaut plutôt que de compter sur chaque route
     * pour y penser. Ajustez la liste dans config/security.php plutôt qu'ici.
     */
    private function applySecurityHeaders(Response $response): Response
    {
        foreach (Config::get('security.headers', []) as $key => $value) {
            $response->header($key, $value);
        }

        return $response;
    }

    private function configureErrorHandling(): void
    {
        error_reporting(E_ALL);
        ini_set('display_errors', Env::get('APP_DEBUG', 'true') === 'true' ? '1' : '0');
    }
}
