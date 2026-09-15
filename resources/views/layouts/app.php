<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'NiangPro') ?></title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <style>
        :root {
            --bg: #0c1917;
            --surface: #14302d;
            --border: #2a4d49;
            --text: #e6f4f2;
            --muted: #8fada8;
            --accent: #2dd4bf;
            --accent-contrast: #06211e;
            --gold: #facc15;
            --success-bg: #123d2c;
            --success-text: #7ee9b8;
            --error: #fca5a5;
        }
        body { font-family: ui-sans-serif, system-ui, sans-serif; background:var(--bg); color:var(--text); display:flex; align-items:center; justify-content:center; min-height:100vh; margin:0; padding:2rem 1rem; }
        .card { background:var(--surface); padding:2rem 2.5rem; border-radius:16px; max-width:var(--card-width, 480px); width:100%; box-shadow:0 20px 40px rgba(0,0,0,.35); }
        h1 { margin-top:0; color:var(--accent); }
        code { background:var(--bg); padding:.15rem .4rem; border-radius:6px; color:var(--gold); }
        a { color:var(--accent); }
        label { display:block; margin:1rem 0 .35rem; font-size:.9rem; color:var(--muted); }
        input, textarea { width:100%; padding:.6rem .75rem; border-radius:8px; border:1px solid var(--border); background:var(--bg); color:var(--text); font:inherit; box-sizing:border-box; }
        textarea { min-height:100px; resize:vertical; }
        button { margin-top:1.25rem; background:var(--accent); color:var(--accent-contrast); border:none; padding:.65rem 1.25rem; border-radius:8px; font-weight:600; cursor:pointer; }
        .success { background:var(--success-bg); color:var(--success-text); padding:.75rem 1rem; border-radius:8px; margin-bottom:1rem; }
        .error { color:var(--error); font-size:.82rem; margin-top:.3rem; }
        .link { margin-top:1.25rem; font-size:.85rem; color:var(--muted); text-align:center; }
        .post { border-bottom:1px solid var(--border); padding:1rem 0; }
        .post:last-child { border-bottom:none; }
        .post h2 { margin:0 0 .3rem; font-size:1.1rem; color:var(--text); }
        .tag { display:inline-block; background:var(--bg); color:var(--muted); font-size:.75rem; padding:.15rem .5rem; border-radius:999px; margin-right:.3rem; }
        .pagination { display:flex; gap:.5rem; margin-top:1.5rem; flex-wrap:wrap; }
        .pagination a, .pagination span { padding:.35rem .7rem; border-radius:6px; font-size:.85rem; text-decoration:none; }
        .pagination a { background:var(--bg); color:var(--accent); }
        .pagination .current { background:var(--accent); color:var(--accent-contrast); font-weight:600; }
    </style>
</head>
<body>
    <div class="card" style="<?= isset($wide) && $wide ? '--card-width:640px' : '' ?>">
        <?= $content ?>
    </div>
</body>
</html>
