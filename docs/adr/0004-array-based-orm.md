# 0004 — Un ORM qui renvoie des tableaux

- **Statut** : accepté
- **Date** : 2026-09-10 (v1.0.0), confirmé en septembre 2026

## Contexte

Un ORM à objets (Active Record « riche », Data Mapper) apporte le chargement paresseux, le suivi des
modifications et les mutateurs, mais aussi des requêtes cachées (N+1), des objets difficiles à sérialiser
et beaucoup de code. La roadmap demande de « conserver l'approche simple basée sur les tableaux ».

## Décision

`Model` est une classe statique : `Post::find(1)` renvoie un `array`, jamais un objet. Les comportements
utiles sont ajoutés autour de ce principe plutôt qu'en le remplaçant : `$fillable`, `$casts`, dates
automatiques, suppression douce, relations chargées explicitement avec `with()`, liaison de modèle sur les
routes (qui injecte le tableau), pagination.

## Conséquences

- Pas de requête cachée : tout chargement de relation est explicite.
- Les données passent telles quelles en JSON, en session, dans une file d'attente.
- Pas de `$post->author->name` : on écrit `Post::with('author')` puis `$post['author']['name']`.
- `show(User $user)` n'existe pas ; la liaison de modèle donne `show(array $user)`.
