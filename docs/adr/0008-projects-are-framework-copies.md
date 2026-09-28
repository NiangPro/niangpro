# 0008 — Un projet créé est une copie du framework

- **Statut** : remplacé par [0013](0013-framework-as-dependency.md) en 2.0
- **Date** : 2026-09 (dixième jalon, thèmes de site)

## Contexte

`composer create-project niangpro/framework` copie le dépôt entier : `src/Core/` fait partie du projet,
au lieu d'être une dépendance dans `vendor/`. C'est simple à comprendre et à modifier, mais un projet ne
reçoit pas automatiquement les correctifs du framework.

## Décision

Garder ce modèle pour l'instant (roadmap : « le framework de base reste minimal »), et le rendre sûr :

- chaque projet reçoit sa propre `APP_KEY` à la création ;
- les thèmes retirent le code et les tests de démonstration qui ne les concernent pas (`theme.json`) ;
- les correctifs de sécurité sont publiés avec la liste des fichiers de `src/Core/` à mettre à jour
  (voir SECURITY.md).

## Conséquences

- Un développeur peut lire et adapter tout le code de son application, framework compris.
- Les mises à jour sont manuelles ; c'est la principale raison de la séparation en paquets
  (`niangpro/framework` en dépendance) prévue par la roadmap pour la v2.0 (§46), qui remplacera cet ADR.
