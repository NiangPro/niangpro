# 0009 — Pilotes Redis avec un client écrit à la main

- **Statut** : accepté ; complète 0007
- **Date** : 2026-09-27

## Contexte

L'ADR 0007 a fait des pilotes `database` la solution multi-serveurs, en notant qu'un pilote Redis
demanderait un client du protocole. Pour un trafic élevé, la base supporte mal des milliers d'écritures de
cache, de sessions et de compteurs par seconde. L'extension `phpredis` n'est pas disponible partout
(hébergements mutualisés, MAMP), et une bibliothèque (Predis) serait une dépendance d'exécution (0001).

## Décision

Un client RESP2 minimal (`Redis\Connection` : AUTH, SELECT, TLS, réponses imbriquées, binaire préservé) et
des pilotes `redis` pour le cache, les sessions, la file d'attente et la limitation de débit. Toute opération
qui doit être atomique (incrément, compteur de débit, réservation d'un job) est un script Lua exécuté par
Redis, jamais une suite de commandes. Toutes les clés portent un préfixe (`REDIS_PREFIX`).

## Conséquences

- Plusieurs serveurs web partagent l'état sans charger la base.
- Pas de pipeline, de cluster ni de Sentinel : un seul serveur Redis (ou un service géré qui l'expose).
- Redis 6 minimum (`KEEPTTL`).
- Tests contre un vrai Redis en CI et en local, dont des tests concurrents (workers et process simultanés).
