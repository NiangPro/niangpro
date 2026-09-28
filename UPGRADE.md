# Passer de NiangPro 1.x à 2.0

En 1.x, `composer create-project niangpro/framework` copiait le framework dans votre projet (`src/Core/`) :
un correctif du framework ne vous parvenait pas. En 2.0, le framework est une dépendance
(`niangpro/framework`, dans `vendor/`) et se met à jour avec `composer update`. Votre code (`app/`, `routes/`,
`resources/`, `config/`, `database/`, `lang/`, `public/`) ne bouge pas.

## 1. Mettre de côté vos modifications du framework

Si vous avez modifié des fichiers de `src/` (déconseillé, mais possible en 1.x), elles seront perdues.
Comparez avec la version d'origine, par exemple :

```bash
git diff v1.5.0 -- src/          # si votre projet est un dépôt git créé depuis 1.5.0
```

Reportez-les dans votre application (Service Provider, middleware, classe dans `app/`) ou proposez-les au
framework.

## 2. `composer.json`

```diff
 {
-    "name": "niangpro/framework",
+    "name": "mon-organisation/mon-app",
     "type": "project",
     "require": {
         "php": ">=8.1",
-        "psr/container": "^2.0",
-        "psr/log": "^3.0",
-        "psr/http-message": "^1.1 || ^2.0",
-        "psr/http-server-middleware": "^1.0"
+        "niangpro/framework": "^2.0"
     },
     "autoload": {
         "psr-4": {
-            "Niang\\": "src/",
             "App\\": "app/"
-        },
-        "files": [
-            "src/helpers.php"
-        ]
+        }
     },
     "scripts": {
-        "post-create-project-cmd": "Niang\\Core\\Console\\ComposerHooks::postCreateProject"
     }
 }
```

## 3. Supprimer la copie du framework et installer

```bash
rm -rf src/
composer update
./bin/niang doctor
./vendor/bin/phpunit
```

`bin/niang`, `public/index.php` et `preload.php` fonctionnent sans modification (namespaces inchangés :
`Niang\Core\...`). Pour `preload.php`, reprenez la version du squelette (`niangpro/niangpro`), qui cherche les
paquets dans `vendor/niangpro/framework`.

Les dossiers `tests/Unit/` et `tests/Database/` d'un projet 1.x sont les tests du framework lui-même, copiés
avec lui : ils lisent `src/` et vérifient des détails internes qui ont changé. Supprimez-les (le framework
est testé dans son propre dépôt) et gardez vos tests, en général dans `tests/Feature/` :

```bash
rm -rf tests/Unit tests/Database
```

puis retirez les suites `Unit` et `Database` de `phpunit.xml` (ou reprenez celui du squelette
`niangpro/niangpro`, qui ne déclare que `Feature` et `Security`).

## 4. Mettre à jour le code de démonstration

L'application de démonstration livrée en 1.x (contrôleurs d'authentification, middlewares, modèles, vues,
migrations) a évolué en 2.0 : double authentification, `$fillable`, nouvelles tables... Récupérez le squelette
2.0 à côté de votre projet pour comparer :

```bash
composer create-project niangpro/niangpro /tmp/niangpro-2 --no-install --no-scripts
diff -rq /tmp/niangpro-2/app app
```

- un fichier de démonstration que vous n'avez **pas** modifié : remplacez-le par celui du squelette ;
- un fichier que vous avez modifié : reportez-y les changements du squelette ;
- `database/migrations/` : copiez les migrations du squelette qui vous manquent, puis `./bin/niang migrate` ;
- `config/`, `lang/`, `resources/views/errors/` : même principe (les nouvelles clés de configuration ont des
  valeurs par défaut ; un fichier absent ne bloque rien) ;
- `tests/Feature/` : les tests de la démonstration suivent son code (par exemple, supprimer un article exige
  désormais le jeton CSRF) — reprenez ceux du squelette pour le code que vous avez repris.

## 5. Changements de comportement à vérifier

- **`$fillable` obligatoire** : `Model::create()` / `update()` ne gardent que les colonnes de
  `protected static array $fillable`, et lèvent `MassAssignmentException` s'il n'est pas déclaré. Pour du
  code de confiance (seeders, calculs internes) : `forceCreate()` / `forceUpdate()`. Les modèles de
  démonstration d'un projet 1.x en ont besoin, par exemple :

  ```php
  // app/Models/User.php — jamais role ni email_verified_at : un visiteur pourrait les envoyer
  protected static array $fillable = ['name', 'email', 'password'];

  // app/Models/Post.php
  protected static array $fillable = ['title', 'body'];
  ```

  Une colonne qui désigne un propriétaire (`user_id`, `tenant_id`, `role`...) n'y figure jamais : renseignez-la
  dans le code (`Post::forceCreate($data + ['user_id' => Auth::id()])`), sans quoi un visiteur pourrait la
  choisir.
- **`$timestamps` vaut `true` par défaut** : un modèle dont la table n'a pas `created_at` / `updated_at` doit
  déclarer `protected static bool $timestamps = false;`.
- **`Gate::allows()` sur une ability sans règle ni Policy** consulte les permissions du rôle de l'utilisateur
  (`config/permissions.php`) au lieu de répondre toujours `false`.
- **Erreurs d'API en JSON** : sous `/api` (`app.api_prefix`), une 404, 405 ou 422 répond en JSON même sans en-tête
  `Accept`.
- **Curseurs de pagination signés** : un curseur émis par la 1.x n'est plus accepté (la pagination repart du
  début).
- **Mode strict dans le framework** (`declare(strict_types=1)`) : passer un entier là où le framework attend
  une chaîne (ou l'inverse) lève désormais une `TypeError` au lieu d'une conversion silencieuse.
- **`niang new`** lance `composer create-project niangpro/niangpro` (réseau nécessaire).

Le détail de chaque changement est dans le [CHANGELOG](CHANGELOG.md).
