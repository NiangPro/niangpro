# NiangPro Framework — Roadmap technique complète

> **Objectif :** faire évoluer NiangPro d'un micro-framework PHP v1.0 prometteur vers un framework PHP moderne, stable, sécurisé, testable, extensible et adapté aux applications web, REST API, SaaS et backends mobiles, tout en conservant sa philosophie : **simplicité, lisibilité, PHP natif et faible dépendance**.

---

## 1. Vision du projet

### Positionnement recommandé

NiangPro ne doit pas chercher à devenir un clone de Laravel.

Le positionnement recommandé est :

> **NiangPro est un framework PHP moderne, minimaliste et pédagogique permettant de construire rapidement des applications web et API professionnelles avec un minimum d'abstraction et de dépendances.**

### Principes directeurs

1. PHP natif avant la magie.
2. API simples et prévisibles.
3. Secure by default.
4. Zéro dépendance runtime lorsque cela est raisonnablement possible.
5. Compatibilité avec l'écosystème PHP via les standards PSR.
6. Excellente expérience développeur.
7. Documentation claire.
8. Tests automatisés obligatoires.
9. Compatibilité SQLite, MySQL et PostgreSQL.
10. Architecture modulaire.
11. Rétrocompatibilité autant que possible.
12. Performance mesurée par des benchmarks, jamais supposée.

---

# 2. État cible

Architecture cible recommandée :

```text
NiangPro
├── Core
│   ├── Application
│   ├── Container
│   ├── Config
│   ├── Environment
│   └── Exceptions
│
├── HTTP
│   ├── Request
│   ├── Response
│   ├── Router
│   └── Middleware
│
├── Database
│   ├── Connection
│   ├── Query Builder
│   ├── ORM
│   ├── Schema
│   └── Grammar
│
├── Validation
├── Authentication
├── Authorization
├── Session
├── Cache
├── Queue
├── Events
├── Logging
├── View
├── Console
├── Testing
└── Contracts
```

Extensions optionnelles :

```text
NiangPro ecosystem
├── Redis
├── Mail
├── Storage
├── Notifications
├── Scheduler
├── OpenAPI
├── OAuth
├── WebSockets
└── Debug Toolbar
```

---

# 3. P0 — Stabilisation de la v1.0

## 3.1. Geler les contrats publics

Avant d'ajouter beaucoup de fonctionnalités, identifier les API publiques :

- `Application`
- `Container`
- `Router`
- `Request`
- `Response`
- `Middleware`
- `DB`
- `Model`
- `QueryBuilder`
- `Validator`
- `Session`
- `Auth`
- `Gate`
- `Cache`
- `Queue`
- `Event`
- `View`
- CLI

Documenter ce qui est :

- public et stable ;
- public mais expérimental ;
- interne ;
- déprécié.

### Ajouter

```text
@internal
@deprecated
@experimental
```

lorsque nécessaire.

---

# 4. P0 — Database : priorité absolue

Le problème principal à corriger est la génération de migrations actuellement orientée SQLite.

## 4.1. Introduire une couche Grammar

Architecture :

```text
Database
├── Connection
├── QueryBuilder
├── Grammar
│   ├── SQLiteGrammar
│   ├── MySqlGrammar
│   └── PostgresGrammar
└── Schema
    └── Blueprint
```

## 4.2. Types SQL abstraits

Exemple :

```php
$table->id();
$table->string('name');
$table->text('description');
$table->integer('age');
$table->boolean('active');
$table->decimal('amount', 12, 2);
$table->date('birth_date');
$table->dateTime('created_at');
$table->json('metadata');
```

Chaque driver traduit ensuite vers son SQL.

### SQLite

```sql
INTEGER PRIMARY KEY AUTOINCREMENT
```

### MySQL

```sql
BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
```

### PostgreSQL

```sql
BIGSERIAL PRIMARY KEY
```

## 4.3. Contraintes

Ajouter :

```php
$table->foreignId('user_id')->constrained();
$table->unique('email');
$table->index('status');
$table->foreign('user_id')
      ->references('id')
      ->on('users')
      ->cascadeOnDelete();
```

## 4.4. Modifications de colonnes

Prévoir :

```php
$table->renameColumn(...);
$table->dropColumn(...);
$table->change(...);
$table->rename(...);
```

## 4.5. Tests obligatoires

Chaque migration importante doit être testée sur :

- SQLite ;
- MySQL ;
- PostgreSQL.

---

# 5. P0 — Isolation complète des tests

Créer :

```text
.env.testing
```

avec :

```env
APP_ENV=testing
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
```

Créer un mécanisme :

```php
RefreshDatabase
```

permettant de reconstruire une base propre pour les tests.

Structure :

```text
tests/
├── Unit/
├── Feature/
├── Integration/
├── Security/
├── Database/
└── Console/
```

