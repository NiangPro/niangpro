<?php

namespace Niang\Core;

use Niang\Core\Http\Request;
use Niang\Core\Http\Response;
use Niang\Core\Validation\ValidationException;

class Application
{
    public Router $router;
    public Container $container;

    public function __construct(private string $basePath)
    {
        Env::load($basePath . '/.env');
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

    public function loadRoutes(string $file): void
    {
        $cached = RouteCache::load();

        if ($cached !== null) {
            $this->router->loadFromCache($cached['routes'], $cached['named']);
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
        try {
            $response = $this->router->dispatch($request, $this->container);
        } catch (ValidationException $e) {
            $response = $this->renderValidationException($e, $request);
        } catch (AuthorizationException $e) {
            $response = $this->renderAuthorizationException($e, $request);
        } catch (\Throwable $e) {
            Log::error($e->getMessage(), ['exception' => $e::class, 'file' => $e->getFile(), 'line' => $e->getLine()]);
            $response = $this->renderException($e);
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
     * pour y penser. Retirez ou ajustez ces en-têtes ici si votre app en a besoin d'autres.
     */
    private function applySecurityHeaders(Response $response): Response
    {
        return $response
            ->header('X-Frame-Options', 'DENY')
            ->header('X-Content-Type-Options', 'nosniff')
            ->header('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->header('Content-Security-Policy', "default-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:")
            ->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    private function configureErrorHandling(): void
    {
        error_reporting(E_ALL);
        ini_set('display_errors', Env::get('APP_DEBUG', 'true') === 'true' ? '1' : '0');
    }

    private function renderValidationException(ValidationException $e, Request $request): Response
    {
        if ($request->wantsJson()) {
            return Response::json(['message' => 'La validation a échoué.', 'errors' => $e->errors], 422);
        }

        $back = $request->header('Referer', '/');

        return Response::redirect($back)
            ->with('errors', $e->errors)
            ->with('old', $request->all());
    }

    private function renderAuthorizationException(AuthorizationException $e, Request $request): Response
    {
        if ($request->wantsJson()) {
            return Response::json(['message' => $e->getMessage()], 403);
        }

        if (file_exists(base_path('resources/views/errors/403.php'))) {
            return View::make('errors.403')->status(403);
        }

        return Response::html('<h1>403</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>', 403);
    }

    private function renderException(\Throwable $e): Response
    {
        $debug = Env::get('APP_DEBUG', 'true') === 'true';

        if (!$debug) {
            $errorView = base_path('resources/views/errors/500.php');

            if (file_exists($errorView)) {
                return View::make('errors.500')->status(500);
            }

            return Response::html('<h1>500</h1><p>Une erreur est survenue.</p>', 500);
        }

        $message = htmlspecialchars($e->getMessage());
        $trace = htmlspecialchars($e->getTraceAsString());

        $html = <<<HTML
        <!doctype html>
        <html lang="fr">
        <head><meta charset="utf-8"><title>Erreur | NiangPro</title></head>
        <body style="font-family: ui-monospace, monospace; background:#111; color:#eee; padding:2rem;">
            <h1 style="color:#ff6b6b;">Erreur non interceptée</h1>
            <p style="font-size:1.1rem;">{$message}</p>
            <p style="color:#888;">dans {$e->getFile()}:{$e->getLine()}</p>
            <pre style="white-space:pre-wrap; background:#1a1a1a; padding:1rem; border-radius:8px;">{$trace}</pre>
        </body>
        </html>
        HTML;

        return Response::html($html, 500);
    }
}
