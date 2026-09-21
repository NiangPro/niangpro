<?php
/**
 * Liste paginée servie par PostController::page.
 *
 * @var list<array<string, mixed>> $posts
 * @var \Niang\Core\Database\Paginator $paginator
 */
layout('layouts.app', ['title' => 'Articles', 'active' => '/blog', 'description' => 'Tous les articles du blog, du plus récent au plus ancien.']);
?>
<?= component('components/page-hero', [
    'title' => 'Articles',
    'lead' => $paginator->total . ' article' . ($paginator->total > 1 ? 's' : '') . ' — page ' . $paginator->currentPage . ' sur ' . $paginator->lastPage() . '.',
    'crumbs' => [],
]) ?>

<section class="section">
    <div class="container">
        <?php if (!$posts): ?>
            <p class="text-center muted">Aucun article pour l'instant.</p>
        <?php endif; ?>

        <div class="grid grid--3">
            <?php foreach ($posts as $post): ?>
                <?= component('components/post-card', ['post' => $post]) ?>
            <?php endforeach; ?>
        </div>

        <?= $paginator->links('/blog') ?>
    </div>
</section>