Ajouter :

- HTTP tests ;
- routing tests ;
- middleware tests ;
- ORM tests ;
- migration tests ;
- authentication tests ;
- authorization tests ;
- CSRF tests ;
- CLI tests.

---

# 6. P0 — Error Handling

Créer une couche centralisée :

```text
Exceptions
├── Handler
├── HttpException
├── ValidationException
├── AuthenticationException
├── AuthorizationException
├── NotFoundException
├── DatabaseException
└── ConfigurationException
```

Prévoir :

```php
abort(404);
abort(403);
abort(500);
```

et :

```php
throw new NotFoundException();
```

## Mode développement

Afficher :

- exception ;
- message ;
- fichier ;
- ligne ;
- stack trace ;
- requête ;
- route ;
- utilisateur ;
- temps d'exécution.

## Production

Afficher uniquement une page d'erreur sûre.

Ne jamais exposer :

- mots de passe ;
- variables sensibles ;
- clés API ;
- tokens ;
- stack traces ;
- secrets `.env`.

---

# 7. P0 — Configuration

Créer un système de configuration cohérent :

```text
config/
├── app.php
├── database.php
├── cache.php
├── session.php
├── queue.php
├── logging.php
├── mail.php
└── security.php
```

API :

```php
config('app.name');
config('database.default');
```

Ajouter :

```bash
niang config:cache
niang config:clear
```

Le cache de configuration doit être sûr et optionnel.

---

# 8. P0 — Environment

Renforcer :

```php
env('APP_ENV');
env('APP_DEBUG', false);
```

Ajouter validation des variables critiques.

Exemple :

```env
APP_KEY=
APP_ENV=production
APP_DEBUG=false
```

En production, refuser le démarrage si une configuration critique est absente.

---

# 9. P0 — Sécurité

Créer une vraie architecture :

```text
Security
├── Hash
├── Encryption
├── CSRF
├── Cookies
├── Headers
├── RateLimiter
├── Password
└── Sanitization
```

## Ajouter

### Security headers

- CSP ;
- HSTS ;
- X-Content-Type-Options ;
- Referrer-Policy ;
- Permissions-Policy ;
- Frame protection.

### Cookies

Support :

```text
HttpOnly
Secure
SameSite=Lax
SameSite=Strict
```

### Session fixation

Régénérer l'identifiant de session après authentification.

### Timing attacks

Utiliser des comparaisons sécurisées pour les secrets.

### Uploads

Créer une validation robuste :

- taille ;
- extension ;
- MIME réel ;
- nom ;
- stockage ;
- permissions ;
- noms aléatoires ;
- interdiction d'exécuter les fichiers uploadés.

---

# 10. P1 — PSR

Objectif : rendre NiangPro compatible avec l'écosystème PHP sans gonfler le cœur.

Priorité :

```text
PSR-4
PSR-11
PSR-3
PSR-15
PSR-7
PSR-17
PSR-14
```

## PSR-11

Le Container doit pouvoir implémenter :

```php
Psr\Container\ContainerInterface
```

## PSR-3

Le logger doit implémenter :

```php
Psr\Log\LoggerInterface
```

## PSR-15

Middleware HTTP compatible PSR.

## PSR-7

Prévoir une couche HTTP Message compatible.

---

# 11. P1 — Container DI professionnel

API cible :

```php
$container->bind(
    PaymentService::class,
    StripePaymentService::class
);

$container->singleton(
    CacheManager::class
);

$container->instance(
    SomeInterface::class,
    $object
);
```

Support :

- autowiring ;
- interfaces ;
- bindings ;
- singletons ;
- factories ;
- paramètres ;
- contextual bindings si nécessaire.

Ajouter des erreurs explicites :

```text
Cannot resolve dependency X
Circular dependency detected
Missing required parameter Y
```

---

# 12. P1 — Router 2.0

Ajouter ou stabiliser :

```text
GET
POST
PUT
PATCH
DELETE
OPTIONS
HEAD
```

API :

```php
$router->match(
    ['GET', 'POST'],
    '/contact',
    [...]
);
```

Ajouter :

```php
$router->fallback(...);
```

## Groupes

Support complet :

```php
$router->group([
    'prefix' => '/admin',
    'middleware' => ['auth']
], function ($router) {
    //
});
```

## Resource routes

```php
$router->resource(
    '/users',
    UserController::class
);
```

## Route model binding

Prévoir éventuellement :

```php
public function show(User $user)
```

comme fonctionnalité avancée, sans rendre l'ORM objet obligatoire.

## Route cache

```bash
niang route:cache
niang route:clear
niang route:list
```

---

# 13. P1 — Request / Response 2.0

Request :

