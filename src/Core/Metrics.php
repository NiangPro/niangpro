<?php

declare(strict_types=1);

namespace Niang\Core;

use Niang\Core\Http\Request;
use Niang\Core\Http\Response;

/**
 * Métriques au format Prometheus (roadmap §53), servies sur GET /metrics avec METRICS_TOKEN.
 * Les compteurs vivent dans le cache (CACHE_DRIVER) : incréments atomiques, partagés entre les
 * process PHP-FPM, et entre serveurs avec les pilotes database ou redis. Le nombre de séries est
 * borné d'avance (méthode et classe de statut, jamais l'URL) : chaque série est relue par son nom,
 * sans index à maintenir.
 *
 * @experimental les noms et les étiquettes peuvent encore évoluer (voir docs/API_STABILITY.md).
 */
final class Metrics
{
    /** Bornes des tranches de durée, en secondes (valeurs par défaut des clients Prometheus). */
    public const BUCKETS = [0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1, 2.5, 5, 10];

    private const METHODS = ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'OTHER'];
    private const STATUSES = ['1xx', '2xx', '3xx', '4xx', '5xx'];
    private const PREFIX = 'np_metrics:';
    private const NAME = '/^[a-zA-Z_:][a-zA-Z0-9_:]*$/';

    /** @var array<string, array{help: string, callback: \Closure(): (int|float)}> */
    private static array $gauges = [];

    public static function enabled(): bool
    {
        return (bool) Config::get('metrics.enabled', false);
    }

    /** @internal appelé par Application::handle() après chaque requête */
    public static function recordRequest(string $method, int $status, float $seconds): void
    {
        $method = in_array($method, self::METHODS, true) ? $method : 'OTHER';
        $class = intdiv(max(100, min(599, $status)), 100) . 'xx';

        Cache::increment(self::PREFIX . "requests:$method:$class");
        Cache::increment(self::PREFIX . 'duration:bucket:' . self::bucketFor($seconds));
        Cache::increment(self::PREFIX . 'duration:sum_us', (int) round($seconds * 1_000_000));
    }

    /** @internal appelé par Queue après chaque job traité (ou échoué) par un worker */
    public static function recordJob(bool $succeeded): void
    {
        if (self::enabled()) {
            Cache::increment(self::PREFIX . ($succeeded ? 'jobs:processed' : 'jobs:failed'));
        }
    }

    /**
     * Compteur de l'application, déclaré dans config/metrics.php (clé 'counters') : le nom
     * exposé est préfixé par « app_ ». Sans effet si les métriques sont désactivées.
     */
    public static function increment(string $counter, int $by = 1): void
    {
        if (preg_match(self::NAME, $counter) !== 1 || !array_key_exists($counter, (array) Config::get('metrics.counters', []))) {
            throw new \InvalidArgumentException("Compteur « $counter » non déclaré dans config/metrics.php (clé 'counters').");
        }

        if (self::enabled()) {
            Cache::increment(self::PREFIX . "app:$counter", $by);
        }
    }

    /**
     * Jauge calculée à chaque lecture de /metrics (taille d'une file, utilisateurs connectés...),
     * enregistrée dans le boot() d'un Service Provider. Exposée sous « app_<nom> ».
     *
     * @param \Closure(): (int|float) $callback
     */
    public static function gauge(string $name, string $help, \Closure $callback): void
    {
        if (preg_match(self::NAME, $name) !== 1) {
            throw new \InvalidArgumentException("Nom de jauge invalide : « $name ».");
        }

        self::$gauges[$name] = ['help' => $help, 'callback' => $callback];
    }

    /** Oublie les jauges enregistrées (tests). */
    public static function reset(): void
    {
        self::$gauges = [];
    }

    /** Remet tous les compteurs à zéro (Prometheus gère une remise à zéro comme un redémarrage). */
    public static function flush(): void
    {
        foreach (self::keys() as $key) {
            Cache::forget($key);
        }
    }

    /**
     * @internal servi par Application::handle() : null si ce n'est pas la route des métriques, 404
     * sans jeton configuré (jamais exposé par défaut), 401 si le jeton ne correspond pas.
     */
    public static function intercept(Request $request): ?Response
    {
        if (!self::enabled() || !$request->isMethod('GET') || '/' . trim($request->uri, '/') !== self::path()) {
            return null;
        }

        $token = (string) Config::get('metrics.token', '');

        if ($token === '') {
            return Response::html(__('http.404'), 404);
        }

        $given = (string) $request->header('Authorization', '');

        if (!str_starts_with($given, 'Bearer ') || !hash_equals($token, substr($given, 7))) {
            return Response::html(__('http.401'), 401)->header('WWW-Authenticate', 'Bearer');
        }

        return Response::html(self::render(), 200)
            ->header('Content-Type', 'text/plain; version=0.0.4; charset=utf-8')
            ->header('Cache-Control', 'no-store');
    }

    public static function path(): string
    {
        return '/' . trim((string) Config::get('metrics.path', '/metrics'), '/');
    }

    /** Le texte au format d'exposition Prometheus 0.0.4. */
    public static function render(): string
    {
        $out = [];

        $out[] = '# HELP niangpro_http_requests_total Requêtes HTTP traitées, par méthode et classe de statut.';
        $out[] = '# TYPE niangpro_http_requests_total counter';

        foreach (self::METHODS as $method) {
            foreach (self::STATUSES as $class) {
                $value = self::read("requests:$method:$class");

                if ($value > 0) {
                    $out[] = "niangpro_http_requests_total{method=\"$method\",status=\"$class\"} $value";
                }
            }
        }

        $out[] = '# HELP niangpro_http_request_duration_seconds Durée de traitement des requêtes HTTP.';
        $out[] = '# TYPE niangpro_http_request_duration_seconds histogram';
        $cumulative = 0;

        foreach ([...self::BUCKETS, '+Inf'] as $bound) {
            $cumulative += self::read('duration:bucket:' . $bound);
            $out[] = 'niangpro_http_request_duration_seconds_bucket{le="' . $bound . "\"} $cumulative";
        }

        $out[] = 'niangpro_http_request_duration_seconds_sum ' . self::number(self::read('duration:sum_us') / 1_000_000);
        $out[] = "niangpro_http_request_duration_seconds_count $cumulative";

        foreach (['processed' => 'Jobs traités avec succès par un worker.', 'failed' => 'Exécutions de jobs en échec (chaque tentative).'] as $kind => $help) {
            $out[] = "# HELP niangpro_jobs_{$kind}_total $help";
            $out[] = "# TYPE niangpro_jobs_{$kind}_total counter";
            $out[] = "niangpro_jobs_{$kind}_total " . self::read("jobs:$kind");
        }

        foreach ((array) Config::get('metrics.counters', []) as $name => $help) {
            if (preg_match(self::NAME, (string) $name) !== 1) {
                continue;
            }

            $out[] = '# HELP app_' . $name . ' ' . self::help((string) $help);
            $out[] = "# TYPE app_$name counter";
            $out[] = "app_$name " . self::read("app:$name");
        }

        foreach (self::$gauges as $name => $gauge) {
            try {
                $value = ($gauge['callback'])();
            } catch (\Throwable $e) {
                // Une jauge qui échoue ne doit pas priver Prometheus de toutes les autres.
                Log::warning("Jauge « $name » : " . $e->getMessage(), ['exception' => $e]);
                continue;
            }

            $out[] = '# HELP app_' . $name . ' ' . self::help($gauge['help']);
            $out[] = "# TYPE app_$name gauge";
            $out[] = "app_$name " . self::number($value);
        }

        return implode("\n", $out) . "\n";
    }

    private static function bucketFor(float $seconds): string
    {
        foreach (self::BUCKETS as $bound) {
            if ($seconds <= $bound) {
                return (string) $bound;
            }
        }

        return '+Inf';
    }

    private static function read(string $key): int
    {
        return (int) Cache::get(self::PREFIX . $key, 0);
    }

    /** @return list<string> */
    private static function keys(): array
    {
        $keys = [];

        foreach (self::METHODS as $method) {
            foreach (self::STATUSES as $class) {
                $keys[] = self::PREFIX . "requests:$method:$class";
            }
        }

        foreach ([...self::BUCKETS, '+Inf'] as $bound) {
            $keys[] = self::PREFIX . 'duration:bucket:' . $bound;
        }

        $keys[] = self::PREFIX . 'duration:sum_us';
        $keys[] = self::PREFIX . 'jobs:processed';
        $keys[] = self::PREFIX . 'jobs:failed';

        foreach (array_keys((array) Config::get('metrics.counters', [])) as $name) {
            $keys[] = self::PREFIX . "app:$name";
        }

        return $keys;
    }

    private static function number(int|float $value): string
    {
        return is_int($value) ? (string) $value : rtrim(rtrim(sprintf('%.6F', $value), '0'), '.');
    }

    private static function help(string $text): string
    {
        return str_replace(['\\', "\n"], ['\\\\', '\n'], $text);
    }
}
