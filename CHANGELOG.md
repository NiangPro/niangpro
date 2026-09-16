# Changelog

Toutes les évolutions notables de NiangPro sont documentées ici.

Le format suit [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/), le versionnage suit
[Semantic Versioning](https://semver.org/lang/fr/).

## [Non publié]

### Added

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

## [1.0.0] — 2026-09-10

Première version publique. Voir la [release GitHub](https://github.com/NiangPro/niangpro/releases/tag/v1.0.0)
pour le détail complet (routeur, conteneur DI, Query Builder, migrations SQLite, auth, tests,
CI, etc.).
