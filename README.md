# NiangPro

Un micro-framework PHP **ultra simple**, plus simple que Laravel : pas de magie, pas de compilation, juste du PHP.

## Installation

```bash
composer install
cp .env.example .env
./bin/niang serve
```

Visitez http://127.0.0.1:8000

## Structure

```
app/Controllers/    Vos contrôleurs
app/Middleware/      Vos middlewares
app/Models/           Vos modèles (Active Record minimal)
routes/web.php        Toutes vos routes
resources/views/      Vues PHP natives (pas de moteur de template)
src/Core/              Le cœur du framework
public/index.php      Point d'entrée unique
```

## Routes

```php
$router->get('/', [HomeController::class, 'index']);
$router->get('/hello/{name}', [HomeController::class, 'hello']);
$router->post('/echo', [HomeController::class, 'echoBody']);

$router->group(['prefix' => '/api', 'middleware' => [SomeMiddleware::class]], function ($router) {
    $router->get('/status', fn () => Response::json(['ok' => true]));
});
```

Les paramètres de route (`{id}`) sont injectés automatiquement dans les méthodes du contrôleur si le
nom de paramètre correspond.

## Contrôleurs

```php
class HomeController extends Controller
{
    public function index(): Response
    {
        return $this->view('home', ['title' => 'Salut']);
    }
}
```

## Middlewares

Implémentez `Niang\Core\Middleware` :

```php
class LogRequest implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // avant
        $response = $next($request);
        // après
        return $response;
    }
}
```

Attachez-le à une route : `$router->get('/ping', $action, [LogRequest::class]);`

## Modèles (Active Record minimal)

```php
class User extends Model
{
    // protected static string $table = 'users';
}

User::all();
User::find(1);
User::where('email', 'a@b.com');
User::create(['name' => 'Awa']);
User::update(1, ['name' => 'Fatou']);
User::destroy(1);
```

Configurez la connexion dans `.env` (`DB_CONNECTION=sqlite` par défaut, ou `mysql`/`pgsql`).

## Query Builder

Pour les requêtes plus riches qu'un `find`/`where` simple :

```php
Post::query()
    ->where('published', true)
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

Post::query()->where('id', 5)->update(['title' => 'Nouveau titre']);
```

## Migrations

```bash
./bin/niang make:migration create_posts_table
./bin/niang migrate
./bin/niang migrate:rollback
./bin/niang migrate:fresh
```

```php
Schema::create('posts', function ($table) {
    $table->id();
    $table->string('title');
    $table->text('body')->nullable();
    $table->timestamps();
});
```

## Seeders & factories

```bash
./bin/niang make:seeder DatabaseSeeder
./bin/niang db:seed
```

```php
Post::factory(fn () => [
    'title' => 'Article ' . random_int(1, 999),
    'body' => 'Contenu...',
])->count(10)->create();
```

Pas de génération de fausses données intégrée (pas de dépendance externe type Faker) : vous fournissez
la closure qui décrit un enregistrement.

## Relations

Les enregistrements restent de simples tableaux (pas d'hydratation d'objets) ; les relations sont des
méthodes statiques qui interrogent la table liée :

```php
class Post extends Model
{
    public static function comments(int|string $postId): array
    {
        return static::hasMany($postId, Comment::class, 'post_id');
    }

    public static function tags(int|string $postId): array
    {
        return static::belongsToMany($postId, Tag::class, 'post_tag', 'post_id', 'tag_id');
    }
}

class Comment extends Model
{
    public static function post(array $comment): ?array
    {
        return static::belongsTo($comment, Post::class, 'post_id');
    }
}
```

## Transactions

```php
DB::transaction(function () {
    $postId = Post::create([...]);
    Comment::create(['post_id' => $postId, ...]);
});
```
Rollback automatique si une exception est levée à l'intérieur.

## Connexions lecture / écriture

Par défaut, une seule connexion sert tout. Pour séparer lecture et écriture, ajoutez dans `.env` :

```
DB_READ_HOST=replica.example.com
DB_READ_DATABASE=niangpro
```

`DB::select()` utilise alors automatiquement la connexion `read`, et les écritures restent sur `write`.
Sans ces variables, les deux pointent vers la même base — rien à configurer par défaut.

