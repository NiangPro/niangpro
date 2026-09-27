# Stabilité de l'API publique

Roadmap §3.1 et §69-70. NiangPro suit le [versionnage sémantique](https://semver.org/lang/fr/) : dans
une même version majeure, une API **stable** ne change pas de façon incompatible (signature, comportement
documenté). Ce document dit ce qui est couvert par cette promesse.

| Statut | Promesse | Marqueur dans le code |
| --- | --- | --- |
| **Stable** | Aucune rupture avant la prochaine version majeure. Une suppression passe par une dépréciation d'au moins une version mineure. | aucun |
| **Expérimental** | Utilisable en production, mais la forme peut encore changer dans une version mineure (mentionné dans le CHANGELOG, section *Changed*). | `@experimental` |
| **Interne** | Détail d'implémentation : peut changer ou disparaître à tout moment. N'appelez pas ces classes ou méthodes directement. | `@internal` |
| **Déprécié** | Encore présent, supprimé à la version majeure suivante ; un avertissement est émis à l'usage. | `@deprecated` + `trigger_deprecation()` |

## Stable

- **Application et HTTP** : `Application` (`run()`, `handle()`, `debug()`), `Router` et `RouteRegistration`
  (routes, groupes, `resource()`, `match()`, `fallback()`, `name()`, `where()`, `bind()`), `Http\Request`,
  `Http\Response` (hors `eventStream()`), `Middleware`, `Controller`, `Http\UploadedFile`,
  `Http\JsonResource`, `Http\Psr7Bridge`, `Http\Psr15Adapter`, `Cors`, `MaintenanceMode`, `HealthCheck`.
- **Conteneur et configuration** : `Container` (`bind()`, `singleton()`, `instance()`, `make()`,
  `call()`, `when()`), `ServiceProvider`, `Config`, `Env`.
- **Base de données** : `Database\DB`, `Database\Model`, `Database\QueryBuilder`, `Database\Schema`,
  `Database\Blueprint`, `Database\ColumnDefinition`, `Database\ForeignKeyDefinition`,
  `Database\Migration`, `Database\Seeder`, `Database\Factory`, `Database\Expression`,
  `Database\Paginator`, `Database\SimplePaginator`, `Database\CursorPaginator`.
- **Validation** : `Validation\Validator` (et ses règles documentées), `Validation\FormRequest`,
  `Validation\ValidationException`.
- **Sécurité et authentification** : `Auth`, `Gate`, `Hash`, `Crypt`, `Csrf`, `Cookie`, `UrlSignature`,
  `ApiToken`, `AppKey`, `RateLimiter`, `TwoFactor`, `Totp`, `Session`.
- **Services** : `Cache`, `Queue`, `Job`, `Contracts\ShouldQueue`, `Event`, `Events\Dispatcher`,
  `Events\StoppableEvent`, événements du framework (`Events\ApplicationBooted`, `Events\RequestReceived`,
  `Events\RouteMatched`, `Events\ResponsePrepared`, `Events\RequestTerminated`), `Log`, `Logger`, `Mail`, `Mailable`, `MailAttachment`, `PendingMail`,
  `Storage`, `Notification`, `Contracts\NotificationChannel`, `Scheduling\Schedule`, `Lang`, `View`.
- **Tests** : `Testing\TestCase`, `Testing\TestResponse`, `Testing\RefreshDatabase`.
- **Console** : `Console\Command` (commandes sur mesure) et les commandes `niang` documentées.
- **Exceptions** : toutes les classes de `Exceptions\` sauf `Handler`.
- **Helpers globaux** de `packages/core/src/helpers.php` et `packages/http/src/helpers.php`.

## Expérimental

`OpenApi` (format du document généré), `OAuth` et `OAuth\*` (fournisseurs, forme du profil),
`Permission` (un seul rôle par utilisateur aujourd'hui), `Response::eventStream()` et
`Http\ServerSentEvent`, `Http\Client`, le disque `s3` de `Storage` (`Storage::temporaryUrl()`), `Redis` et les
pilotes `redis`, `Metrics` (noms et étiquettes des métriques), `Trace` (contenu de `Trace::context()`), `Tenancy` (forme de
la configuration).

Points d'extension (ADR 0012), par lesquels `Application::wire()` assemble les composants et qu'une
application peut aussi utiliser : `Log::contextUsing()`, `Http\Client::headersUsing()`, `Event::queueUsing()`,
`Cache::prefixUsing()`, `Model::tenantScopeUsing()`, `Metrics::isolateUsing()`, `Queue::stampUsing()`,
`Queue::wrapUsing()`, `Queue::afterUsing()`, `Container::resolveUsing()`.

## Interne

`Console\Commander`, `Console\ComposerHooks`, `Console\ProjectScaffolder`, `Console\SiteTypePrompt`,
`Console\ThemePackageInstaller`, `Console\ThemeSetup`, `Database\Grammar\*`, `Database\Migrator`,
`Database\EagerLoadBuilder` (utilisez-le via `Model::with()`), `RouteCache`, `ConfigCache`,
`DebugToolbar`, `DatabaseSessionHandler`, `ArraySessionHandler`, `SmtpTransport` (passez par `Mail`),
`Storage\S3Client` et `Storage\SigV4` (passez par `Storage`), `Redis\Connection` (passez par `Redis`),
`Queue\RedisQueue`, `RedisSessionHandler`,
`Jobs\*`, `Notifications\*` (canaux intégrés), `ContextualBindingBuilder` (via `Container::when()`),
`Exceptions\Handler`, `Scheduling\CronExpression`, `Scheduling\ScheduledTask`, `ViteAssets` (via
`vite_asset()`), et toute méthode marquée `@internal`.

## Déprécié

Rien pour l'instant.

## Politique de dépréciation (§70)

1. **Version N** : l'API est marquée `@deprecated` (avec son remplacement) et appelle
   `trigger_deprecation('niangpro/framework', 'N', 'Foo::bar() est déprécié, utilisez Foo::baz().')`.
   Le CHANGELOG le signale dans la section *Deprecated*.
2. En production, `Application::run()` consigne chaque dépréciation dans les logs (une fois par
   requête) ; PHPUnit les affiche dans la suite de tests.
3. **Version majeure suivante** : l'API est supprimée (section *Removed* du CHANGELOG).
