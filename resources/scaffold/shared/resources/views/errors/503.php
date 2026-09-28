<?php
// Volontairement autonome (pas de layout) : pendant une maintenance, le layout du site peut
// dépendre d'une base en cours de migration ou de fichiers en cours de déploiement.
?>
<!DOCTYPE html>
<html lang="<?= e(\Niang\Core\Lang::locale()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title><?= e(__('http.page_maintenance_title')) ?></title>
    <style>
        :root { color-scheme: light dark; --bg: #f7f8fa; --fg: #1d2330; --muted: #5b6474; --accent: #0f9d8a; }
        @media (prefers-color-scheme: dark) { :root { --bg: #11151c; --fg: #e8ecf2; --muted: #9aa3b2; } }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: var(--bg); color: var(--fg);
               font: 16px/1.6 system-ui, -apple-system, "Segoe UI", sans-serif; padding: 24px; box-sizing: border-box; }
        main { max-width: 32rem; text-align: center; }
        .code { font-size: .875rem; font-weight: 600; letter-spacing: .12em; color: var(--accent); }
        h1 { font-size: 1.75rem; margin: .25rem 0 .75rem; }
        p { color: var(--muted); margin: 0; }
    </style>
</head>
<body>
<main>
    <p class="code">503</p>
    <h1><?= e(__('http.page_maintenance_title')) ?></h1>
    <p><?= e(__('http.page_maintenance')) ?></p>
</main>
</body>
</html>
