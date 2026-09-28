# 0006 — SMTP, TOTP, OAuth et SSE écrits à la main

- **Statut** : accepté
- **Date** : septembre 2026 (lots d'audit P0/P1, 1 et 2)

## Contexte

Envoyer des emails, proposer la double authentification, la connexion avec Google ou GitHub et le temps
réel reposent d'habitude sur des bibliothèques (PHPMailer ou Symfony Mailer, une bibliothèque TOTP, un
client OAuth, un serveur WebSocket). La décision 0001 exclut les dépendances d'exécution.

## Décision

Implémenter chaque protocole sur son strict nécessaire, avec les protections de sécurité non négociables :

- **SMTP** : STARTTLS obligatoire (pas de repli en clair), certificat vérifié, identifiants jamais envoyés
  en clair hors localhost, injection d'en-têtes refusée ;
- **TOTP** : RFC 6238 et 4226, secret chiffré, anti-rejeu ;
- **OAuth 2** : `state` à usage unique et PKCE, compte rattaché seulement par un email vérifié ;
- **temps réel** : Server-Sent Events plutôt que WebSocket (un flux HTTP ordinaire, sans serveur dédié).

## Conséquences

- Aucune dépendance, et un code court que l'on peut relire entièrement.
- Chaque protocole doit être vérifié contre une référence indépendante : faux serveur SMTP et aiosmtpd
  avec le parseur `email` de Python, vecteurs de la RFC 6238 et implémentation Python, faux fournisseur
  OAuth, serveur réel pour SSE.
- Les cas plus rares ne sont pas couverts : pas d'`AUTH CRAM-MD5`, deux fournisseurs OAuth seulement,
  pas de WebSocket.
