# 0013 — Le framework est une dépendance du projet (v2)

- **Statut** : accepté ; remplace 0008
- **Date** : 2026-09-27

## Contexte

Jusqu'en 1.x, `composer create-project niangpro/framework` copiait tout le dépôt dans le projet (ADR 0008) :
un correctif, même de sécurité, ne parvenait à une application que par une mise à jour manuelle de
`src/Core/`. Le framework est désormais découpé en 14 paquets sans dépendance circulaire (ADR 0012).

## Décision

Comme Laravel (`laravel/laravel` et `laravel/framework`) :

- **`niangpro/framework`** (ce dépôt) devient la bibliothèque : type `library`, les 14 paquets réunis
  (`replace`), une archive qui ne contient que `packages/` (`.gitattributes`). Il reste le seul lieu de
  développement, avec l'application de démonstration, qui sert aux tests.
- **`niangpro/niangpro`** est le squelette d'application pour `composer create-project` : l'application de
  démonstration et ses thèmes, qui demande `niangpro/framework` au lieu d'en contenir le code. Il est
  *construit* à partir de ce dépôt (`tools/build-skeleton.php`, `skeleton/`) et publié dans un dépôt miroir
  (`tools/publish-skeleton.sh`) ; on ne le modifie jamais directement.
- `base_path()` prend la racine du projet chez Composer (`InstalledVersions`) : juste que le framework soit
  dans `packages/` ou dans `vendor/`.
- La v1 reste installable (`composer create-project niangpro/framework:^1.5`) ; `UPGRADE.md` décrit le passage
  d'un projet 1.x à 2.0, vérifié sur un projet 1.5.0 créé depuis Packagist.

## Conséquences

- Une application se met à jour avec `composer update niangpro/framework` ; son propre code est séparé du
  framework (`vendor/`), qu'elle ne modifie plus.
- La commande documentée d'installation change (`niangpro/niangpro`) : README, documentation et `niang new`.
- La CI crée un projet par type de site à partir du squelette construit, avec le framework dans `vendor/`,
  et lance les tests de ce projet.
- La publication (dépôts miroirs, Packagist pour `niangpro/niangpro` et les paquets) demande une
  configuration unique par le propriétaire du dépôt (voir `.github/workflows/split.yml`).
