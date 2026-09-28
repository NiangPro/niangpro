<?php

declare(strict_types=1);

namespace Niang\Core;

use Niang\Core\Database\QueryBuilder;
use Niang\Core\Exceptions\TenancyException;
use Niang\Core\Http\Request;

/**
 * Multi-locataire (roadmap §50), base partagée : chaque ligne d'un modèle déclaré
 * `protected static bool $tenantScoped = true;` porte la colonne tenant_id, filtrée et renseignée
 * automatiquement pour le locataire courant. Le locataire est identifié par le middleware
 * IdentifyTenant (sous-domaine ou préfixe d'URL via le paramètre de route {tenant}, domaine
 * personnalisé, ou en-tête X-Tenant), ou fixé explicitement avec run() (commandes, seeders).
 *
 * Sûr par défaut : un modèle par locataire interrogé sans locataire courant lève une exception,
 * plutôt que de renvoyer les lignes de tous les locataires. central() l'autorise explicitement.
 *
 * @experimental la forme de la configuration peut encore évoluer (voir docs/API_STABILITY.md).
 */
final class Tenancy
{
    /** @var array<string, mixed>|null */
    private static ?array $current = null;
    private static int $centralDepth = 0;

    public static function enabled(): bool
    {
        return (bool) Config::get('tenancy.enabled', false);
    }

    /** @return array<string, mixed>|null la ligne de la table des locataires */
    public static function current(): ?array
    {
        return self::$current;
    }

    public static function id(): int|string|null
    {
        return self::$current[self::primaryKey()] ?? null;
    }

    /** Colonne qui porte l'identifiant du locataire dans les tables partagées. */
    public static function column(): string
    {
        return (string) Config::get('tenancy.column', 'tenant_id');
    }

    /**
     * Exécute $callback pour $tenant (ligne, ou identifiant cherché dans la table des locataires),
     * puis rétablit le locataire précédent : commandes, seeders, jobs.
     *
     * @template T
     * @param array<string, mixed>|int|string $tenant
     * @param \Closure(): T $callback
     * @return T
     */
    public static function run(array|int|string $tenant, \Closure $callback): mixed
    {
        if (!is_array($tenant)) {
            $tenant = self::find(self::primaryKey(), $tenant) ?? throw new TenancyException("Locataire introuvable : « $tenant ».");
        }

        $previous = [self::$current, self::$centralDepth];
        $log = Log::sharedContext();
        self::$current = $tenant;
        self::$centralDepth = 0;
        Log::withContext(['tenant' => $tenant[self::primaryKey()] ?? null]);

        try {
            return $callback();
        } finally {
            [self::$current, self::$centralDepth] = $previous;
            Log::flushSharedContext();
            Log::withContext($log);
        }
    }

    /**
     * Exécute $callback hors de tout locataire : les modèles par locataire voient alors les lignes
     * de tous les locataires, le cache n'est plus préfixé. Pour l'administration de la plateforme.
     *
     * @template T
     * @param \Closure(): T $callback
     * @return T
     */
    public static function central(\Closure $callback): mixed
    {
        $previous = self::$current;
        self::$current = null;
        self::$centralDepth++;

        try {
            return $callback();
        } finally {
            self::$current = $previous;
            self::$centralDepth--;
        }
    }

    /**
     * @internal pour Model : l'identifiant à filtrer, ou null si aucun filtre ne s'applique.
     *
     * @throws TenancyException modèle par locataire, sans locataire courant ni central()
     */
    public static function scopeFor(string $model): int|string|null
    {
        if (!self::enabled() || self::$centralDepth > 0) {
            return null;
        }

        return self::id() ?? throw new TenancyException(
            "$model est un modèle par locataire, mais aucun locataire n'est identifié : ajoutez le middleware "
            . 'IdentifyTenant à la route, ou utilisez Tenancy::run($tenant, ...) ou Tenancy::central(...).'
        );
    }

    /** @internal pour Cache : préfixe des clés du locataire courant ('' hors locataire). */
    public static function cachePrefix(): string
    {
        $id = self::enabled() ? self::id() : null;

        return $id === null ? '' : "tenant:$id:";
    }

    /**
     * Le locataire désigné par la requête, selon tenancy.identify_by :
     *  - 'route' (défaut) : paramètre de route {tenant} — sous-domaine avec
     *    $router->domain('{tenant}.exemple.sn', ...) ou préfixe avec group(['prefix' => '/{tenant}']) ;
     *  - 'domain' : l'hôte complet, colonne domain (domaines personnalisés des clients) ;
     *  - 'header' : en-tête X-Tenant (API) — ne prouve rien à lui seul : vérifiez que l'utilisateur
     *    authentifié appartient bien à ce locataire.
     *
     * @return array<string, mixed>|null
     */
    public static function resolve(Request $request): ?array
    {
        $value = match ((string) Config::get('tenancy.identify_by', 'route')) {
            'domain' => strtolower(explode(':', (string) ($request->server['HTTP_HOST'] ?? ''))[0]),
            'header' => (string) $request->header('X-Tenant', ''),
            default => (string) ($request->params[(string) Config::get('tenancy.route_parameter', 'tenant')] ?? ''),
        };

        if ($value === '') {
            return null;
        }

        $column = Config::get('tenancy.identify_by', 'route') === 'domain' ? 'domain' : (string) Config::get('tenancy.slug_column', 'slug');

        return self::find($column, $value);
    }

    /** @return array<string, mixed>|null */
    public static function find(string $column, int|string $value): ?array
    {
        return (new QueryBuilder((string) Config::get('tenancy.table', 'tenants')))->where($column, $value)->first();
    }

    /** Oublie le locataire courant (tests). */
    public static function reset(): void
    {
        self::$current = null;
        self::$centralDepth = 0;
    }

    private static function primaryKey(): string
    {
        return (string) Config::get('tenancy.primary_key', 'id');
    }
}