```php
$request->method();
$request->path();
$request->query();
$request->input();
$request->json();
$request->header();
$request->cookie();
$request->ip();
$request->file();
```

Response :

```php
Response::json($data);
Response::redirect('/login');
Response::download($path);
Response::file($path);
Response::stream(...);
```

Ajouter :

```php
response()
    ->json(...)
    ->header(...)
    ->cookie(...);
```

---

# 14. P1 — Middleware

Créer les middleware officiels :

```text
StartSession
VerifyCsrfToken
Authenticate
RedirectIfAuthenticated
ThrottleRequests
HandleCors
SecurityHeaders
TrimStrings
ConvertEmptyStringsToNull
```

Définir clairement :

```php
interface Middleware
{
    public function handle(
        Request $request,
        Closure $next
    ): Response;
}
```

---

# 15. P1 — Validation 2.0

Ajouter les règles :

```text
required
nullable
string
integer
numeric
boolean
array
email
url
date
date_format
regex
min
max
between
in
not_in
same
different
confirmed
unique
exists
required_if
required_with
required_without
```

Exemple :

```php
return [
    'email' => [
        'required',
        'email',
        'unique:users,email'
    ]
];
```

Ajouter :

- messages personnalisables ;
- traduction française ;
- attributs personnalisés ;
- validation conditionnelle ;
- validation de fichiers ;
- validation de tableaux imbriqués.

---

# 16. P1 — FormRequest

Architecture :

```text
FormRequest
├── authorize()
├── rules()
├── messages()
└── attributes()
```

Cycle :

```text
Container
    ↓
FormRequest
    ↓
authorize()
    ↓
rules()
    ↓
validate()
    ↓
Controller
```

Ajouter des méthodes :

```php
$request->validated();
$request->safe();
$request->validated('email');
```

---

# 17. P1 — ORM : consolider le modèle actuel

NiangPro peut conserver son approche simple basée sur les tableaux.

API :

```php
User::all();
User::find(1);
User::where('email', $email);
User::create([...]);
```

Ne pas imposer immédiatement un ORM objet complexe.

## Query Builder

Ajouter :

```text
whereNull
whereNotNull
whereBetween
whereNotBetween
whereDate
whereColumn
having
havingRaw
distinct
union
exists
count
sum
avg
min
max
firstOrFail
findOrFail
```

---

# 18. P1 — Protection du Query Builder

Distinguer strictement :

```text
SQL values
```

et :

```text
identifiers
```

Les valeurs doivent toujours être bindées.

Les éléments comme :

```text
table names
column names
ORDER BY
GROUP BY
SQL fragments
```

doivent être validés ou whitelistés.

Éviter toute API qui permet facilement :

```php
->orderBy($_GET['sort'])
```

sans validation.

---

# 19. P1 — Relations ORM

Conserver l'approche simple mais ajouter progressivement :

```text
hasOne
hasMany
belongsTo
belongsToMany
```

Prévoir :

```php
Post::with('author')->get();
Post::with(['author', 'comments'])->get();
```

Objectif principal :

> éviter les problèmes N+1.

Ajouter éventuellement :

```text
lazy loading
eager loading
nested eager loading
```

---

# 20. P1 — Pagination

API :

```php
Post::query()
    ->paginate(15);
```

Retour :

```php
[
    'data' => [...],
    'current_page' => 1,
    'per_page' => 15,
    'total' => 120,
    'last_page' => 8
]
```

Ajouter :

```text
paginate()
simplePaginate()
cursorPaginate()
```

pour les grandes tables.

---

# 21. P1 — Authentication 2.0

Architecture :

```text
Authentication
├── Session
├── Token
├── API Token
└── Personal Access Token
```

API :

```php
Auth::attempt(...);
Auth::check();
Auth::user();
Auth::id();
Auth::logout();
```

Ajouter :

```text
remember me
password reset
email verification
login throttling
session regeneration
logout all devices
```

---

# 22. P1 — Authorization

Conserver les Gates et ajouter progressivement :

```text
Policies
Roles
Permissions
```

API :

```php
Gate::allows('posts.delete', $post);
```

ou :

```php
$user->can('delete', $post);
```

Prévoir des Policies :

```text
PostPolicy
UserPolicy
CommentPolicy
```

---

# 23. P1 — Cache

Créer :

```php
interface CacheStore
{
    public function get(string $key): mixed;
    public function put(
        string $key,
        mixed $value,
        int $ttl
    ): bool;
    public function forget(string $key): bool;
}
```

Drivers :

```text
Array
File
Database
Redis
```

API :

```php
Cache::get();
Cache::put();
Cache::remember();
Cache::forget();
Cache::has();
Cache::increment();
Cache::decrement();
```

---

# 24. P1 — Session drivers

Drivers :

```text
File
Database
Redis
Array
```

L'interface doit être indépendante du stockage.

