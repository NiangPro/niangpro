# 0003 — Vues en PHP natif, sans moteur de template

- **Statut** : accepté
- **Date** : 2026-09-10 (v1.0.0)

## Contexte

Un moteur de template (Blade, Twig) demande de compiler les vues, ajoute une syntaxe à apprendre et une
couche à déboguer, alors que PHP est lui-même un langage de template.

## Décision

Les vues sont des fichiers `.php`. Trois helpers couvrent la composition : `layout()`, `component()` et
`field()`. L'échappement est explicite avec `e()` ; `json_for_html()` couvre le passage de données à du
JavaScript.

## Conséquences

- Aucune compilation, aucun cache de vues ; une erreur pointe sur la vraie ligne du fichier.
- L'échappement n'est pas automatique : c'est la responsabilité de chaque `<?= e(...) ?>`. Les tests de
  sécurité (`tests/Security/InjectionTest.php`) vérifient les helpers ; une revue de code reste nécessaire.
- Les frameworks frontend (Alpine, htmx, Vue, React) s'intègrent par `vite_asset()` sans imposer Node.js.
