# Changelog

Toutes les évolutions notables de NiangPro sont documentées ici.

Le format suit [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/), le versionnage suit
[Semantic Versioning](https://semver.org/lang/fr/).

## [Non publié]

### Added

- **Debug toolbar** (P0 #15, NiangPro 2.0 — DX) : avec `APP_DEBUG=true` (défaut en développement),
  `Niang\Core\DebugToolbar` ajoute en bas de chaque page HTML le temps de réponse, le nombre de
  requêtes SQL exécutées (`DB::queryCount()`, désormais remis à zéro automatiquement au début de
  chaque requête par `Application::handle()`, plutôt que seulement via un appel manuel en test),
  le pic mémoire et le code de statut. Jamais injectée sur une réponse JSON, ni sans balise
  `</body>` où s'accrocher, ni si `APP_DEBUG=false` — jamais en production avec la config par
  défaut. Composé avec la compression gzip existante sans la casser (injection avant compression).
  9 nouveaux tests (`tests/Unit/DebugToolbarTest.php`, `tests/Feature/DebugToolbarTest.php`).
- **Authorization Policies** (P0 #15, NiangPro 2.0 — premier jalon) : `Gate::policy($prefix,
  $policyClass)` enregistre une classe pour plusieurs règles autour d'un même modèle
  (`'post.delete'` résout vers `PostPolicy::delete(Auth::user(), $post)`), en complément de
  `Gate::define()` (toujours prioritaire en cas de conflit de nom) qui reste la solution la plus
  simple pour une règle isolée. Les enregistrements restant de simples tableaux (pas d'objets), la
  policy se choisit par le préfixe explicite de l'ability, pas par inspection d'un type PHP qui
  n'existe pas.
  - `app/Policies/PostPolicy.php` remplace l'ancien `Gate::define('delete-post', ...)` dans
    `routes/web.php` — même règle, organisée différemment.
  - 7 nouveaux tests (`tests/Unit/GateTest.php`) et `tests/Feature/AuthorizationTest.php`, qui
    couvre pour la première fois `DELETE /posts/{id}` (invité redirigé, utilisateur connecté
    autorisé) — jusqu'ici sans aucun test malgré `Controller::authorize()` déjà en place depuis
    v0.4.0.

## [1.1.0] — 2026-09-17

### Added

- **Queue 2.0, Storage, Mail** (P0 #13 de la roadmap technique — périmètre volontairement sans
  Redis ni SMTP réel : les deux demanderaient une dépendance ou une extension externe, contraire
  au principe « sans dépendance d'implémentation à l'exécution » du framework, et un client SMTP
  écrit à la main n'aurait pas pu être vérifié contre un vrai serveur dans cet environnement) :
  - `Queue` : les jobs échoués sont désormais retentés jusqu'à `Job::$tries` fois (défaut 1) avec
    un backoff exponentiel (10s, 20s, 40s...), puis déplacés vers les jobs échoués plutôt que
    perdus silencieusement. `Queue::later($delaySeconds, $job)` pour différer un job. Nouveau :
    `Queue::failed()`, `Queue::retry($id)`, `Queue::flush()`, et les commandes
    `queue:failed`/`queue:retry`/`queue:flush`.
  - `Niang\Core\Storage` — disque local (`storage/app/`) : `put()`/`get()`/`exists()`/`delete()`/
    `size()`/`url()`. Les chemins contenant `..` sont rejetés (`InvalidArgumentException`) pour
    empêcher une traversée de répertoire à partir d'une entrée utilisateur.
  - `Niang\Core\Mail`/`Mailable`/`PendingMail` — `Mail::to($email)->send(new WelcomeMailable(...))`.
    Deux drivers pilotés par `MAIL_MAILER` : `log` (défaut, écrit dans `storage/logs/`, pratique en
    dev) et `array`/`Mail::fake()` (garde les emails en mémoire pour `Mail::sent()` dans les tests).
  - 24 nouveaux tests (`tests/Unit/QueueTest.php`, `tests/Unit/StorageTest.php`,
    `tests/Unit/MailTest.php`).
- **API layer : JSON Resources & CORS** (P0 #12 de la roadmap technique) :
  - `Niang\Core\Http\JsonResource` — enveloppe un enregistrement dans `{"data": ...}`, à surcharger
    (`toArray()`) pour choisir les champs exposés plutôt que de renvoyer l'enregistrement brut.
    `Resource::collection($items)` accepte un tableau ou un `Paginator` et ajoute `meta`
    (`current_page`/`last_page`/`per_page`/`total`) et `links` (`prev`/`next`) dans ce second cas.
    Pas de JSON:API complet : juste `data`/`meta`/`links`, la partie utile sans la complexité de
    la spec entière.
  - CORS configurable dans `config/cors.php` (`allowed_origins`, `allowed_methods`,
    `allowed_headers`, `exposed_headers`, `supports_credentials`, `max_age`) et appliqué via
    `App\Middleware\HandleCors`, qui répond directement au préflight `OPTIONS` (204, sans exécuter
    la route) et ajoute les en-têtes `Access-Control-*` à la vraie réponse. Avec
    `supports_credentials: true`, l'origine exacte est toujours reflétée (jamais `*`, que les
    navigateurs rejettent dans ce cas).
  - Démo câblée : `GET /api/posts` (`PostController::apiIndex()` + `App\Resources\PostResource`)
    sous le groupe `/api` avec `HandleCors`.
  - 12 nouveaux tests : logique CORS pure (`tests/Unit/CorsTest.php`), exécution via le middleware
    et le vrai routeur (`tests/Feature/CorsTest.php`), et la forme `data`/`meta`/`links`
    (`tests/Feature/JsonResourceTest.php`).
- **PSR-11 et PSR-3** (P0 #11 de la roadmap technique) :
  - `Container` implémente désormais `Psr\Container\ContainerInterface` (`get()` — alias de
    `make()` qui lève `ContainerNotFoundException` plutôt que la `ContainerException` générique
    quand l'identifiant n'est résoluble d'aucune façon — et `has()`). `ContainerException`
    implémente `ContainerExceptionInterface`.
  - Nouveau `Niang\Core\Logger`, implémentation `Psr\Log\LoggerInterface` qui délègue à
    `Niang\Core\Log` (même fichiers de sortie) — utilisable via injection de dépendances
    (`LoggerInterface $logger` dans un constructeur) plutôt que la façade statique, notamment pour
    interopérer avec du code tiers compatible PSR-3. `Application` enregistre `Container::class`/
    `ContainerInterface::class` et `Logger::class`/`LoggerInterface::class` comme singletons au
    démarrage.
  - Deux dépendances ajoutées à `composer.json` : `psr/container` et `psr/log` — deux paquets
    d'interfaces pures, sans code d'implémentation ni dépendance transitive. Le framework reste
    sans dépendance d'implémentation à l'exécution ; seules des interfaces standard sont ajoutées,
    pour l'interopérabilité.
  - 9 nouveaux tests (`tests/Unit/ContainerTest.php`, nouveau `tests/Unit/LoggerTest.php`).
- **ORM / pagination / relations** (P0 #10 de la roadmap technique) :
  - `QueryBuilder` : `distinct()`, `whereNull()`/`whereNotNull()`, `whereBetween()`/`whereNotBetween()`,
    `whereDate()`, `whereColumn()` (comparaison entre deux colonnes), `having()`/`havingRaw()`,
    `exists()`, `firstOrFail()` (lève `NotFoundException`), et les agrégats `sum()`/`avg()`/`min()`/
    `max()` ainsi que `count(string $column = '*')`.
  - `Model::findOrFail()` — équivalent de `find()` qui lève `NotFoundException` au lieu de renvoyer
    `null`.
  - **Eager loading** : `Model::with('relation')`/`with(['a', 'b'])->get()` (aussi `first()` et
    `paginate()`), qui charge chaque relation déclarée en une seule requête pour toute la collection
    via `whereIn`/jointure, au lieu d'une requête par enregistrement. Les relations disponibles se
    déclarent dans la classe fille via `eagerLoadable()`, en s'appuyant sur trois nouvelles méthodes
    protégées de `Model` : `loadMany()` (hasMany), `loadOne()` (belongsTo), `loadManyToMany()`
    (belongsToMany via pivot) — toutes basées sur des tableaux associatifs, pas d'objets hydratés,
    cohérent avec le choix d'architecture Active-Record-lite du framework.
  - `DB::queryCount()`/`DB::resetQueryCount()` — compteur de requêtes exécutées, ajouté spécifiquement
    pour *prouver* par un test qu'une correction N+1 fonctionne (plutôt que de le supposer).
  - 21 nouveaux tests : génération SQL pure (`tests/Unit/Database/QueryBuilderTest.php`), exécution
    réelle des nouvelles clauses/agrégats (`tests/Database/QueryBuilderExecutionTest.php`), et surtout
    `tests/Database/EagerLoadingTest.php` qui vérifie que `DB::queryCount()` reste constant (2 ou 3
    requêtes) quel que soit le nombre d'enregistrements chargés avec `with()`.
- **Validation 2.0** (P0 #9 de la roadmap technique) :
  - Nouvelles règles : `nullable`, `boolean`, `array`, `url`, `date`, `date_format`, `between`,
    `in`/`not_in`, `same`/`different`, `required_if`/`required_with`/`required_without`, et
    `unique`/`exists` (contre la base, `unique` accepte un id à ignorer pour les mises à jour).
  - Validation d'un tableau élément par élément : `'items.*.name' => 'required|string'`.
  - Messages et noms de champs personnalisables : `Validator::make($data, $rules, $messages,
    $attributes)`, `Controller::validate(..., messages: [...], attributes: [...])`, et
    `FormRequest::messages()`/`attributes()` (prévus par l'architecture FormRequest de la roadmap
    mais pas encore câblés jusqu'ici).
  - `AuthController::register()` simplifié : la vérification manuelle d'email déjà pris est
    remplacée par `'email' => 'unique:users,email'` — moins de code, même comportement (vérifié
    en direct : inscription en double toujours rejetée, avec le message personnalisé attendu).
  - 22 nouveaux tests (règles pures dans `tests/Unit/ValidatorTest.php`, `unique`/`exists` contre
    une vraie table dans `tests/Database/ValidatorDatabaseRulesTest.php`).
- **Router 2.0** (P0 #8 de la roadmap technique) :
  - `$router->match(['GET', 'POST'], $uri, $action)` — une seule action pour plusieurs méthodes ;
    `->name()`/`->where()` s'appliquent à toutes les routes enregistrées.
  - `$router->fallback($action)` — route de secours quand rien ne correspond, prioritaire sur la
    404 par défaut mais pas sur une 405 (une route existe, juste pas pour cette méthode). Survit à
    `route:cache` si l'action n'est pas une closure, comme les routes normales.
  - `$router->options(...)` et `$router->head(...)` pour un enregistrement explicite.
  - **`HEAD` fonctionne désormais automatiquement sur toute route `GET`** (corps vidé, en-têtes et
    statut conservés) sans rien déclarer — une route `HEAD` explicite garde, elle, entièrement la
    main sur sa réponse (testé : les deux comportements coexistent correctement).
  - `route:list` affiche aussi le fallback s'il y en a un.
- **Contrats du Container** (P0 #7 de la roadmap technique) : `Niang\Core\Exceptions\ContainerException`
  remplace les `\RuntimeException` génériques, avec des messages explicites et actionnables :
  - Classe introuvable, ou binding manquant sur une interface/classe abstraite (avec un rappel de
    la syntaxe `$container->bind(...)` à utiliser).
  - **Dépendances circulaires détectées** (`A -> B -> A`) au lieu de provoquer un débordement de
    pile — c'était un vrai trou avant : `make()` maintient maintenant une pile de résolution.
  - Paramètre scalaire manquant : nom, type et méthode/constructeur concerné dans le message.
  - `tests/Unit/ContainerTest.php` couvre les quatre cas (avec des classes de test dédiées pour la
    dépendance circulaire, vérifiant qu'elle ne fait plus planter le process).
- **CI multi-versions et multi-SGBD** (P0 #6 de la roadmap technique) :
  - Job `test` en matrice sur PHP 8.1, 8.2, 8.3 et 8.4.
  - Jobs `mysql` (MySQL 8) et `postgres` (PostgreSQL 16, conteneurs de service GitHub Actions) qui
    font vraiment tourner les migrations (`tests/Database/`) contre ces moteurs, pas seulement SQLite.

### Fixed

- **Les paramètres liés via `DB::select()`/`selectOne()`/`statement()` étaient tous transmis comme
  chaînes** (`PDOStatement::execute($bindings)` lie systématiquement en `PDO::PARAM_STR`, quel que
  soit le type PHP réel). Sans colonne pour absorber la conversion par affinité — typiquement une
  expression agrégée dans une clause `HAVING`, ex. `HAVING SUM(views) > ?` — SQLite compare alors un
  INTEGER à un TEXTE et le classe toujours avant, si bien qu'un `>` numériquement correct ne
  renvoyait aucune ligne. Trouvé en écrivant `QueryBuilderExecutionTest::test_having` (silencieux sur
  MySQL/PostgreSQL, qui font la conversion). Corrigé en liant chaque valeur explicitement avec
  `bindValue()` et le type `PDO::PARAM_*` correspondant à son type PHP, plutôt que via `execute()`.
- **`PostController::index()`/`page()` interrogeaient chaque post individuellement pour ses
  commentaires et ses tags** (`Post::comments($id)`/`Post::tags($id)` dans une boucle), soit 2N+1
  requêtes pour N posts. Remplacé par `Post::with(['comments', 'tags'])->get()`, qui ramène le total
  à 3 requêtes constantes quel que soit N — corrigé et prouvé par `EagerLoadingTest`, pas seulement
  supposé.
- **Bug réel trouvé en testant contre un vrai MySQL local avant de pousser en CI** :
  `Migrator::ensureTable()` créait la table interne `migrations` en SQL SQLite brut
  (`INTEGER PRIMARY KEY AUTOINCREMENT`), en contournant complètement le Grammar — échouait sur
  MySQL/PostgreSQL. Corrigé pour passer par `Schema`/`Blueprint` comme toute migration applicative.
- **`Env::get()` donnait la priorité au fichier `.env*` sur une variable d'environnement réelle** —
  à l'inverse de la convention habituelle (CI, Docker, hébergeurs injectent leurs propres variables,
  qui doivent gagner). Corrigé : une variable déjà présente dans l'environnement n'est plus jamais
  écrasée par `.env`/`.env.testing`. C'est ce qui permet à la CI de piloter `DB_CONNECTION` par job.
- **Le DSN PDO générique incluait `charset=` pour tous les moteurs non-SQLite**, y compris
  PostgreSQL — `PDO_PGSQL` ne reconnaît pas ce paramètre et refusait la connexion
  (`invalid connection option "charset"`). `charset` n'est plus ajouté qu'en MySQL ; le port par
  défaut est aussi désormais correct par moteur (3306 MySQL / 5432 PostgreSQL).
- **`composer.lock` verrouillait des outils dev (PHPUnit 11, PHP-CS-Fixer avec Symfony 8.1)
  nécessitant PHP 8.4+ en interne**, cassant `composer install` sur PHP 8.1/8.2/8.3 en CI — la
  contrainte `"php": ">=8.1"` de `composer.json` n'empêche pas Composer de verrouiller, lors d'un
  `composer update` local, des versions qui ne marchent que sur le PHP effectivement utilisé à ce
  moment-là. Corrigé avec `config.platform.php` fixé à `8.1.0` (force Composer à résoudre pour le
  minimum supporté, pas la version locale du contributeur) et un passage à PHPUnit 10 (dernière
  branche majeure compatible PHP 8.1).

- **Configuration & sécurité renforcée** (P0 #4 et #5 de la roadmap technique) :
  - `config/security.php` : en-têtes HTTP (CSP, HSTS, X-Frame-Options...) appliqués à toutes les
    réponses, désormais éditables sans toucher `Application`.
  - `config/session.php` : durée de vie du cookie de session, `SameSite`, `Secure` (auto-détecté
    selon HTTPS, ou forcé via `SESSION_SECURE_COOKIE`).
  - **Le cookie de session est maintenant réellement sécurisé** : `Session::start()` appelait
    `session_start()` sans configurer `HttpOnly`/`Secure`/`SameSite` — c'était une vraie lacune,
    corrigée via `session_set_cookie_params()`.
  - `Cookie::set()` porte désormais aussi le drapeau `Secure`.
  - `Niang\Core\ConfigCache` + `niang config:cache` / `config:clear` : fige `config/*.php` (et les
    `env()` qu'ils contiennent) dans un seul fichier pour la production — vérifié qu'il est bien
    pris en compte (en modifiant une valeur pendant que le cache est actif) et qu'il se vide
    correctement.
  - `niang optimize` met aussi en cache la configuration (en plus des routes).

- **Gestion des erreurs centralisée** (`Niang\Core\Exceptions`) : `Handler` unique appelé par
  `Application::handle()`, plus aucune logique de rendu d'erreur éparpillée dans le routeur, les
  middlewares ou les contrôleurs.
  - `HttpException` (+ statut, en-têtes personnalisés), `NotFoundException`,
    `AuthenticationException`, `DatabaseException`, `ConfigurationException`.
  - `abort(404)`, `abort(403, 'message personnalisé')`.
  - Réponse JSON automatique (`{"message": "..."}`) si le client l'attend, sinon page HTML dédiée
    (`resources/views/errors/{code}.php`) ou générique (`errors/generic.php`).
  - Page de debug enrichie en développement (exception, fichier:ligne, requête, route,
    utilisateur connecté, durée) ; jamais affichée si `APP_DEBUG=false`.
  - `\PDOException` toujours journalisée, jamais montrée telle quelle en production (SQL et
    chaîne de connexion jamais exposés).
  - `AuthorizationException` étend maintenant `HttpException` (403) ; `Router` lève
    `NotFoundException`/`HttpException(405)` au lieu de construire une réponse lui-même ;
    `VerifyCsrfToken` (419), `ThrottleRequests` (429) et `Authenticate` (401 JSON) font de même.

- `docs/ROADMAP_TECHNIQUE.md` : feuille de route technique complète (P0 à P3, jalons v1.1 → v2.0)
  adoptée comme référence pour la suite du développement.
- **Database Grammar** (`Niang\Core\Database\Grammar`) : couche de traduction SQL par moteur
  (`SQLiteGrammar`, `MySqlGrammar`, `PostgresGrammar`). Les migrations écrites avec `Blueprint`
  génèrent désormais du SQL correct pour SQLite, MySQL et PostgreSQL, sans changement d'API —
  `$table->id()`, `$table->string()`, etc. restent identiques quel que soit `DB_CONNECTION`.
- `Blueprint::decimal()`, `dateTime()`, `json()`.
- `Blueprint::unique()` et `index()` au niveau table (en plus du modificateur `->unique()` par colonne).
- `ColumnDefinition::constrained()` et `Blueprint::foreign()` : contraintes de clé étrangère
  (`references()->on()->cascadeOnDelete()`/`nullOnDelete()`/`restrictOnDelete()`).
- `Blueprint::renameColumn()` et `dropColumn()`, `Schema::rename()` (renommage de table).
- SQLite : activation de `PRAGMA foreign_keys = ON` à la connexion (désactivé par défaut par
  SQLite, contrairement à MySQL/PostgreSQL qui appliquent toujours les clés étrangères déclarées).
- `tests/Unit/Database/GrammarTest.php` (SQL généré par moteur) et `tests/Database/SchemaTest.php`
  (exécution réelle : création de table, contrainte FK appliquée, rename/drop de colonne).
- `np:install` : raccourci CLI global `np` (macOS/Linux) qui retrouve `bin/niang` depuis n'importe
  quel sous-dossier d'un projet NiangPro.
- **Isolation complète des tests** : `.env.testing` (committé) charge une base SQLite `:memory:` au
  lieu de `storage/database.sqlite` dès que `APP_ENV=testing` (positionné par `phpunit.xml`) ;
  `Niang\Core\Testing\TestCase` migre automatiquement cette base ; `tests/bootstrap.php` repart d'un
  `storage/framework/` propre à chaque run (le cache et le rate limiting sur fichier ne fuient plus
  d'une exécution de la suite à l'autre) ; nouveau trait `Niang\Core\Testing\RefreshDatabase`
  (transaction annulée après chaque test) pour les tests qui écrivent en base.
- `tests/Feature/AuthTest.php` démontre `RefreshDatabase` (un test vérifie explicitement qu'aucune
  trace du précédent ne subsiste).

### Changed

- `SchemaTest` étend désormais `Niang\Core\Testing\TestCase` (au lieu de `PHPUnit\Framework\TestCase`)
  pour charger `.env.testing` de façon fiable, indépendamment de l'ordre d'exécution des suites.
- CI : suppression de l'étape « Préparer l'environnement » (`.env` + `migrate`), devenue inutile —
  les tests s'auto-suffisent désormais.
- `phpstan.neon` analyse aussi `tests/` (niveau 6, toujours 0 erreur).

- `ColumnDefinition` et `Blueprint` ne construisent plus de SQL directement : ils décrivent la
  colonne de façon abstraite, et `Grammar::compile*()` traduit vers le SQL du moteur configuré.

**Écosystème (P0 #14)** : `composer create-project niangpro/framework` et `composer require
niangpro/framework` installaient encore le tag `v1.0.0` (2026-09-15), donc PHPUnit 11 (qui exige
PHP 8.3+) et aucune des fonctionnalités livrées depuis — vérifié en exécutant réellement la
commande, pas supposé. Les Service Providers et la publication Packagist existaient déjà et
fonctionnaient ; il manquait juste un nouveau tag. Cette version (1.1.0) comble cet écart.

## [1.0.0] — 2026-09-10

Première version publique. Voir la [release GitHub](https://github.com/NiangPro/niangpro/releases/tag/v1.0.0)
pour le détail complet (routeur, conteneur DI, Query Builder, migrations SQLite, auth, tests,
CI, etc.).
