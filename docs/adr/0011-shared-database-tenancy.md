# 0011 — Multi-locataire : base partagée d'abord

- **Statut** : accepté
- **Date** : 2026-09-27

## Contexte

La roadmap (§50) cite trois modèles : base partagée (colonne `tenant_id`), un schéma par locataire, une base
par locataire. Le framework range dans la même base ses propres tables (sessions, cache, jetons d'API, file
d'attente, limitation de débit) et celles de l'application, derrière une seule connexion d'écriture.

## Décision

Seule la base partagée est prise en charge : `Model::$tenantScoped` filtre et renseigne `tenant_id`,
`Tenancy` porte le locataire courant, le cache est préfixé par locataire. Un modèle par locataire interrogé
sans locataire courant lève une exception : une route qui oublie le middleware échoue au lieu de montrer les
données de tous les clients.

Une base (ou un schéma) par locataire n'est pas proposée : basculer la connexion déplacerait aussi les
sessions, le cache et les jetons, ou demanderait une seconde connexion « centrale » dans tout le cœur
(Query Builder, transactions, migrations, pilotes). À reprendre si un besoin réel apparaît, probablement
dans un paquet séparé.

## Conséquences

- Une seule base à sauvegarder, migrer et superviser ; les requêtes entre locataires (administration,
  statistiques) restent simples avec `Tenancy::central()`.
- L'isolation repose sur le filtre : `DB::select()` et `new QueryBuilder()` écrits à la main ne sont pas
  filtrés, ce que la documentation signale.
- Les clients qui exigent une isolation physique ne sont pas couverts.