---

# 25. P1 — Queue 2.0

Architecture :

```text
Queue
├── Sync
├── File
├── Database
└── Redis
```

Interface :

```php
interface QueueDriver
{
    public function push(Job $job): string;
    public function pop(): ?Job;
    public function release(...);
    public function delete(...);
}
```

Ajouter :

```text
retry
backoff
timeout
failed jobs
job IDs
queue names
delayed jobs
```

Commandes :

```bash
niang queue:work
niang queue:failed
niang queue:retry
niang queue:flush
```

---

# 26. P1 — Events typés

Évoluer de :

```php
Event::listen('user.registered', ...);
```

vers :

```php
Event::dispatch(
    new UserRegistered($user)
);
```

Supporter progressivement les événements sous forme d'objets.

Conserver les événements string comme compatibilité legacy si nécessaire.

---

# 27. P2 — Logging professionnel

PSR-3.

Drivers :

```text
File
Daily
ErrorLog
Syslog
```

Niveaux :

```text
debug
info
notice
warning
error
critical
alert
emergency
```

Support du contexte :

```php
Log::info(
    'User registered',
    ['user_id' => $id]
);
```

Ne jamais loguer :

```text
password
token
secret
credit card data
session secrets
```

---

# 28. P2 — Mail

Créer un package séparé :

```text
niangpro/mail
```

Support :

```text
SMTP
sendmail
log
array/testing
```

API :

```php
Mail::to($email)
    ->send(new WelcomeMail($user));
```

Prévoir intégration avec la queue.

---

# 29. P2 — Notifications

Canaux :

```text
mail
database
SMS
webhook
```

Architecture :

```php
Notification::send(
    $user,
    new PasswordResetNotification(...)
);
```

---

# 30. P2 — Storage

Créer :

```text
niangpro/storage
```

Drivers :

```text
Local
S3-compatible
```

API :

```php
Storage::put();
Storage::get();
Storage::delete();
Storage::exists();
Storage::url();
```

---

# 31. P2 — API Framework

Créer une couche API officielle.

Fonctionnalités :

```text
JSON responses
API middleware
API authentication
rate limiting
pagination
resources
validation
CORS
OpenAPI
```

Exemple :

```php
return response()->json([
    'data' => $users
]);
```

---

# 32. P2 — API Resources

API :

```php
return UserResource::collection(
    User::paginate(20)
);
```

Permettre :

```text
data
meta
links
relationships
```

sans imposer JSON:API complet.

---

# 33. P2 — CORS

Configuration :

```text
allowed_origins
allowed_methods
allowed_headers
exposed_headers
supports_credentials
max_age
```

Commande :

```bash
niang cors:check
```

Tests automatisés obligatoires.

---

# 34. P2 — Rate Limiting

Drivers :

```text
Array
File
Redis
Database
```

API :

```php
RateLimiter::for(
    'api',
    60,
    1
);
```

Support :

```text
IP
user ID
API token
route
custom key
```

---

# 35. P2 — OpenAPI

Générer une documentation API :

```text
/openapi.json
/docs
```

Décrire :

```text
routes
parameters
request body
responses
authentication
schemas
```

Ne pas rendre OpenAPI obligatoire pour les applications classiques.

---

# 36. P2 — CLI 2.0

Architecture :

```text
Console
├── Command
├── Input
├── Output
├── Argument
├── Option
└── Signature
```

Commandes :

```text
make:controller
make:model
make:migration
make:seeder
make:middleware
make:request
make:policy
make:job
make:event
make:command
make:test
```

Ajouter :

```bash
niang about
niang doctor
niang env
niang route:list
niang migrate
niang migrate:rollback
niang migrate:fresh
niang db:seed
niang optimize
```

---

# 37. P2 — `niang doctor`

Créer une commande de diagnostic :

```bash
niang doctor
```

Vérifications :

```text
PHP version
extensions
permissions
.env
APP_KEY
database
cache
storage
routes
configuration
```

Exemple :

```text
✓ PHP 8.3
✓ PDO
✓ SQLite
✓ Storage writable
✓ APP_KEY configured
⚠ Redis unavailable
```

---

# 38. P2 — Debug Toolbar

Uniquement en développement.

Afficher :

```text
request time
memory
queries
query time
route
middleware
events
cache hits
cache misses
```

Ne jamais activer en production.

---

# 39. P2 — Performance et benchmarks

Créer :

```text
benchmarks/
├── routing
├── container
├── request
├── response
├── database
├── rendering
└── full-request
```

Mesurer :

```text
requests/sec
p50
p95
p99
memory/request
startup time
```

Comparer objectivement avec :

```text
Native PHP
NiangPro
Slim
Laravel
Symfony
```

Publier les conditions de benchmark :

