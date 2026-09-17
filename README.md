<p align="center"><img src="public/logo.svg" width="140" alt="Logo NiangPro"></p>

# NiangPro

Un micro-framework PHP **ultra simple**, plus simple que Laravel : pas de magie, pas de compilation, juste du PHP.

## Installation

Pour démarrer un nouveau projet (le package est publié sur [Packagist](https://packagist.org/packages/niangpro/framework)) :

```bash
composer create-project niangpro/framework mon-app
cd mon-app
./bin/niang serve
```

Visitez http://127.0.0.1:8000

Pour contribuer au framework lui-même (cloner ce dépôt directement) :

```bash
git clone https://github.com/NiangPro/niangpro.git
cd niangpro
composer install
cp .env.example .env
./bin/niang key:generate
./bin/niang serve
```

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
User::findOrFail(1);  // lève NotFoundException (404) si absent, plutôt que null
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
    ->select('id', 'title')
    ->where('published', true)
    ->orWhere('author_id', 1)
    ->whereIn('category_id', [1, 2, 3])
    ->join('users', 'posts.author_id', '=', 'users.id')
    ->groupBy('category_id')
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->offset(20)
    ->get();

Post::query()->where('id', 5)->update(['title' => 'Nouveau titre']);
Post::query()->where('published', true)->count();
Post::query()->where('id', 5)->lockForUpdate()->first(); // SELECT ... FOR UPDATE, dans une transaction
Post::query()->onConnection('read')->get(); // force la connexion 'read' ou 'write'

// Filtres supplémentaires
Post::query()->whereNull('deleted_at')->get();
Post::query()->whereBetween('views', [10, 1000])->get();
Post::query()->whereColumn('updated_at', '>', 'created_at')->get();
Post::query()->select('category_id')->distinct()->get();
Post::query()->groupBy('category_id')->having('id', '>', 1)->get();

// Agrégats
Post::query()->count();          // ou count('id')
Post::query()->sum('views');
Post::query()->avg('views');
Post::query()->min('views');
Post::query()->max('views');
Post::query()->where('id', 5)->exists();  // bool

Post::query()->where('id', 5)->firstOrFail();  // lève NotFoundException si aucune ligne
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
    $table->string('title', 255);       // longueur par défaut : 255
    $table->text('body')->nullable();
    $table->integer('views')->default(0);
    $table->boolean('published')->default(false);
    $table->decimal('price', 8, 2)->nullable();  // precision, scale
    $table->float('rating')->nullable();
    $table->date('published_at')->nullable();
    $table->timestamp('deleted_at')->nullable();
    $table->json('metadata')->nullable();
    $table->foreignId('author_id')->constrained();  // -> table `authors`, colonne `id`
    $table->string('slug')->unique();
    $table->timestamps();               // created_at + updated_at

    $table->unique(['title', 'author_id']);  // contrainte unique multi-colonnes
    $table->index('published_at');
});
```

Chaque colonne accepte `->nullable()`, `->default($valeur)` et `->unique()`, chaînables entre eux.

### Multi-SGBD (SQLite, MySQL, PostgreSQL)

Les migrations sont traduites par un `Grammar` propre à chaque moteur (`DB_CONNECTION` dans `.env`) :
`$table->id()` génère `INTEGER PRIMARY KEY AUTOINCREMENT` en SQLite, `BIGINT UNSIGNED AUTO_INCREMENT
PRIMARY KEY` en MySQL, `BIGSERIAL PRIMARY KEY` en PostgreSQL — sans rien changer à vos migrations.

```php
$table->foreignId('author_id')->constrained();                    // devine la table `authors`
$table->foreignId('author_id')->constrained('users');              // table explicite
$table->foreign('author_id')->references('id')->on('authors')      // syntaxe complète
    ->cascadeOnDelete();                                            // ou ->nullOnDelete() / ->restrictOnDelete()

