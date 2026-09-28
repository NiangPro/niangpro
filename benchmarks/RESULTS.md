# Résultats des benchmarks

Généré par `php benchmarks/run.php --markdown`. Ce sont des micro-benchmarks : ils mesurent le coût
de chaque couche du framework face à son équivalent en PHP natif, pas les performances d'une application
réelle (qui dépendent surtout de ses requêtes SQL et de ses appels externes).

## Conditions

- **PHP** : 8.4.17
- **OPcache (CLI)** : activé
- **JIT** : disable
- **Processeur** : Apple M1
- **Mémoire** : 8 Go
- **Système** : Darwin 25.6.0
- **Base** : SQLite en mémoire, 500 lignes
- **Itérations** : 3000 par scénario, après échauffement
- **Date** : 2026-09-27

## Mesures

| Scénario | Mesure | p50 (ms) | p95 (ms) | p99 (ms) | Opérations/s | Mémoire retenue après la série (Ko) |
| --- | --- | ---: | ---: | ---: | ---: | ---: |
| Routage (200 routes) | PHP natif (boucle naïve de preg_match) | 0.0639 | 0.0650 | 0.0683 | 15 614 | 0.0 |
| Routage (200 routes) | NiangPro Router::dispatch() | 0.0094 | 0.0096 | 0.0099 | 106 565 | 0.0 |
| Conteneur | PHP natif (new) | 0.0008 | 0.0008 | 0.0009 | 1 232 680 | 0.0 |
| Conteneur | NiangPro Container::make() (auto-wiring) | 0.0092 | 0.0094 | 0.0095 | 108 604 | 0.0 |
| Requête et réponse | PHP natif (json_encode) | 0.0004 | 0.0005 | 0.0005 | 2 479 265 | 0.0 |
| Requête et réponse | NiangPro Request::create() + Response::json() | 0.0027 | 0.0028 | 0.0029 | 365 693 | 0.0 |
| Base de données (SQLite) | PDO natif (requête préparée) | 0.0227 | 0.0232 | 0.0240 | 43 976 | 0.0 |
| Base de données (SQLite) | NiangPro QueryBuilder | 0.0287 | 0.0290 | 0.0297 | 34 788 | 0.0 |
| Rendu | PHP natif (include + ob_start) | 0.0008 | 0.0008 | 0.0008 | 1 274 427 | 0.0 |
| Rendu | NiangPro View::make() (page d'erreur 404) | 0.0209 | 0.0213 | 0.0218 | 47 691 | 0.0 |
| Requête complète | NiangPro Application::handle() (GET JSON) | 0.0147 | 0.0151 | 0.0155 | 67 600 | 0.0 |
| Démarrage | new Application() (config, conteneur, providers) | 0.0748 | 0.0764 | 0.0798 | 13 330 | 436.5 |

## Lecture

- **Requête complète** : environ 0,015 ms de framework par requête (routage, middlewares, en-têtes de
  sécurité, réponse JSON), hors démarrage. Avec le démarrage (`new Application()`, environ 0,08 ms), le
  coût du framework reste sous 0,1 ms : une seule requête SQL en coûte déjà 0,02 à 0,03.
- **Routage** : NiangPro est plus rapide qu'une boucle naïve de `preg_match` sur 200 routes, grâce à son
  index par premier segment d'URL (voir CHANGELOG, quinzième jalon). Ce n'est pas une comparaison avec un
  routeur compilé.
- **Conteneur** : l'auto-wiring par réflexion coûte environ 0,01 ms pour trois classes ; enregistrez un
  `singleton()` pour un service utilisé souvent.
- **Mémoire** : aucune croissance sur les séries, sauf `new Application()` : chaque instance ré-enregistre
  les écouteurs de `AppServiceProvider`, statiques. Sans effet en production (un process par requête avec
  PHP-FPM) ; les tests les vident (`Event::reset()`).

## Non mesuré

La comparaison avec Slim, Laravel et Symfony demandée par la roadmap n'est pas faite : elle suppose
d'installer ces frameworks et de mesurer une même application servie par un vrai serveur HTTP (débit
sous charge, avec `wrk` ou `ab`), pas des micro-benchmarks en ligne de commande.
