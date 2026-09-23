<?php
/**
 * Layout de l'espace d'administration : barre latérale (sections propres au thème, voir
 * App\Support\AdminMenu), barre supérieure, contenu. Autonome : il ne charge pas le design du site
 * public, seulement ses icônes.
 *
 * @var string $content
 * @var string|null $title     titre de la page, repris dans <title> et dans la barre supérieure
 * @var string|null $active    lien de la barre latérale à mettre en avant, ex. '/admin/produits'
 * @var string|null $subtitle  ligne d'aide sous le titre
 * @var string|null $actions   HTML déjà sûr affiché à droite du titre (boutons)
 */

use App\Support\AdminMenu;
use Niang\Core\Auth;

$siteName = (string) config('site.name', 'Mon site');
$title = $title ?? 'Tableau de bord';
$active = $active ?? '/admin';
$user = Auth::user() ?? [];
$userName = (string) ($user['name'] ?? 'Administrateur');
$initials = mb_strtoupper(implode('', array_map(static fn (string $w): string => mb_substr($w, 0, 1), array_slice(preg_split('/\s+/', trim($userName)) ?: ['A'], 0, 2))));
$asset = static fn (string $path): string => '/' . $path . '?v=' . (@filemtime(base_path('public/' . $path)) ?: 1);
$isActive = static fn (string $href): bool => $href === '/admin' ? $active === '/admin' : ($active === $href || str_starts_with($active, $href . '/'));
$success = flashed('success');
$error = flashed('error');
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($title) ?> — Administration · <?= e($siteName) ?></title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="stylesheet" href="<?= e($asset('css/admin.css')) ?>">
    <script src="<?= e($asset('js/admin.js')) ?>"></script>
</head>
<body class="admin">
<a class="skip-link" href="#admin-content">Aller au contenu</a>
<div class="admin-shell">
    <aside class="admin-sidebar" id="admin-sidebar" aria-label="Menu d'administration">
        <div class="admin-sidebar__brand">
            <span class="admin-brand__mark" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($siteName, 0, 1))) ?></span>
            <span class="admin-brand__text">
                <strong><?= e($siteName) ?></strong>
                <small>Administration</small>
            </span>
        </div>

        <nav class="admin-nav">
            <?php foreach (AdminMenu::sections() as $section): ?>
                <p class="admin-nav__label"><?= e($section['label']) ?></p>
                <ul>
                    <?php foreach ($section['items'] as $item): ?>
                        <li>
                            <a class="admin-nav__link" href="<?= e($item['href']) ?>"<?= $isActive($item['href']) ? ' aria-current="page"' : '' ?>>
                                <?= component('components/icon', ['name' => $item['icon']]) ?>
                                <span><?= e($item['label']) ?></span>
                                <?php if (!empty($item['badge'])): ?>
                                    <span class="admin-nav__badge"><?= (int) $item['badge'] ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endforeach; ?>
        </nav>

        <div class="admin-sidebar__footer">
            <a class="admin-nav__link" href="/" target="_blank" rel="noopener">
                <?= component('components/icon', ['name' => 'external']) ?>
                <span>Voir le site</span>
            </a>
            <div class="admin-user">
                <span class="admin-avatar" aria-hidden="true"><?= e($initials) ?></span>
                <span class="admin-user__text">
                    <strong><?= e($userName) ?></strong>
                    <small><?= e((string) ($user['email'] ?? '')) ?></small>
                </span>
                <form method="POST" action="/logout">
                    <?= csrf_field() ?>
                    <button class="admin-icon-btn" type="submit" aria-label="Se déconnecter" title="Se déconnecter">
                        <?= component('components/icon', ['name' => 'log-out']) ?>
                    </button>
                </form>
            </div>
        </div>
    </aside>
    <div class="admin-backdrop" data-admin-close hidden></div>

    <div class="admin-main">
        <header class="admin-topbar">
            <button class="admin-icon-btn admin-topbar__menu" type="button" data-admin-toggle aria-controls="admin-sidebar" aria-expanded="false" aria-label="Ouvrir le menu">
                <?= component('components/icon', ['name' => 'menu']) ?>
            </button>
            <p class="admin-topbar__crumb">
                <span>Administration</span>
                <?= component('components/icon', ['name' => 'chevron-down', 'class' => 'admin-crumb-sep']) ?>
                <strong><?= e($title) ?></strong>
            </p>
            <div class="admin-topbar__actions">
                <span class="admin-topbar__date"><?= e(\App\Support\AdminFormat::longDate(date('Y-m-d'))) ?></span>
                <button class="admin-icon-btn" type="button" data-theme-toggle aria-label="Basculer le thème clair ou sombre" title="Thème clair / sombre">
                    <?= component('components/icon', ['name' => 'moon', 'class' => 'icon--moon']) ?>
                    <?= component('components/icon', ['name' => 'sun', 'class' => 'icon--sun']) ?>
                </button>
                <a class="admin-avatar admin-avatar--link" href="/admin/parametres" aria-label="Mon compte" title="Mon compte"><?= e($initials) ?></a>
            </div>
        </header>

        <main class="admin-content" id="admin-content" tabindex="-1">
            <div class="admin-page-head">
                <div>
                    <h1><?= e($title) ?></h1>
                    <?php if (!empty($subtitle)): ?><p><?= e($subtitle) ?></p><?php endif; ?>
                </div>
                <?php if (!empty($actions)): ?><div class="admin-page-head__actions"><?= $actions ?></div><?php endif; ?>
            </div>

            <?php if ($success): ?><p class="admin-alert admin-alert--success" role="status"><?= component('components/icon', ['name' => 'check']) ?><?= e($success) ?></p><?php endif; ?>
            <?php if ($error): ?><p class="admin-alert admin-alert--error" role="alert"><?= component('components/icon', ['name' => 'alert']) ?><?= e($error) ?></p><?php endif; ?>

            <?= $content ?>
        </main>
    </div>
</div>
</body>
</html>
