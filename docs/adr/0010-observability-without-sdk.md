# 0010 — Observabilité sans SDK : W3C Trace Context, logs JSON, Prometheus

- **Statut** : accepté
- **Date** : 2026-09-27

## Contexte

La roadmap (§53) demande métriques, traces, logs structurés et health checks. Le SDK PHP d'OpenTelemetry
apporte une dizaine de paquets, une extension recommandée et un exportateur à faire tourner à chaque
requête : contraire à 0001 (aucune dépendance d'exécution) et coûteux pour une application sans
supervision. Les health checks existent déjà (`/health`, `/health/live`, `/health/ready`).

## Décision

S'appuyer sur les formats standards plutôt que sur un SDK :

- **Traces** : le framework parle W3C Trace Context. Un `traceparent` entrant est rejoint, `Http\Client` le
  propage aux services appelés, et l'identifiant de requête (`X-Request-Id`) est dans chaque log. Un proxy,
  un APM ou un collecteur OpenTelemetry placé devant relie ainsi ses spans aux logs de l'application.
- **Logs** : `LOG_FORMAT=json`, un objet par ligne, lisible par tout agrégateur.
- **Métriques** : format texte de Prometheus (lu aussi par l'agent OpenTelemetry, Datadog, Grafana Agent...),
  compteurs dans le cache existant, séries bornées d'avance (méthode, classe de statut, jamais l'URL).

## Conséquences

- Rien n'est activé qui coûte : l'en-tête et le contexte de log sont gratuits ; les métriques ajoutent trois
  incréments de cache par requête et restent désactivées par défaut.
- Pas de spans produits par l'application elle-même (durée des requêtes SQL, des vues...) : l'histogramme
  des durées et la barre de debug couvrent l'essentiel ; un SDK complet reste possible dans un paquet séparé.
- Pas d'étiquette par route : une route mal nommée ne peut pas faire exploser le nombre de séries.
