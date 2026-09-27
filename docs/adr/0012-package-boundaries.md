# 0012 — Frontières des paquets avant la séparation (§46)

- **Statut** : accepté ; préparera le remplacement de 0008
- **Date** : 2026-09-27

## Contexte

La roadmap (§46) prévoit de séparer le framework en paquets (`niangpro/database`, `niangpro/redis`,
`niangpro/mail`...) pour que le cœur reste minimal et qu'un projet reçoive les correctifs par Composer. Une
analyse des 137 classes de `src/Core` montre que la plupart des références vont vers de vraies briques de base
(configuration, environnement, langues, exceptions, logs), mais que quelques-unes rendraient la séparation
impossible sans cycle : `Application` connaît tout, `Event` connaît la file d'attente, `Cache`, `Model` et
`Queue` connaissent le multi-locataire, `Log` connaît les traces.

## Décision

Les paquets et leurs dépendances autorisées sont écrits dans `tests/Unit/Architecture/packages.php` :

```text
core           conteneur, config, env, événements, logs, langues, chiffrement, exceptions, client HTTP sortant
database       → core
redis          → core
http           → core            (optionnels : database, redis — pilotes de session)
cache, queue   → core            (optionnels : database, redis — pilotes)
mail           → core, queue     (optionnels : database, auth)
storage        → core            (optionnel : http)
auth           → core, http, database
tenancy        → core, database, http
observability  → core, http, cache
openapi, debug → core, http
framework      Application, gestion des erreurs, CLI, outils de test : assemble tout
```

Une dépendance vers un paquet de plus haut niveau se remplace par un point d'extension du paquet de bas
niveau (par exemple : `Log` accepte des fournisseurs de contexte, et `Application` y branche `Trace`). Un type
générique (interface, exception) descend dans le paquet le plus bas qui en a besoin.

`PackageBoundariesTest` compte les dépendances interdites et les compare à `baseline.php` : le compte ne peut
que descendre. Les namespaces restent `Niang\Core\...` : la séparation physique (dossiers `packages/`,
dépôts Composer) n'interviendra qu'à zéro dépendance interdite, sans changement pour le code des applications.

## Conséquences

- 22 dépendances interdites au départ, supprimées en quatre étapes testées (reclassement de types génériques ;
  points d'extension de `Log`, `Http\Client` et `Event` ; de `Cache`, `Model`, `Metrics` et `Queue` ;
  résolveurs du `Container`). `PackageBoundariesTest` exige désormais zéro.
- Les points d'extension ajoutés servent aussi aux applications (contexte de log, portée des modèles...).
- Séparation physique faite : un dossier `packages/<nom>/` par paquet, avec son `composer.json`
  (`niangpro/<nom>`, dépendances NiangPro en `self.version` : versions synchronisées). Le `composer.json`
  racine charge tous les paquets (PSR-4 sur plusieurs dossiers) et les déclare en `replace`. Le paquet
  d'assemblage s'appelle `niangpro/foundation` (`niangpro/framework` reste le nom du projet racine).
- Publication de dépôts séparés : à venir.