## Sessions, CSRF & messages flash

```php
Session::put('user_id', 1);
Session::get('user_id');

$this->redirect('/contact')->with('success', 'Message envoyé !');
// dans la vue :
flashed('success');
```

Ajoutez `VerifyCsrfToken::class` aux routes POST/PUT/PATCH/DELETE qui traitent des formulaires HTML,
et déposez `<?= csrf_field() ?>` dans le `<form>`. Les routes API en JSON n'en ont pas besoin.

## Validation

```php
class ContactController extends Controller
{
    public function store(Request $request): Response
    {
        $this->validate($request, [
            'email' => 'required|email',
            'message' => 'required|string|min:10',
        ]);
        // ...
    }
}
```

Si la validation échoue : redirection automatique vers la page précédente avec les erreurs et l'ancienne
saisie en flash (`errors('email')`, `old('email')`), ou réponse JSON 422 si la requête attend du JSON.

## Pages d'erreur personnalisées

Créez `resources/views/errors/404.php` et `resources/views/errors/500.php` : ils remplacent
automatiquement les pages par défaut du framework.

## Logs

```php
Log::info('Utilisateur {id} connecté', ['id' => $user['id']]);
Log::error('Échec du paiement', ['order' => $orderId]);
```

Un fichier par jour dans `storage/logs/`. Les exceptions non interceptées y sont aussi consignées automatiquement.

## Vues : layouts, composants, échappement

Toujours du PHP natif — pas de compilateur de templates, pas de cache à invalider. Juste deux helpers :

```php
<?php layout('layouts.app', ['title' => 'Contact']); ?>

<h1>Contact</h1>
<p>Bonjour <?= e($name) ?></p>
```

`layout()` injecte le contenu déjà rendu de la vue courante dans `$content` de `layouts.app`.
`component('components/field-errors', ['field' => 'email'])` inclut un fragment réutilisable et retourne
son HTML. `e($valeur)` échappe pour l'affichage (alias court de `htmlspecialchars`) — à utiliser à chaque
sortie de donnée utilisateur.

## Pagination

```php
$paginator = Post::paginate(10, (int) $request->input('page', 1));
// $paginator->items, ->total, ->lastPage(), ->hasMorePages()
```

Dans la vue :

```php
<?php foreach ($paginator->items as $post): ?>...<?php endforeach; ?>
<?= $paginator->links('/blog') ?>
```

## Routes nommées, contraintes, ressources REST

```php
$router->get('/posts/{id}', [PostController::class, 'show'])
    ->name('posts.show')
    ->where(['id' => '[0-9]+']); // /posts/abc ne matche plus

route('posts.show', ['id' => 5]); // '/posts/5'
```

```php
$router->resource('tags', TagController::class);
// génère : GET /tags (index), GET /tags/create, POST /tags (store),
// GET /tags/{id} (show), GET /tags/{id}/edit, PUT /tags/{id} (update), DELETE /tags/{id} (destroy)
// nommées tags.index, tags.create, tags.store, tags.show, tags.edit, tags.update, tags.destroy
```

## Sous-domaines

```php
$router->domain('{tenant}.niangpro.test', function ($router) {
    $router->get('/', [TenantController::class, 'index']);
    // $request->param('tenant') disponible dans le contrôleur
});
```

## Cache de routes (production)

```bash
./bin/niang route:cache   # compile routes/web.php -> storage/framework/routes.php
./bin/niang route:clear   # supprime le cache
```

Limite assumée : les routes définies avec une closure ne sont pas sérialisables et sont exclues du
cache (avec un avertissement à la compilation). Utilisez des contrôleurs (`[Controller::class, 'method']`)
pour les routes qui doivent survivre au cache — c'est de toute façon la pratique recommandée.

## FormRequest (validation automatique à l'injection)

```bash
./bin/niang make:request ContactRequest
```

```php
class ContactRequest extends FormRequest
{
    public function rules(): array
    {
        return ['email' => 'required|email'];
    }

    // public function authorize(): bool { return true; }
}
```

Type-hintez-la directement dans le contrôleur : le Container la construit, vérifie `authorize()` puis
valide `rules()` **avant** d'appeler la méthode — plus besoin d'appeler `$this->validate()` :