$table->renameColumn('old', 'new');
$table->dropColumn('champ_obsolete');
Schema::rename('anciens_posts', 'posts');
```

En SQLite, les clés étrangères sont activées (`PRAGMA foreign_keys = ON`) — comme en MySQL/PostgreSQL,
une insertion référençant une ligne inexistante est rejetée.

Limite assumée : `->change()` (modifier le type d'une colonne existante) n'est pas encore supporté —
les trois moteurs divergent trop pour une traduction fiable (SQLite ne le permet même pas nativement
sans reconstruire la table). Pour l'instant, gérez ce cas via une nouvelle migration qui recrée la colonne.

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
    public static function author(int|string $authorId): ?array
    {
        return static::hasOne($authorId, Author::class, 'post_id');
    }

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

### Eager loading (éviter le N+1)

Appeler `Post::comments($id)` pour chaque post d'une liste déclenche une requête par post (N+1).
`Model::with()` charge chaque relation en une seule requête pour toute la collection, via `whereIn`
(hasMany/belongsTo) ou une jointure (belongsToMany) :

```php
class Post extends Model
{
    public static function eagerLoadable(): array
    {
        return [
            'comments' => fn (array $posts) => static::loadMany($posts, 'comments', Comment::class, 'post_id'),
            'tags' => fn (array $posts) => static::loadManyToMany($posts, 'tags', Tag::class, 'post_tag', 'post_id', 'tag_id'),
        ];
    }
}

class Comment extends Model
{
    public static function eagerLoadable(): array
    {
        return [
            'post' => fn (array $comments) => static::loadOne($comments, 'post', Post::class, 'post_id'),
        ];
    }
}

Post::with(['comments', 'tags'])->get();               // 3 requêtes, quel que soit le nombre de posts
Post::with('comments')->where('published', true)->get();
Post::with('comments')->first();
Post::with('comments')->paginate(10, $page);
```

`with()` renvoie un `EagerLoadBuilder` qui délègue `where`/`orderBy`/`limit`/etc. au `QueryBuilder`
sous-jacent, puis charge les relations déclarées après `get()`/`first()`/`paginate()`. Trois méthodes
protégées de `Model` couvrent les trois types de relation : `loadMany()` (hasMany), `loadOne()`
(belongsTo), `loadManyToMany()` (belongsToMany via pivot) — toujours des tableaux associatifs, jamais
d'objets hydratés.

Pour vérifier qu'un endroit précis n'a plus de N+1, `DB::resetQueryCount()` puis `DB::queryCount()`
donnent le nombre exact de requêtes exécutées entre les deux appels.

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

Règles disponibles :

```text
required          string            numeric           integer
boolean           array             email             url
date              date_format:F     min:n              max:n
between:min,max   in:a,b,c          not_in:a,b,c       same:champ
different:champ   regex:/motif/     confirmed          nullable
required_if:champ,valeur           required_with:champ
required_without:champ             unique:table,colonne[,id_à_ignorer]
exists:table,colonne
```

`nullable` : si le champ est vide, les autres règles ne s'appliquent pas (sinon un champ absent y
échoue normalement). `unique`/`exists` interrogent la base — `unique:users,email,5` ignore la ligne
d'id 5 (cas « je modifie mon propre profil »). `min`/`max`/`between` comparent une longueur de chaîne
ou une valeur numérique selon le type du champ.

Validation d'un tableau, élément par élément :

```php
$this->validate($request, ['items.*.name' => 'required|string']);
// erreurs indexées : items.0.name, items.1.name...
```

Messages et noms de champs personnalisés (aussi disponibles sur `FormRequest` via `messages()` et
`attributes()`) :

```php
$this->validate($request, ['email' => 'required|email'],
    messages: ['email.required' => 'Merci de renseigner votre email.'],
    attributes: ['email' => 'Adresse email']
);
```

Si la validation échoue : redirection automatique vers la page précédente avec les erreurs et l'ancienne
saisie en flash (`errors('email')`, `old('email')`), ou réponse JSON 422 si la requête attend du JSON.

## Gestion des erreurs

Tout passe par un point d'entrée unique : `Niang\Core\Exceptions\Handler`. Le routeur, les
middlewares (CSRF, rate limiting, authentification) et vos contrôleurs n'ont plus besoin de
construire eux-mêmes une réponse d'erreur — il suffit de lever une exception.

```php
abort(404);
abort(404, 'Article introuvable.');           // message personnalisé (visible en JSON)
abort(403, 'Réservé aux administrateurs.');

