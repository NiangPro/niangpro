# Smoke test navigateurs (Chromium, Firefox, WebKit)

Outil **jetable**, séparé du projet PHP : rien ici n'est un prérequis de `composer test`. Un
projet NiangPro sans Node.js n'en a besoin d'aucune partie. Utile pour vérifier le rendu réel des
thèmes de site sur les trois moteurs que Playwright embarque — WebKit étant le seul moyen
réaliste de tester le rendu Safari sans Mac dédié (et ici couvre aussi Safari iOS : même moteur).

## Utilisation

1. Générer un projet par thème (frère du dépôt, `niang new` les place à côté) :

   ```bash
   cd ..
   ./niangpro/bin/niang new niang-smoke-minimal --type=minimal
   ./niangpro/bin/niang new niang-smoke-vitrine --type=vitrine
   ./niangpro/bin/niang new niang-smoke-ecommerce --type=ecommerce
   ./niangpro/bin/niang new niang-smoke-blog --type=blog
   ./niangpro/bin/niang new niang-smoke-portfolio --type=portfolio
   ./niangpro/bin/niang new niang-smoke-landing --type=landing
   (cd niang-smoke-ecommerce && ./bin/niang migrate && ./bin/niang db:seed)
   (cd niang-smoke-blog && ./bin/niang migrate && ./bin/niang db:seed)
   ```

   Désactivez `APP_DEBUG` dans chaque `.env` généré (`sed -i '' 's/^APP_DEBUG=.*/APP_DEBUG=false/'
   niang-smoke-*/.env` sur macOS). Sans ça, la debug toolbar (`position: fixed; bottom: 0`)
   intercepte les clics Playwright destinés à des éléments proches du bas de l'écran (piège
   rencontré en écrivant ce script : un clic sur une question FAQ scrollée en bas de viewport
   atterrissait sur la toolbar, pas la question — rien à voir avec un vrai visiteur en production).

2. Démarrer un serveur par thème, sur les ports attendus par `smoke.mjs` :

   ```bash
   (cd niang-smoke-minimal && php -S 127.0.0.1:8101 -t public) &
   (cd niang-smoke-vitrine && php -S 127.0.0.1:8102 -t public) &
   (cd niang-smoke-ecommerce && php -S 127.0.0.1:8103 -t public) &
   (cd niang-smoke-blog && php -S 127.0.0.1:8104 -t public) &
   (cd niang-smoke-portfolio && php -S 127.0.0.1:8105 -t public) &
   (cd niang-smoke-landing && php -S 127.0.0.1:8106 -t public) &
   ```

3. Depuis ce dossier :

   ```bash
   npm install
   npx playwright install chromium firefox webkit
   npm run smoke
   ```

## Ce qui est vérifié

- Sur les 6 thèmes : `/`, `/up`, `/health` répondent 200, sur les 3 moteurs.
- Sur `vitrine` (représentatif : menu, formulaire, FAQ — fournis par le design system partagé à
  tous les thèmes, donc suffisant pour prouver que `niang.js`/`niang.css` fonctionnent
  cross-browser sans répéter la même vérification 6 fois) : le menu burger ouvre/ferme la
  navigation, la soumission du formulaire de contact aboutit, un clic sur une question FAQ ouvre
  le `<details>`, le rendu change bien entre `prefers-color-scheme` clair et sombre.

## Ce qui n'est PAS vérifié ici

Le rendu pixel par pixel (pas de comparaison de captures d'écran), le comportement réel de la
barre d'adresse rétractable de Safari iOS (Playwright/WebKit ne la simule pas — la correction CSS
`100dvh` du CHANGELOG a été vérifiée par lecture du comportement documenté de `dvh`, pas mesurée
sur un vrai iPhone), l'autofill visuel (Playwright ne déclenche pas le remplissage natif du
navigateur pour peindre le fond spécifique à l'autofill).
