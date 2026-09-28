# Contribuer à NiangPro

Merci de votre intérêt ! NiangPro reste volontairement petit : avant d'ajouter une fonctionnalité,
relisez la [règle stratégique](docs/ROADMAP_TECHNIQUE.md#78-règle-stratégique-essentielle) de la
roadmap (indispensable au cœur ? faisable sans dépendance ? standard PHP existant ? testable ?).

Une faille de sécurité ne se signale pas dans une issue : voir [SECURITY.md](SECURITY.md).

## Installation

```bash
git clone https://github.com/NiangPro/niangpro.git
cd niangpro
composer install
cp .env.example .env
./bin/niang key:generate
./bin/niang serve
```

PHP 8.1 minimum, extensions `pdo_sqlite`, `mbstring` et `fileinfo`. Aucune base à installer pour les
tests : ils tournent sur SQLite en mémoire (`.env.testing`).

## Avant d'ouvrir une pull request

Les mêmes vérifications que la CI :

```bash
composer test       # PHPUnit : Unit, Feature et Database (dont les suites des 6 thèmes)
composer lint       # style PSR-12 (php-cs-fixer) ; composer lint:fix corrige
composer analyse    # PHPStan
```

La CI mesure aussi la couverture de code : un seuil global et un seuil par composant critique
(`tools/coverage-check.php`). En local, avec l'extension `pcov` ou `xdebug` :

```bash
vendor/bin/phpunit --coverage-clover build/clover.xml && php tools/coverage-check.php build/clover.xml
```

Suite Database sur un vrai MySQL ou PostgreSQL (la CI le fait pour vous, mais c'est utile si vous
touchez aux migrations ou au Query Builder) :

```bash
DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_DATABASE=niangpro_test DB_USERNAME=root DB_PASSWORD= \
    vendor/bin/phpunit --testsuite=Database
```

## Ce qu'on attend d'une contribution

- **Des tests** qui échouent sans votre changement. Un bug corrigé sans test reviendra.
- **Aucune nouvelle dépendance à l'exécution** dans `composer.json` `require` (seules les interfaces
  PSR y figurent). Une dépendance de développement se discute dans l'issue d'abord.
- **Rétrocompatibilité** : une API publique ne change pas de signature dans une version mineure ;
  ajoutez un paramètre optionnel ou une nouvelle méthode. Une suppression passe par `@deprecated`
  pendant une version (voir la roadmap, « Deprecation policy »).
- **La documentation** : le `README.md` pour l'essentiel, une entrée dans `CHANGELOG.md` sous
  `[Non publié]` (sections `Added`, `Changed`, `Fixed`, `Security`...). Le site de documentation
  ([NiangPro/niangpro-docs](https://github.com/NiangPro/niangpro-docs)) est mis à jour en français et
  en anglais.
- **Un sujet par pull request** : plus facile à relire et à annuler si besoin.

## Messages de commit

Format [Conventional Commits](https://www.conventionalcommits.org/fr/), en français, à l'impératif ou
au nominal, avec le domaine entre parenthèses :

```
feat(mail): copies (cc, bcc), pièces jointes, envoi par la file
fix(db): booléens sous PostgreSQL (valeurs par défaut et cast bool)
security(db): valide les identifiants de colonne/table dans QueryBuilder
test(themes): ...   docs: ...   ci: ...   perf(router): ...   style(cli): ...
```

Le corps du message explique **pourquoi**, pas seulement quoi.

## Issues

- **Bug** : version de NiangPro et de PHP, système, étapes pour reproduire, résultat attendu et obtenu.
- **Fonctionnalité** : le besoin réel d'abord, la solution ensuite. Précisez si elle pourrait vivre
  dans un paquet séparé plutôt que dans le cœur.

Les modèles de `.github/ISSUE_TEMPLATE/` guident la saisie.

## Structure

```
src/Core/          le framework (Niang\Core)
app/, routes/      l'application de démonstration, copiée dans chaque nouveau projet
resources/scaffold les thèmes de site proposés par create-project
tests/Unit         classes isolées        tests/Feature   requêtes HTTP simulées
tests/Database     SQL réellement exécuté (SQLite, MySQL, PostgreSQL en CI)
```