- PHP version ;
- CPU ;
- RAM ;
- OPcache ;
- database ;
- nombre de routes ;
- nombre de requêtes SQL.

---

# 40. P2 — OPcache / production

Documenter précisément :

```text
OPcache
realpath cache
autoload optimization
route cache
config cache
```

Créer :

```bash
niang optimize
niang optimize:clear
```

## État d'avancement : préchargement OPcache (NiangPro 2.0, quinzième jalon de P0 #15)

`preload.php`, à la racine du projet, précharge les classes de `src/Core/` (construit par `glob()`,
protégé par `function_exists('opcache_compile_file')`). C'est un réglage **serveur/déploiement**,
jamais quelque chose qu'une commande CLI ponctuelle comme `niang optimize` peut activer depuis une
requête isolée — elle se contente de rappeler que le fichier existe. Pour l'activer réellement,
dans le `php.ini` du serveur (jamais dans le projet, jamais par requête) :

```ini
opcache.preload=/chemin/absolu/vers/le/projet/preload.php
opcache.preload_user=www-data
```

Nécessite un redémarrage de PHP-FPM (ou du serveur web) à chaque déploiement pour que le nouveau
code soit repréchargé — comme `opcache.validate_timestamps=0`, à réserver à un environnement où le
déploiement redémarre déjà le processus PHP. `route:cache` et `config:cache` (voir `niang optimize`)
restent indépendants et s'appliquent même sans préchargement.

---

# 41. P2 — Scheduler

Créer un scheduler optionnel :

```php
Schedule::call(...)
    ->daily();
```

Support :

```text
everyMinute
hourly
daily
weekly
monthly
cron expression
```

CLI :

```bash
niang schedule:run
```

Pour la production, documenter l'utilisation avec cron/systemd.

---

# 42. P2 — Filesystem et uploads

API :

```php
$request->file('avatar');
```

Validation :

```text
required
image
mimes
mimetypes
max
dimensions
```

Sécurité :

- nom généré ;
- stockage hors exécution ;
- MIME vérifié ;
- taille limitée ;
- extension contrôlée.

---

# 43. P2 — Internationalisation

Créer :

```text
lang/
├── fr/
├── en/
└── ...
```

API :

```php
__('validation.required');
```

Support :

```text
translations
pluralization
locale
fallback locale
```

Priorité initiale :

```text
fr
en
```

---

# 44. P2 — Views et composants

Conserver les vues PHP natives.

Ajouter progressivement :

```php
view('users.index', [
    'users' => $users
]);
```

Composants :

```php
component('alert', [
    'type' => 'success'
]);
```

Layouts :

```php
layout('app');
```

Éviter de créer un moteur de template complexe.

Objectif :

> PHP reste PHP.

---

# 45. P2 — Assets

Ne pas imposer Node.js.

Prévoir :

```text
public/css
public/js
public/images
```

Optionnellement fournir un starter avec :

```text
Vite
Bootstrap
Tailwind
Alpine
```

mais ces outils doivent rester optionnels.

---

# 46. P3 — Architecture modulaire

Séparer les composants :

```text
niangpro/framework
niangpro/database
niangpro/redis
niangpro/mail
niangpro/storage
niangpro/openapi
niangpro/testing
niangpro/debug
```

Le framework de base reste minimal.

---

# 47. P3 — Plugins / packages

Créer une documentation expliquant comment développer :

```text
NiangPro package
```

Un package doit pouvoir :

- enregistrer des services ;
- publier une configuration ;
- publier des migrations ;
- enregistrer des routes ;
- enregistrer des commandes ;
- enregistrer des vues ;
- enregistrer des événements.

---

# 48. P3 — Service Providers

Introduire :

```php
class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        //
    }
}
```

Cycle :

```text
register()
    ↓
boot()
    ↓
Application ready
```

Attention à ne pas rendre les providers obligatoires pour toutes les applications.

---

# 49. P3 — Events / Hooks du framework

Prévoir :

```text
ApplicationStarting
ApplicationBooted
RequestReceived
RouteMatched
ResponsePrepared
RequestTerminated
```

Utile pour les extensions.

---

# 50. P3 — Multi-tenancy

Grâce au support des domaines/sous-domaines, prévoir une couche optionnelle :

```text
TenantResolver
TenantContext
TenantMiddleware
```

Exemple :

```text
client1.example.com
client2.example.com
```

ou :

```text
example.com/client1
```

Support éventuel :

```text
database-per-tenant
schema-per-tenant
shared-database
```

---

# 51. P3 — WebSockets / temps réel

Ne pas intégrer directement au cœur.

Créer un package :

```text
niangpro/realtime
```

Possibilités :

```text
WebSocket
SSE
Broadcasting
```

Utilisations :

```text
notifications
chat
dashboard
progression
live updates
```

---

