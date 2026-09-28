# 0002 — Une couche Grammar par moteur SQL

- **Statut** : accepté
- **Date** : 2026-09 (P0 de la roadmap)

## Contexte

Les premières migrations produisaient du SQL SQLite (`INTEGER PRIMARY KEY AUTOINCREMENT`) qui échouait sur
MySQL et PostgreSQL. Les trois moteurs divergent sur les types, les clés auto-incrémentées, les guillemets
d'identifiants, les booléens et les contraintes.

## Décision

`Blueprint` décrit des colonnes abstraites (`string`, `bigInteger`, `uuid`, `enum`...). Seules les classes
`Grammar\SQLiteGrammar`, `MySqlGrammar` et `PostgresGrammar` connaissent le SQL de chaque moteur. Le Query
Builder, lui, n'emploie que du SQL commun aux trois (les formes non portables, comme une union triée, sont
réécrites dans une forme acceptée partout : sous-requête `SELECT * FROM (a UNION b)`).

## Conséquences

- Une même migration fonctionne sur les trois moteurs ; la CI joue la suite Database sur SQLite, MySQL et
  PostgreSQL.
- Certaines opérations ne sont pas offertes faute de traduction fiable : `->change()` d'une colonne existante
  (SQLite ne le permet pas sans reconstruire la table).
- Un nouveau type de colonne demande un cas dans chaque grammaire et un test d'exécution réelle.