```php
public function store(ContactRequest $request): Response
{
    $data = $request->validated();
}
```

## Tinker

```bash
./bin/niang tinker
> App\Models\Post::all()
```

REPL minimal (pas de complétion façon PsySH) : tape du PHP, `exit` pour sortir.

## Authentification

Convention : `app/Models/User.php` avec les colonnes `email` et `password`.

```php
Auth::attempt($email, $password); // true/false, connecte si succès
Auth::login($user);
Auth::logout();
Auth::check();  // true si connecté
Auth::user();   // le tableau utilisateur, ou null
Auth::id();

$user['password'] = Hash::make($plain); // Argon2id si dispo, sinon bcrypt
```

Un autre modèle ? `Auth::useModel(MonUser::class)`.

Protégez une route avec `Authenticate::class` (redirige vers `/login`, ou 401 JSON si la requête
l'attend) ; empêchez l'accès aux pages login/register une fois connecté avec `RedirectIfAuthenticated::class`.

## Autorisation (Gates)

```php
Gate::define('delete-post', fn (?array $user, array $post) => $user && $post['author_id'] === $user['id']);
```

Dans un contrôleur :

```php
$this->authorize('delete-post', $post); // lève une 403 si refusé
```

## Rate limiting

`ThrottleRequests::class` limite par défaut à 10 requêtes/minute par IP et par route (utile sur
`/login`, `/register`, tout formulaire public). Besoin d'une autre limite ailleurs ? Dupliquez la
classe avec vos valeurs plutôt que d'ajouter un système de configuration générique.

## En-têtes de sécurité

Appliqués automatiquement à **toutes** les réponses par `Application` (`X-Frame-Options`,
`X-Content-Type-Options`, `Referrer-Policy`, `Content-Security-Policy`, `Strict-Transport-Security`) —
sûr par défaut, sans middleware à ajouter. Ajustez-les dans `Application::applySecurityHeaders()` si
besoin (par exemple un CSP plus permissif pour charger un script tiers).

Toutes les requêtes SQL de l'ORM et du Query Builder passent par des requêtes préparées PDO — aucune
concaténation de valeurs utilisateur dans le SQL, nulle part.

## Cache applicatif

```php
Cache::remember('posts.index', 60, fn () => Post::all()); // TTL 60s
Cache::put('clé', $valeur, 300);
Cache::forget('clé');
```

Fichier (`storage/framework/cache/`), pas de dépendance à Redis/Memcached.

## Jobs différés

```bash
./bin/niang queue:work
```

```php
class SendWelcomeEmailJob extends Job
{
    public function __construct(private string $email) {}
    public function handle(): void { /* ... */ }
}

Queue::push(new SendWelcomeEmailJob($email));
```

File sur fichier (`storage/framework/queue/`) — pas de démon fourni : lancez `queue:work` via cron,
ou en boucle, selon vos besoins.

## Compression & supervision

Les réponses sont automatiquement compressées en gzip si le client l'accepte et que ça vaut le coût.
`GET /up` renvoie 200 (`{"status":"ok"}`) ou 503 si la base de données est injoignable — à brancher
sur votre outil de supervision.

## Passage en production

```bash
composer install --no-dev --optimize-autoloader
./bin/niang route:cache   # ou : ./bin/niang optimize
```

Activez `opcache.enable=1` et `opcache.validate_timestamps=0` dans le `php.ini` de production
(remettez `validate_timestamps=1` en développement, sinon vos modifications de code ne seront pas prises
en compte sans redémarrage).

## Tests

Les seules dépendances Composer du projet sont en `require-dev` (jamais livrées en production) :
PHPUnit, PHP-CS-Fixer, PHPStan.

```bash
composer test       # PHPUnit
composer lint        # PHP-CS-Fixer (dry-run)
composer lint:fix     # PHP-CS-Fixer (applique)
composer analyse      # PHPStan niveau 6
```

Client de test sans serveur HTTP réel (dispatche directement dans le Router) :

```php
use Niang\Core\Testing\TestCase;

class HomeTest extends TestCase
{
    public function test_home_page_loads(): void
    {
        $this->get('/')->assertOk()->assertSee('NiangPro');
    }
}
```

Assertions disponibles : `assertStatus`, `assertOk`, `assertRedirect`, `assertSee`, `assertDontSee`,
`assertJson`, `->json()`. Pour un test unitaire pur (sans HTTP), étendez directement
`PHPUnit\Framework\TestCase` — voir `tests/Unit/ValidatorTest.php`.

Limite assumée : les tests utilisent la même base sqlite que le développement local (`storage/database.sqlite`).
Pour une CI/vraie isolation, pointez `DB_DATABASE` vers `:memory:` dans un `.env` dédié aux tests.

## CLI

```bash
./bin/niang serve                    # démarre le serveur de dev
./bin/niang make:controller Blog     # génère app/Controllers/BlogController.php
./bin/niang make:model Post          # génère app/Models/Post.php
./bin/niang make:migration create_x  # génère database/migrations/..._create_x.php
./bin/niang make:seeder Demo         # génère database/seeders/DemoSeeder.php
./bin/niang migrate                  # applique les migrations en attente
./bin/niang migrate:rollback         # annule le dernier lot de migrations
./bin/niang migrate:fresh            # réinitialise la base et rejoue tout
./bin/niang db:seed                  # exécute DatabaseSeeder
./bin/niang route:cache              # compile les routes pour la prod
./bin/niang route:clear              # supprime le cache de routes
./bin/niang route:list               # liste toutes les routes
./bin/niang make:middleware Cors     # génère app/Middleware/Cors.php
./bin/niang make:request ContactRequest  # génère app/Requests/ContactRequest.php
./bin/niang tinker                   # REPL interactif
./bin/niang key:generate             # régénère APP_KEY dans .env
./bin/niang queue:work               # traite les jobs différés en attente
./bin/niang cache:clear              # vide le cache applicatif
./bin/niang optimize                 # cache les routes + rappels de prod
./bin/niang new mon-app              # crée un nouveau projet à partir de ce squelette
```

## Configuration

Fichiers `config/*.php`, chargés automatiquement au démarrage :

```php
// config/app.php
return ['name' => env('APP_NAME', 'NiangPro')];

config('app.name'); // notation pointée, avec valeur par défaut : config('app.name', 'Défaut')
```

## Service Providers

```php
class AppServiceProvider extends ServiceProvider
{
    public function register(): void { /* bindings dans le Container */ }
    public function boot(): void { /* écouteurs d'événements, etc. */ }
}
```

Déclarez-les dans `config/app.php` (`providers`). Tous les `register()` s'exécutent avant tout `boot()`.

## Événements

```php
Event::listen('user.registered', function (array $user) {
    Log::info('Nouvel utilisateur : {email}', ['email' => $user['email']]);
});

Event::dispatch('user.registered', $user);
```

Découple la logique secondaire (notifications, journalisation, futurs écouteurs) du contrôleur qui
déclenche l'action — sans passer par un vrai bus d'événements avec files et retries.

## Créer un nouveau projet

```bash
./bin/niang new mon-app
```

Clone ce squelette (sans `vendor/`, `.git/`, données locales), installe les dépendances, génère une
nouvelle `APP_KEY`. Une fois le paquet publié sur Packagist, `composer create-project niangpro/framework mon-app`
fera la même chose sans avoir de projet existant sous la main.

## Publier sur Packagist

Le `composer.json` est prêt (nom, description, mots-clés, licence). Pour publier réellement :

1. Poussez ce dépôt sur GitHub (remplacez les URLs `support` du `composer.json` par les vraies).
2. Créez un tag de version (`git tag v1.0.0 && git push --tags`).
3. Soumettez l'URL du dépôt sur [packagist.org](https://packagist.org/packages/submit).
4. Activez le webhook GitHub → Packagist pour que les futurs tags soient publiés automatiquement.

## Philosophie

- Aucune dépendance externe **à l'exécution** (PHPUnit/PHP-CS-Fixer/PHPStan sont en `require-dev`,
  jamais livrés en production).
- Un seul fichier par responsabilité (Router, Request, Response, Container, View, DB).
- Injection de dépendances automatique via Reflection, sans configuration.
- Pas de compilation de templates, pas de cache de config à vider en développement : vous modifiez,
  vous rafraîchissez.