throw new \Niang\Core\Exceptions\NotFoundException('Article introuvable.');
throw new \Niang\Core\Exceptions\AuthenticationException();
```

Le Handler adapte automatiquement la réponse :

- **Client JSON** (`Accept: application/json`) → `{"message": "..."}` avec le bon statut.
- **Client HTML** → la page dédiée si elle existe (`resources/views/errors/404.php`,
  `403.php`, `500.php`), sinon `resources/views/errors/generic.php` avec le message.
- **`APP_DEBUG=true`** → page de debug complète (exception, fichier:ligne, requête, route,
  utilisateur connecté, durée, stack trace) — jamais affichée si `APP_DEBUG=false`.
- Une `\PDOException` est toujours journalisée et jamais montrée telle quelle en production
  (le SQL et la chaîne de connexion ne doivent pas fuiter).

Créez `resources/views/errors/{code}.php` (404, 403, 500...) pour personnaliser une page précise ;
`resources/views/errors/generic.php` sert de filet pour tous les autres statuts (401, 419, 429...).

## Logs

```php
Log::info('Utilisateur {id} connecté', ['id' => $user['id']]);
Log::error('Échec du paiement', ['order' => $orderId]);
```

Niveaux disponibles (style PSR-3) : `emergency`, `alert`, `critical`, `error`, `warning`, `notice`,
`info`, `debug`. Les `{clé}` dans le message sont remplacées par les valeurs correspondantes du tableau
de contexte. Un fichier par jour dans `storage/logs/`. Les exceptions non interceptées y sont aussi
consignées automatiquement.

Pour injecter un logger plutôt qu'appeler la façade statique (interop avec du code tiers, tests avec
un mock), `Niang\Core\Logger` implémente `Psr\Log\LoggerInterface` et écrit dans les mêmes fichiers
— voir [PSR-11 et PSR-3](#psr-11-et-psr-3).

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

Autres helpers globaux utiles : `view('home', ['title' => 'Salut'])` retourne directement une `Response`
HTML (équivalent de `$this->view()` en dehors d'un contrôleur) ; `json_response($data, 201)` retourne une
`Response` JSON ; `dd($valeur, ...)` (*dump and die*) affiche une variable et arrête l'exécution — pratique
en debug, à retirer avant de committer.

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

## API : JSON Resources & CORS

`JsonResource` enveloppe un enregistrement dans `{"data": ...}` — surchargez `toArray()` pour
choisir exactement les champs exposés (masquer un mot de passe haché, renommer une clé, ajouter un
champ calculé) plutôt que de renvoyer l'enregistrement brut de la base :

```php
class PostResource extends JsonResource
{
    public function toArray(): array
    {
        return [
            'id' => $this->resource['id'],
            'title' => $this->resource['title'],
            'comments_count' => count($this->resource['comments'] ?? []),
        ];
    }
}

// un seul enregistrement
(new PostResource($post))->toResponse();

// une collection (tableau ou Paginator) -> {"data": [...], "meta": {...}, "links": {...}}
PostResource::collection(Post::with('comments')->paginate(10, $page))->toResponse();
```

Pas de JSON:API complet imposé : juste `data`/`meta`/`links`, la partie utile sans la complexité de
la spec entière. `meta` (`current_page`, `last_page`, `per_page`, `total`) et `links`
(`prev`/`next`) n'apparaissent que si la source est un `Paginator`.

CORS se configure dans `config/cors.php` (`allowed_origins`, `allowed_methods`, `allowed_headers`,
`exposed_headers`, `supports_credentials`, `max_age`) et s'applique via le middleware
`App\Middleware\HandleCors` :

```php
$router->group(['prefix' => '/api', 'middleware' => [HandleCors::class]], function ($router) {
    $router->get('/posts', [PostController::class, 'apiIndex']);
    $router->options('/posts', fn () => Response::html('', 204)); // chaque route API a besoin de sa contrepartie OPTIONS
});
```

`HandleCors` répond directement au préflight `OPTIONS` (204, sans exécuter la route ni ses autres
middlewares) et ajoute les en-têtes `Access-Control-*` à la vraie réponse. Avec
`supports_credentials: true`, l'origine exacte de la requête est toujours reflétée (jamais `*`,
que les navigateurs rejettent dans ce cas) — sans credentials et avec `allowed_origins: ['*']`,
c'est `*` littéral.

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

Une seule action pour plusieurs méthodes :

```php
$router->match(['GET', 'POST'], '/contact', [ContactController::class, 'handle']);
```

Route de secours quand rien ne correspond (remplace la 404 par défaut) :

```php
$router->fallback(function () {
    return json_response(['message' => 'Page introuvable.'], 404);
});
```

`HEAD` fonctionne automatiquement sur toute route `GET` (corps vidé, en-têtes/statut conservés) — pas
besoin de la déclarer. Pour un comportement `HEAD` différent du `GET` correspondant, déclarez-le
explicitement avec `$router->head(...)` ; elle garde alors entièrement la main sur sa réponse.

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
Auth::guest();  // true si non connecté (inverse de check())
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

Gate::allows('delete-post', $post); // true/false, sans lever d'exception
Gate::denies('delete-post', $post); // inverse de allows()
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
    public int $tries = 3; // par défaut : 1 (aucune retentative)

    public function __construct(private string $email) {}
    public function handle(): void { /* ... */ }
}

Queue::push(new SendWelcomeEmailJob($email));
Queue::later(300, new SendWelcomeEmailJob($email)); // dû dans 5 minutes
```