# 52. P3 — OAuth / Social Authentication

Package optionnel :

```text
niangpro/oauth
```

Support potentiel :

```text
Google
GitHub
Facebook
Microsoft
Apple
```

Ne pas mettre les dépendances OAuth dans le cœur.

---

# 53. P3 — Observabilité

Créer des intégrations optionnelles :

```text
OpenTelemetry
metrics
tracing
structured logs
health checks
```

Endpoint :

```text
/health
```

ou :

```text
/health/live
/health/ready
```

---

# 54. P3 — Health Check

Commande :

```bash
niang health
```

Endpoint :

```text
GET /health
```

Vérifier :

```text
application
database
cache
queue
storage
```

Réponse API :

```json
{
    "status": "ok",
    "services": {
        "database": "ok",
        "cache": "ok",
        "storage": "ok"
    }
}
```

---

# 55. P3 — Sécurité avancée

Ajouter une suite de tests de sécurité :

```text
SQL injection
XSS
CSRF
session fixation
session hijacking
path traversal
file upload
open redirect
host header attacks
mass assignment
IDOR
rate limiting
security headers
```

Faire des audits réguliers avec :

```text
PHPStan
PHP-CS-Fixer
composer audit
```

et éventuellement des outils externes.

---

# 56. Qualité du code

Objectif :

```text
PHPStan niveau élevé
PSR-12 / standard de code cohérent
strict_types
typed properties
return types
final lorsque pertinent
interfaces pour les contrats importants
```

Privilégier :

```php
declare(strict_types=1);
```

dans les fichiers où cela est compatible avec la stratégie du projet.

---

# 57. Compatibilité PHP

Stratégie recommandée :

```text
PHP 8.1 minimum pour NiangPro 1.x
```

Puis, selon la version majeure :

```text
NiangPro 2.x → PHP 8.2+
NiangPro 3.x → PHP 8.3+
```

Ne pas augmenter brutalement la version minimale sans justification.

Documenter :

```text
minimum
recommended
tested
```

---

# 58. CI/CD

Mettre en place GitHub Actions :

```text
push
pull_request
tag
release
```

Pipeline :

```text
Composer install
↓
PHP lint
↓
PHPStan
↓
CS fixer check
↓
Unit tests
↓
Feature tests
↓
Security tests
↓
Database matrix
↓
Coverage
```

Tester :

```text
PHP 8.1
PHP 8.2
PHP 8.3
PHP 8.4+
```

selon les versions officiellement supportées.

---

# 59. Matrix de bases de données

CI :

```text
SQLite
MySQL
PostgreSQL
```

Chaque release doit valider :

```text
migrations
CRUD
transactions
joins
indexes
foreign keys
pagination
aggregates
```

---

# 60. Code coverage

Objectif progressif :

```text
v1.1 → 70%
v1.2 → 80%
v2.0 → 85%+
```

Le pourcentage seul ne suffit pas : couvrir surtout les composants critiques.

Priorité :

```text
Router
Container
Database
Auth
Validation
Security
HTTP
```

---

# 61. Documentation

Créer un vrai site de documentation.

Structure :

```text
docs/
├── getting-started
├── installation
├── architecture
├── routing
├── controllers
├── requests
├── responses
├── middleware
├── database
├── migrations
├── orm
├── validation
├── authentication
├── authorization
├── sessions
├── cache
├── queues
├── events
├── views
├── cli
├── testing
├── security
├── deployment
├── performance
└── packages
```

---

# 62. Documentation pédagogique

Comme NiangPro a un potentiel pédagogique important, chaque fonctionnalité devrait idéalement avoir :

```text
Concept
↓
Installation
↓
Exemple minimal
↓
Exemple professionnel
↓
API
↓
Pièges
↓
Tests
```

Exemple :

```php
$router->get('/users', [UserController::class, 'index']);
```

puis :

```php
$router->group([
    'prefix' => '/admin',
    'middleware' => ['auth', 'admin']
], function ($router) {
    //
});
```

---

# 63. Starter Kits

Créer plusieurs starters officiels :

```text
niangpro/starter-basic
niangpro/starter-api
niangpro/starter-auth
niangpro/starter-saas
```

## Basic

```text
Router
Controller
View
Database
```

## API

```text
JSON
Auth
CORS
Rate limiting
OpenAPI
```

## Auth

```text
Login
Register
Logout
Password reset
Email verification
```

## SaaS

```text
Users
Teams
Tenants
Subscriptions
Billing abstraction
```

## État d'avancement : thèmes de site (NiangPro 2.0, dixième jalon de P0 #15)

Plutôt que des paquets séparés, un premier pas vers les starter kits est livré dans le paquet
principal : à la création d'un projet (`composer create-project` ou `niang new`), NiangPro demande quel
type de site construire et installe un thème visiteur complet.

