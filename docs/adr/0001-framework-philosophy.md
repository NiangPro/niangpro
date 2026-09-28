# 0001 — Philosophie : PHP natif, cœur minimal, aucune dépendance d'exécution

- **Statut** : accepté
- **Date** : 2026-09-10 (v1.0.0)

## Contexte

Les frameworks PHP complets (Laravel, Symfony) apportent beaucoup, au prix de centaines de classes, de
dizaines de dépendances et d'une « magie » (façades, proxies, annotations) qui rend le code difficile à
lire pour un débutant et à auditer pour une équipe. NiangPro vise les applications web, API et SaaS de
petite et moyenne taille, et l'apprentissage.

## Décision

- Le code lu par le développeur est du PHP ordinaire : classes statiques explicites, tableaux, closures.
  Pas de proxy dynamique ni de configuration par annotation.
- `composer.json` `require` ne contient que PHP et des interfaces PSR (voir 0005). Toute fonctionnalité
  du cœur est écrite dans le dépôt.
- Une fonctionnalité n'entre dans le cœur que si elle sert la plupart des projets, peut se faire sans
  dépendance et peut être testée (roadmap §78). Sinon, elle vit dans un paquet séparé.

## Conséquences

- Installation et mise à jour simples, aucune dépendance transitive à surveiller en production.
- Le framework doit maintenir lui-même du code que d'autres délèguent (client SMTP, TOTP, OAuth : voir
  0006). Chaque ajout de ce type exige des tests contre un vrai serveur ou des vecteurs de référence.
- Pas d'écosystème de paquets tiers à réutiliser tel quel pour le cœur.