File sur fichier (`storage/framework/queue/`) — pas de démon fourni : lancez `queue:work` via cron,
ou en boucle, selon vos besoins.

Un job qui échoue est retenté jusqu'à `Job::$tries` fois, avec un backoff exponentiel (10s, 20s,
40s...) entre les tentatives, puis déplacé vers les jobs échoués :

```bash
./bin/niang queue:failed          # liste les jobs qui ont épuisé leurs tentatives
./bin/niang queue:retry <id>      # remet un job échoué en file, tentatives réinitialisées
./bin/niang queue:flush           # supprime définitivement tous les jobs échoués
```

## Stockage de fichiers

Disque local uniquement (`storage/app/`) — un driver S3 demanderait un SDK externe, contraire au
principe « sans dépendance d'implémentation à l'exécution » du framework :

```php
Storage::put('avatars/1.png', $contents);
Storage::get('avatars/1.png');     // contenu, ou null si absent
Storage::exists('avatars/1.png');
Storage::delete('avatars/1.png');
Storage::size('avatars/1.png');    // en octets, ou null
Storage::url('avatars/1.png');     // '/storage/avatars/1.png' — à router vers Storage::get() si besoin de le servir
```

Les chemins contenant `..` sont rejetés (`InvalidArgumentException`) : sans ça, un chemin construit
à partir d'une entrée utilisateur pourrait écrire ou lire en dehors de `storage/app/`.

## Emails

Deux drivers, pilotés par `MAIL_MAILER` dans `.env` (`log` par défaut) — pas d'envoi SMTP réel :
ça demanderait soit une extension, soit un client écrit à la main non vérifiable dans cet
environnement, et une absence assumée vaut mieux qu'une implémentation non testée :

```php
class WelcomeMailable extends Mailable
{
    public function __construct(private string $name) {}
    public function subject(): string { return 'Bienvenue'; }
    public function body(): string { return "Bonjour {$this->name} !"; }
}

Mail::to('awa@example.com')->send(new WelcomeMailable('Awa'));
```

`MAIL_MAILER=log` (défaut) écrit le contenu complet dans `storage/logs/` — pratique en développement
pour lire un lien de vérification sans boîte mail réelle (ne le gardez pas en production si vos
emails transportent des secrets). `MAIL_MAILER=array`, ou `Mail::fake()` dans un test, garde les
emails en mémoire pour `Mail::sent()` plutôt que de les journaliser :

```php
Mail::fake();
$this->post('/register', [...]);
$this->assertCount(1, Mail::sent());
```

## Compression & supervision

Les réponses sont automatiquement compressées en gzip si le client l'accepte et que ça vaut le coût.
`GET /up` renvoie `{"status":"ok","database":true}` (200) si la base de données répond, ou
`{"status":"degraded","database":false}` (503) sinon — à brancher sur votre outil de supervision.

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

### Isolation complète

Les tests tournent entièrement isolés du développement local, sans rien à configurer :

- `phpunit.xml` force `APP_ENV=testing`, ce qui fait charger **`.env.testing`** (committé — clé de
  test, pas un secret) au lieu de `.env`.
- `.env.testing` pointe `DB_DATABASE` sur **`:memory:`** : jamais `storage/database.sqlite`.
- `Niang\Core\Testing\TestCase` migre automatiquement cette base en mémoire au premier test qui en a
  besoin (idempotent, pas de doublon).
- `tests/bootstrap.php` repart d'un `storage/framework/` propre à chaque run : le cache applicatif et
  le rate limiting (sur fichier) ne s'accumulent pas d'une exécution de la suite à l'autre.

