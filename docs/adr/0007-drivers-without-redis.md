# 0007 — Pilotes fichier et base de données avant Redis

- **Statut** : accepté
- **Date** : septembre 2026

## Contexte

Sessions, cache, limitation de débit et file d'attente doivent être partagés entre plusieurs serveurs web.
Redis est la solution habituelle, mais demande un serveur de plus et, en PHP, une extension ou une
bibliothèque.

## Décision

Chaque service a un pilote `file` (défaut, un seul serveur), un pilote `database` (partagé, sur la base
déjà présente) et un pilote `array` ou `sync` pour les tests. L'atomicité est obtenue par des requêtes
conditionnelles (`UPDATE ... WHERE`, compare-and-swap) plutôt que par des verrous propres à un moteur.

## Conséquences

- Passer à plusieurs serveurs ne demande que deux ou trois variables d'environnement et `migrate`.
- La base porte plus de charge qu'avec Redis ; pour un trafic élevé, un pilote Redis reste à écrire (il
  faudrait un client du protocole RESP, dans l'esprit de 0006).
- L'atomicité est vérifiée par des tests concurrents réels (workers et process simultanés sur MySQL).
