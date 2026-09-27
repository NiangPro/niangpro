# Architecture Decision Records

Une décision d'architecture par fichier : le contexte, la décision, ses conséquences (y compris ce
qu'elle coûte). Un ADR n'est jamais réécrit après coup : une décision qui change fait l'objet d'un
nouvel ADR, qui remplace l'ancien (statut « Remplacé par 00XX »).

| N° | Décision | Statut |
| --- | --- | --- |
| [0001](0001-framework-philosophy.md) | Philosophie : PHP natif, cœur minimal, aucune dépendance d'exécution | Accepté |
| [0002](0002-database-grammar.md) | Une couche Grammar par moteur SQL | Accepté |
| [0003](0003-native-php-views.md) | Vues en PHP natif, sans moteur de template | Accepté |
| [0004](0004-array-based-orm.md) | Un ORM qui renvoie des tableaux | Accepté |
| [0005](0005-psr-compatibility.md) | Compatibilité PSR par les interfaces seulement | Accepté |
| [0006](0006-hand-written-protocols.md) | SMTP, TOTP, OAuth et SSE écrits à la main | Accepté |
| [0007](0007-drivers-without-redis.md) | Pilotes fichier et base de données avant Redis | Accepté |
| [0008](0008-projects-are-framework-copies.md) | Un projet créé est une copie du framework | Remplacé par 0013 |
| [0009](0009-redis-without-extension.md) | Pilotes Redis avec un client écrit à la main | Accepté (complète 0007) |
| [0010](0010-observability-without-sdk.md) | Observabilité sans SDK : W3C Trace Context, logs JSON, Prometheus | Accepté |
| [0011](0011-shared-database-tenancy.md) | Multi-locataire : base partagée d'abord | Accepté |
| [0012](0012-package-boundaries.md) | Frontières des paquets avant la séparation (§46) | Accepté |
| [0013](0013-framework-as-dependency.md) | Le framework est une dépendance du projet (v2) | Accepté (remplace 0008) |

Modèle : copiez un fichier existant, numéro suivant, statut « Proposé » tant qu'il est en discussion.