Pour qu'un test qui écrit en base n'affecte pas les suivants (la base `:memory:` survit tout le run
PHPUnit, contrairement à un vrai processus web), utilisez le trait `RefreshDatabase` — chaque test est
enrobé dans une transaction annulée à la fin :

```php
use Niang\Core\Testing\RefreshDatabase;
use Niang\Core\Testing\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_a_user(): void
    {
        $this->post('/register', [...]);
        $this->assertNotEmpty(User::where('email', 'awa@example.test'));
    }
}
```

Conséquence directe : `composer test`, `composer lint` et `composer analyse` fonctionnent sans aucune
préparation (pas de `.env`, pas de `migrate`) — vérifié en CI comme en local.

### CI multi-versions et multi-SGBD

`.github/workflows/ci.yml` fait tourner :

- **`test`** : lint + PHPStan + tests, en matrice sur **PHP 8.1, 8.2, 8.3 et 8.4** (SQLite `:memory:`).
- **`mysql`** / **`postgres`** : les migrations (`tests/Database/`) contre un vrai conteneur MySQL 8
  et PostgreSQL 16 — pas seulement SQLite. `DB_CONNECTION`/`DB_HOST`/... passés en variables
  d'environnement CI ont priorité sur `.env.testing` (une variable d'environnement déjà présente
  garde toujours la priorité sur les fichiers `.env*`, utile aussi derrière un vrai hébergeur).

