<?php layout('layouts.app', ['title' => 'Articles', 'wide' => true]); ?>

<h1>Articles</h1>

<?php if (!$posts): ?>
    <p>Aucun article pour l'instant.</p>
<?php endif; ?>

<?php foreach ($posts as $post): ?>
    <div class="post">
        <h2><?= e($post['title']) ?></h2>
        <p><?= e($post['body']) ?></p>
        <?php foreach ($post['tags'] as $tag): ?>
            <span class="tag">#<?= e($tag['name']) ?></span>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>

<?= $paginator->links('/blog') ?>
