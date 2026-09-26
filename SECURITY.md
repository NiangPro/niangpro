# Politique de sécurité

## Versions maintenues

Les correctifs de sécurité sont publiés pour la dernière version mineure. Les projets créés avec
`composer create-project` sont des copies du framework : appliquez le correctif en mettant à jour
les fichiers de `src/Core/` concernés, comme indiqué dans l'avis de sécurité.

| Version | Correctifs de sécurité |
| ------- | ---------------------- |
| 1.5.x   | ✅                     |
| < 1.5   | ❌                     |

## Signaler une vulnérabilité

**N'ouvrez pas d'issue publique** pour une faille de sécurité : elle serait visible de tous avant
qu'un correctif existe.

Signalez-la en privé via GitHub : onglet **Security** du dépôt, puis **Report a vulnerability**
(<https://github.com/NiangPro/niangpro/security/advisories/new>).

Indiquez si possible :

- la version de NiangPro et de PHP ;
- la partie concernée (fichier, classe, route) ;
- les étapes pour reproduire, ou une preuve de concept minimale ;
- l'impact que vous envisagez (lecture de données, exécution de code, contournement d'authentification...).

## Ce qui se passe ensuite

1. **Accusé de réception** sous 72 heures.
2. **Évaluation** sous 7 jours : nous confirmons la faille (ou expliquons pourquoi ce n'en est pas une)
   et estimons sa gravité.
3. **Correctif** développé en privé dans l'avis de sécurité GitHub, avec un test qui reproduit la faille.
4. **Publication** : nouvelle version, entrée `### Security` dans `CHANGELOG.md` et avis de sécurité
   public. Nous visons 30 jours au plus entre le signalement et la publication ; nous vous tenons
   informé si c'est plus long.

## Crédits

Sauf demande contraire de votre part, votre nom (ou pseudonyme) est cité dans l'avis de sécurité et
dans le `CHANGELOG.md`.

## Hors périmètre

- Les failles dans le code d'une application construite avec NiangPro (contrôleurs, vues, requêtes
  écrites par l'application), sauf si le framework rend l'erreur facile ou l'encourage.
- Les configurations explicitement déconseillées par la documentation (`APP_DEBUG=true` ou
  `MAIL_MAILER=log` en production, `APP_KEY` partagée entre projets...).
- Les attaques par déni de service nécessitant un volume de requêtes que la limitation de débit
  (`ThrottleRequests`) ou l'infrastructure devrait absorber.