Ces deux jobs ont fait remonter un vrai bug avant même d'être poussés en CI (testé en local contre
un MySQL réel) : la table interne de suivi des migrations était créée en SQL SQLite brut, hors du
Grammar — corrigé pour passer par `Schema`/`Blueprint` comme n'importe quelle migration applicative.

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
./bin/niang queue:failed             # liste les jobs qui ont épuisé leurs tentatives
./bin/niang queue:retry <id>         # remet un job échoué en file
./bin/niang queue:flush              # supprime définitivement tous les jobs échoués
./bin/niang cache:clear              # vide le cache applicatif
./bin/niang optimize                 # cache les routes + rappels de prod
./bin/niang new mon-app              # crée un nouveau projet à partir de ce squelette
./bin/niang np:install               # installe le raccourci global `np` (macOS/Linux)
```

### Raccourci `np` (optionnel)

Pour taper `np serve` au lieu de `./bin/niang serve`, installez une fois le raccourci global
(macOS/Linux) :

```bash
./bin/niang np:install
```

La commande dépose un script `np` dans un dossier déjà présent dans votre `PATH` (auto-détecté, par
exemple `~/.local/bin`, `/opt/homebrew/bin` ou `/usr/local/bin`) qui remonte l'arborescence depuis le
dossier courant pour retrouver `bin/niang`. Résultat : `np` fonctionne dans **n'importe quel** projet
NiangPro sur la machine, même depuis un sous-dossier, sans rien reconfigurer par projet. Si aucun
dossier de votre `PATH` n'est accessible en écriture, la commande vous indique comment en créer un.

## Configuration

Fichiers `config/*.php`, chargés automatiquement au démarrage : `app.php` (nom, debug, providers),
`security.php` (en-têtes HTTP appliqués à toutes les réponses), `session.php` (durée de vie et
drapeaux du cookie de session).

```php
// config/app.php
return ['name' => env('APP_NAME', 'NiangPro')];

config('app.name'); // notation pointée, avec valeur par défaut : config('app.name', 'Défaut')
```

### Cache de configuration (production)

```bash
./bin/niang config:cache   # fige config/*.php (et les env() qu'ils contiennent) dans un seul fichier
./bin/niang config:clear   # supprime le cache
```

Une fois caché, modifier `.env` ou `config/*.php` n'a plus d'effet tant que le cache n'est pas vidé —
sûr uniquement parce qu'il est explicitement optionnel : ne l'activez qu'en production
(`./bin/niang optimize` le fait pour vous, avec le cache de routes).

## Sécurité

- **En-têtes HTTP** (CSP, HSTS, X-Frame-Options...) appliqués à toutes les réponses par défaut,
  configurables dans `config/security.php` — pas besoin de toucher `Application`.
- **Cookie de session** : `HttpOnly` toujours actif, `SameSite` et durée de vie configurables
  (`config/session.php`), `Secure` détecté automatiquement selon HTTPS (ou forcé via
  `SESSION_SECURE_COOKIE` en `.env`).
- **CSRF** : comparaison à temps constant (`hash_equals`), voir la section CSRF plus haut.
- **Mots de passe** : Argon2id (ou bcrypt si indisponible), voir `Hash::make()`.
- **SQL** : toutes les requêtes de l'ORM et du Query Builder passent par des requêtes préparées PDO.
- **Régénération de session** : `Auth::login()`/`Auth::logout()` appellent `Session::regenerate()`
  automatiquement (protection contre la fixation de session).

## Conteneur (Container)

Auto-wiring par Reflection, sans configuration : type-hintez une dépendance dans un constructeur ou
une méthode de contrôleur, elle est résolue automatiquement (et récursivement).

```php
$container->bind(PaymentGateway::class, fn ($c) => new StripeGateway(env('STRIPE_KEY')));
$container->singleton(Clock::class, new SystemClock());
```

Erreurs explicites plutôt qu'un plantage silencieux ou un débordement de pile :

```text
Impossible de résoudre [App\Nope] : cette classe n'existe pas.
Impossible de résoudre [App\PaymentGateway] : ce n'est pas une classe instanciable
  (interface ou classe abstraite ?). Enregistrez un binding avec $container->bind(...).
Dépendance circulaire détectée : A -> B -> A
Paramètre manquant : $name (type string) (paramètre de PostController::store()) —
  aucune valeur fournie et pas de valeur par défaut.
```

### PSR-11 et PSR-3

`Container` implémente `Psr\Container\ContainerInterface` (`get()`/`has()`, alias standard de
`make()`) et `ContainerException` implémente `ContainerExceptionInterface`/`NotFoundExceptionInterface`
— du code tiers compatible PSR-11 (ou tapé contre l'interface plutôt que la classe concrète)
fonctionne sans adaptation.

`Niang\Core\Logger` implémente `Psr\Log\LoggerInterface` et délègue à `Niang\Core\Log` (la façade
statique utilisée ailleurs dans le framework — même fichiers de sortie) :

```php
class ReportGenerator
{
    public function __construct(private \Psr\Log\LoggerInterface $logger)
    {
    }
}

// résolu automatiquement par le container ($container->singleton(LoggerInterface::class, ...)
// est déjà enregistré par Application) :
$container->make(ReportGenerator::class);
```

Seules deux dépendances existent dans `composer.json` : `psr/container` et `psr/log`, deux paquets
d'interfaces pures (aucun code d'implémentation, aucune dépendance transitive) — le framework reste
sans dépendance d'implémentation à l'exécution.

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

Depuis un projet existant, `./bin/niang new mon-app` clone ce squelette (sans `vendor/`, `.git/`, données
locales), installe les dépendances et génère une nouvelle `APP_KEY`. Sans projet existant sous la main,
utilisez plutôt `composer create-project niangpro/framework mon-app` (voir [Installation](#installation)).

## Dépôt public

Le code est sur GitHub : **https://github.com/NiangPro/niangpro** (public, CI activée sur chaque push).

## Packagist

Le package est publié : **[packagist.org/packages/niangpro/framework](https://packagist.org/packages/niangpro/framework)**.
`composer create-project niangpro/framework mon-app` et `composer require niangpro/framework`
fonctionnent pour tout le monde.

Pour publier une nouvelle version : créez un tag (`git tag v1.1.0 && git push --tags`) — le webhook
GitHub → Packagist (configuré une fois pour toutes) met à jour le package automatiquement à chaque push.
Si le webhook n'est pas configuré : GitHub → repo → **Settings → Webhooks → Add webhook**, avec comme
Payload URL `https://packagist.org/api/github?username=VOTRE_PSEUDO_PACKAGIST`, content type
`application/json`, et comme secret votre [token API Packagist](https://packagist.org/profile/).

## Philosophie

- Aucune dépendance externe **à l'exécution** (PHPUnit/PHP-CS-Fixer/PHPStan sont en `require-dev`,
  jamais livrés en production).
- Un seul fichier par responsabilité (Router, Request, Response, Container, View, DB).
- Injection de dépendances automatique via Reflection, sans configuration.
- Pas de compilation de templates, pas de cache de config à vider en développement : vous modifiez,
  vous rafraîchissez.