```text
✓ vitrine     ✓ ecommerce (sans paiement réel)     ✓ blog
✓ portfolio   ✓ landing                            ✓ minimal (squelette de démonstration)
```

Un thème est un dossier de `resources/scaffold/themes/<slug>/` : ajouter un dossier l'ajoute au catalogue.
Restent à faire pour rejoindre la vision ci-dessus : les starters `api`, `auth` et `saas`, et la
publication de thèmes sous forme de paquets Composer. Voir le CHANGELOG (« Thèmes de site à la création
d'un projet ») pour les décisions prises.

---

# 64. Générateurs CLI

Créer :

```bash
niang new blog
```

Structure :

```text
blog/
├── app/
├── config/
├── database/
├── public/
├── resources/
├── routes/
├── storage/
├── tests/
└── composer.json
```

Ajouter des options :

```bash
niang new blog --database=mysql
niang new api --auth
niang new blog --testing
```

---

# 65. Compatibilité avec les hébergements classiques

Documentation pour :

```text
Apache
Nginx
PHP-FPM
shared hosting
VPS
Docker
```

Objectif :

> Une application NiangPro doit pouvoir être déployée facilement sans infrastructure complexe.

---

# 66. Docker

Créer un exemple officiel :

```text
docker/
├── php
├── nginx
├── mysql
└── compose.yaml
```

Commande :

```bash
docker compose up -d
```

Prévoir aussi un environnement sans Docker pour rester fidèle à la simplicité du framework.

---

# 67. Deployment

Documenter :

```text
development
staging
production
```

Checklist production :

```text
APP_ENV=production
APP_DEBUG=false
APP_KEY configurée
HTTPS
cookies sécurisés
OPcache
config cache
route cache
storage permissions
database backups
logs
health checks
queue workers
```

---

# 68. Backups

Ne pas implémenter nécessairement un système de backup dans le cœur.

Documenter :

```text
database backups
storage backups
configuration backups
```

et éventuellement fournir un package CLI.

---

# 69. Versioning

Utiliser strictement :

```text
Semantic Versioning
```

Exemple :

```text
1.0.0
1.1.0
1.1.1
2.0.0
```

### Patch

Corrections sans changement d'API.

### Minor

Nouvelles fonctionnalités compatibles.

### Major

Breaking changes.

---

# 70. Deprecation policy

Avant suppression :

```text
Version N
    ↓
deprecated
    ↓
documentation warning
    ↓
Version N+1
    ↓
removal
```

Fournir :

```php
trigger_deprecation(...)
```

ou une stratégie équivalente.

---

# 71. Changelog

Créer :

```text
CHANGELOG.md
```

Format :

```text
Added
Changed
Deprecated
Removed
Fixed
Security
```

Chaque release doit être documentée.

---

# 72. Security Policy

Créer :

```text
SECURITY.md
```

Contenu :

- comment signaler une vulnérabilité ;
- versions supportées ;
- délai de réponse ;
- procédure de publication ;
- crédits de sécurité.

---

# 73. CONTRIBUTING

Créer :

```text
CONTRIBUTING.md
```

Inclure :

```text
installation
tests
coding standards
pull requests
commit messages
issue templates
```

---

# 74. Architecture Decision Records

Créer :

```text
docs/adr/
```

Exemples :

```text
0001-framework-philosophy.md
0002-database-grammar.md
0003-native-php-views.md
0004-array-based-orm.md
0005-psr-compatibility.md
```

Cela empêchera l'architecture de devenir incohérente avec le temps.

---

# 75. Roadmap de versions proposée

## v1.1 — Stabilisation

Priorités :

```text
✓ migrations multi-SGBD
✓ isolation tests
✓ error handling
✓ sécurité renforcée
✓ configuration
✓ database grammar
✓ documentation
✓ CI
✓ PHPStan
✓ benchmarks
```

---

## v1.2 — DX

```text
✓ Container amélioré
✓ Router amélioré
✓ Validation avancée
✓ FormRequest
✓ CLI 2.0
✓ niang doctor
✓ cache drivers
✓ session drivers
✓ pagination
✓ ORM improvements
```

---

## v1.3 — API

```text
✓ API authentication
✓ JSON resources
✓ CORS
✓ rate limiting
✓ OpenAPI
✓ API testing
```

---

## v1.4 — Infrastructure

```text
✓ Redis
✓ Queue database
✓ Queue Redis
✓ Mail
✓ Storage
✓ Notifications
✓ Scheduler
```

---

## v1.5 — Écosystème

```text
✓ Packages officiels
✓ Service Providers
✓ Plugin system
✓ Starter kits
✓ documentation avancée
```

---

## v2.0 — Framework mature

```text
✓ architecture modulaire
✓ PSR complet selon pertinence
✓ ORM avancé optionnel
✓ policies
✓ API platform
✓ observability
✓ multi-tenancy optionnel
✓ performance optimisée
✓ excellente DX
```

---

# 76. Priorisation globale

## 🔴 Critique

1. Database Grammar.
2. Migrations MySQL/PostgreSQL.
3. Tests isolés.
4. Error handling.
5. Security audit.
6. CI.
7. Documentation API interne.
8. Stabilisation du Container.
9. Request/Response contracts.
10. Benchmarks.

## 🟠 Haute priorité

11. PSR-11.
12. PSR-3.
13. Router 2.0.
14. Validation 2.0.
15. Pagination.
16. ORM relations.
17. Authentication améliorée.
18. Authorization.
19. Cache drivers.
20. Session drivers.
21. Queue drivers.

## 🟡 Moyenne priorité

22. API resources.
23. CORS.
24. OpenAPI.
25. Mail.
26. Storage.
27. Notifications.
28. Scheduler.
29. Debug toolbar.
30. Internationalisation.

## 🟢 Long terme

31. Plugins.
32. Service Providers.
33. Multi-tenancy.
34. WebSockets.
35. OAuth.
36. Observability.
37. Starter SaaS.
38. Ecosystème de packages.

---

# 77. Architecture finale recommandée

```text
                         NIANGPRO
                            │
             ┌──────────────┴──────────────┐
             │                             │
           CORE                         EXTENSIONS
             │                             │
     ┌───────┼────────┐          ┌─────────┼──────────┐
     │       │        │          │         │          │
    HTTP     DB     Container    Redis     Mail     Storage
     │       │        │          │         │          │
  Router   ORM      DI          Queue    SMTP        S3
  Request  Query    Config      Cache    Notify      Files
  Response Schema
  Middleware
     │
     ├── Validation
     ├── Auth
     ├── Authorization
     ├── Session
     ├── View
     ├── Events
     ├── Logging
     ├── Console
     └── Testing
```

---

# 78. Règle stratégique essentielle

À chaque nouvelle fonctionnalité, poser quatre questions :

### 1. Est-elle indispensable au cœur ?

Si non :

> package séparé.

### 2. Peut-elle être implémentée sans dépendance ?

Si oui :

> préférer l'implémentation native.

### 3. Est-elle compatible avec un standard PHP ?

Si oui :

> implémenter le standard.

### 4. Peut-elle être testée ?

Si non :

> ne pas la considérer comme terminée.

---

# 79. Definition of Done

Une fonctionnalité NiangPro n'est terminée que si :

```text
[ ] API définie
[ ] code implémenté
[ ] tests unitaires
[ ] tests d'intégration si nécessaire
[ ] tests sécurité si nécessaire
[ ] documentation
[ ] exemple
[ ] PHPStan
[ ] coding standards
[ ] compatibilité DB si concernée
[ ] changelog
[ ] migration si nécessaire
[ ] rétrocompatibilité vérifiée
```

---

# 80. Objectif final

À terme, NiangPro doit permettre de construire :

```text
Sites web
Applications CRUD
Dashboards
Applications administratives
REST APIs
Backends mobiles
SaaS
Applications multi-tenant
Plateformes e-learning
ERP
Systèmes de gestion
Applications temps réel
```

avec un socle :

```text
Simple
Rapide
Sécurisé
Testable
Extensible
Documenté
PSR-compatible
Multi-SGBD
```

---

# 81. Conclusion

La stratégie recommandée n'est pas :

> « Ajouter toutes les fonctionnalités de Laravel. »

La stratégie est :

> **Construire un cœur NiangPro extrêmement propre et minimal, puis développer un écosystème de packages officiels autour de ce cœur.**

Le cœur doit rester petit :

```text
Core
├── Application
├── Container
├── HTTP
├── Router
├── Middleware
├── Database
├── Validation
├── Security
├── View
└── Console
```

Tout ce qui est lourd ou spécialisé doit pouvoir devenir une extension :

```text
Redis
Mail
Storage
OAuth
OpenAPI
WebSockets
Notifications
Observability
```

### Priorité immédiate

Si le développement reprend maintenant, l'ordre conseillé est :

```text
1. Database Grammar
2. Migrations SQLite/MySQL/PostgreSQL
3. Tests isolés
4. Error Handler
5. Security hardening
6. CI/CD
7. Container contracts
8. Router 2.0
9. Validation 2.0
10. ORM / pagination / relations
11. PSR-11 + PSR-3
12. API layer
13. Redis / Queue / Mail / Storage
14. Packages & ecosystem
15. NiangPro 2.0
```

**Le principe à conserver pendant toute l'évolution :**

> **NiangPro doit être facile à apprendre, facile à lire, facile à tester et difficile à utiliser de manière dangereuse.**
