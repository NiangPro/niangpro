<?php

declare(strict_types=1);

namespace Niang\Core;

use Niang\Core\Database\DB;
use Niang\Core\Exceptions\Handler;
use Niang\Core\Http\Request;
use Niang\Core\Http\Response;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

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

        $logger = new Logger();

        $this->container->singleton(Router::class, $this->router);
        $this->container->singleton(Container::class, $this->container);
        $this->container->singleton(ContainerInterface::class, $this->container);
        $this->container->singleton(self::class, $this);
        $this->container->singleton(Logger::class, $logger);
        $this->container->singleton(LoggerInterface::class, $logger);
        $this->container->singleton(EventDispatcherInterface::class, new Events\Dispatcher());

        $this->configureErrorHandling();
        $this->bootProviders();

        self::fire(new Events\ApplicationBooted($this));
    }

    /** Événement du framework, émis seulement s'il est écouté (aucun coût sinon). */
    private static function fire(object $event): void
    {
        if (Event::hasListeners($event::class)) {
            Event::dispatch($event);
        }
    }

    /**
     * register() de chaque provider avant tout boot() : un provider peut ainsi dépendre d'un
     * binding enregistré par un autre sans se soucier de l'ordre déclaré dans config/app.php.
     */
    private function bootProviders(): void
    {
        $providers = [];

        foreach ((array) Config::get('app.providers', []) as $class) {
            $provider = is_string($class) && class_exists($class) ? new $class($this) : null;

            if (!$provider instanceof ServiceProvider) {
                throw new Exceptions\ConfigurationException(
                    (is_string($class) ? $class : get_debug_type($class)) . ' (config/app.php, providers) doit être une classe qui étend Niang\\Core\\ServiceProvider.'
                );
            }

            $providers[] = $provider;
        }

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
        $problems = self::productionProblems();

        // Roadmap §8 : en production, pas de requête servie avec une configuration critique absente.
        // Le détail va dans les logs, jamais au visiteur.
        if ($problems !== []) {
            Log::critical('Configuration de production invalide : ' . implode(' ; ', $problems));
            Response::html('<h1>503</h1><p>Service temporairement indisponible.</p>', 503)->send();
            return;
        }

        set_error_handler([self::class, 'logDeprecation'], E_DEPRECATED | E_USER_DEPRECATED);

        Session::start();
        $request = Request::capture();
        $response = $this->handle($request);
        $response->send();

        if (Event::hasListeners(Events\RequestTerminated::class)) {
            // Le visiteur a sa réponse : le travail des écouteurs ne le fait plus attendre.
            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            }

            Event::dispatch(new Events\RequestTerminated($request, $response));
        }
    }

    /** @var array<string, true> messages déjà consignés pendant cette requête */
    private static array $loggedDeprecations = [];

    /**
     * @internal gestionnaire d'erreurs posé par run() : chaque dépréciation (y compris silencée par @,
     * comme celles de trigger_deprecation()) est consignée une fois par requête. Retourne true :
     * rien n'est affiché au visiteur.
     */
    public static function logDeprecation(int $type, string $message, string $file = '', int $line = 0): bool
    {
        if (!isset(self::$loggedDeprecations[$message])) {
            self::$loggedDeprecations[$message] = true;
            Log::warning('Dépréciation : {message}', ['message' => $message, 'file' => $file, 'line' => $line]);
        }

        return true;
    }

    /**
     * Mode debug (traces d'erreur détaillées, barre de debug, display_errors). Toujours désactivé
     * en production, même avec APP_DEBUG=true : une variable oubliée ne doit pas exposer le code et
     * les requêtes SQL à tout le monde. Ailleurs, activé sauf APP_DEBUG=false.
     */
    public static function debug(): bool
    {
        if (Env::get('APP_ENV') === 'production') {
            return false;
        }

        return Env::get('APP_DEBUG', 'true') === 'true';
    }

    /** @return list<string> ce qui empêche de servir des requêtes en production (vide hors production) */
    public static function productionProblems(): array
    {
        if (Env::get('APP_ENV') !== 'production') {
            return [];
        }

        $problems = [];

        if (!AppKey::isValid((string) Env::get('APP_KEY', ''))) {
            $problems[] = 'APP_KEY absente ou trop courte (./bin/niang key:generate)';
        }

        return $problems;
    }

    /**
     * Dispatche une requête et convertit toute exception en réponse — utilisé par run() en
     * production et par Niang\Core\Testing\TestCase pour simuler des requêtes sans serveur HTTP.
     */
    public function handle(Request $request): Response
    {
        $startedAt = hrtime(true);
        DB::resetQueryCount();
        Trace::begin($request);

        try {
            // Dans le try : un écouteur qui échoue donne une page d'erreur, pas une requête plantée.
            self::fire(new Events\RequestReceived($request));
            $response = MaintenanceMode::intercept($request) ?? $this->router->dispatch($request, $this->container);
        } catch (\Throwable $e) {
            $response = Handler::render($e, $request, $startedAt);
        }

        $response = $this->applySecurityHeaders($response)->withQueuedCookies(Cookie::pullQueued());
        $response->header('X-Request-Id', Trace::requestId());
        $response = DebugToolbar::inject($response, $startedAt);

        self::fire(new Events\ResponsePrepared($request, $response));

        return $this->compressIfSupported($response, $request);
    }

    /** Compresse en gzip si le client l'accepte et que ça vaut le coût (au-delà d'un petit seuil). */
    private function compressIfSupported(Response $response, Request $request): Response
    {
        $acceptEncoding = $request->header('Accept-Encoding', $request->server['HTTP_ACCEPT_ENCODING'] ?? '');

        if ($response->isStreamed() || !str_contains((string) $acceptEncoding, 'gzip') || !function_exists('gzencode')) {
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
        ini_set('display_errors', self::debug() ? '1' : '0');
    }
}
