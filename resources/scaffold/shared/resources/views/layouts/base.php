<?php
/**
 * Squelette HTML commun à tous les thèmes. Le layout propre au thème (layouts/app.php) construit
 * l'en-tête et le pied de page avec les composants partagés, puis délègue ici.
 *
 * @var string $content
 * @var string|null $title        titre de la page, suivi du nom du site dans <title>
 * @var string|null $description  meta description (défaut : config('site.description'))
 * @var string|null $bodyClass    classe du <body>, ex. 'theme-vitrine'
 * @var string $header            HTML de l'en-tête
 * @var string $footer            HTML du pied de page
 */
$siteName = (string) config('site.name', 'Mon site');
$tagline = (string) config('site.tagline', '');
$pageTitle = !empty($title) ? "$title — $siteName" : ($tagline !== '' ? "$siteName — $tagline" : $siteName);
$pageDescription = $description ?? (string) config('site.description', '');
// ?v=<date de modification> : un fichier modifié est retéléchargé, un fichier inchangé reste en cache.
$asset = static fn (string $path): string => '/' . $path . '?v=' . (@filemtime(base_path('public/' . $path)) ?: 1);
$success = flashed('success');
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <?php if ($pageDescription !== ''): ?>
        <meta name="description" content="<?= e($pageDescription) ?>">
        <meta property="og:description" content="<?= e($pageDescription) ?>">
    <?php endif; ?>
    <meta property="og:title" content="<?= e($pageTitle) ?>">
    <meta property="og:type" content="website">
    <meta name="theme-color" content="#f5faf9" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0c1917" media="(prefers-color-scheme: dark)">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="stylesheet" href="<?= e($asset('css/niang.css')) ?>">
    <link rel="stylesheet" href="<?= e($asset('css/theme.css')) ?>">
    <script src="<?= e($asset('js/niang.js')) ?>"></script>
    <?php if (is_file(base_path('public/js/theme.js'))): ?>
        <script src="<?= e($asset('js/theme.js')) ?>" defer></script>
    <?php endif; ?>
</head>
<body class="<?= e($bodyClass ?? '') ?>">
    <a class="skip-link" href="#contenu">Aller au contenu</a>
    <?= $header ?>
    <main id="contenu" tabindex="-1">
        <?php if ($success): ?>
            <div class="container" style="padding-top: var(--space-5)">
                <p class="alert alert--success" role="status"><?= e($success) ?></p>
            </div>
        <?php endif; ?>
        <?= $content ?>
    </main>
    <?= $footer ?>
</body>
</html>
