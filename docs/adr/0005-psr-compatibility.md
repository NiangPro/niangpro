# 0005 — Compatibilité PSR par les interfaces seulement

- **Statut** : accepté
- **Date** : septembre 2026

## Contexte

Les standards PSR permettent de réutiliser du code tiers (middlewares, loggers, bibliothèques qui
attendent un conteneur). Les adopter complètement (PSR-7 immuable partout) changerait l'API du framework
et ajouterait des implémentations.

## Décision

NiangPro implémente les interfaces PSR là où elles servent à l'interopérabilité, sans en faire son API
principale :

- PSR-11 : `Container` implémente `ContainerInterface` ;
- PSR-3 : `Logger` implémente `LoggerInterface` ;
- PSR-14 : `Events\Dispatcher` implémente `EventDispatcherInterface` ;
- PSR-7 et PSR-15 : `Http\Psr7Bridge` et `Http\Psr15Adapter` convertissent aux frontières.

Seuls les paquets d'interfaces (`psr/container`, `psr/log`, `psr/event-dispatcher`, `psr/http-message`,
`psr/http-server-middleware`) figurent dans `require`.

## Conséquences

- Un middleware PSR-15 ou une bibliothèque qui attend un `LoggerInterface` fonctionne sans adaptation lourde.
- L'API quotidienne (`Request`, `Response` mutables, façades statiques) reste simple.
- Une implémentation PSR-7 concrète (nyholm/psr7...) doit être ajoutée par l'application qui utilise le pont.
