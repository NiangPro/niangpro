# Changelog

Toutes les évolutions notables de NiangPro sont documentées ici.

Le format suit [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/), le versionnage suit
[Semantic Versioning](https://semver.org/lang/fr/).

## [Non publié]

### Added

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
