<?php
/**
 * Liste d'articles d'une catégorie ou d'un thème.
 *
 * @var string $heading
 * @var string $lead
 * @var list<array<string, mixed>> $posts
 */
layout('layouts.app', ['title' => $heading, 'active' => '/tags']);
?>
<?= component('components/page-hero', ['title' => $heading, 'lead' => $lead, 'crumbs' => [['label' => 'Catégories et thèmes', 'href' => '/tags']]]) ?>

<section class="section">
    <div class="container">
        <?php if (!$posts): ?>
            <p class="text-center muted">Aucun article ici pour l'instant.</p>
        <?php endif; ?>
        <div class="grid grid--3">
            <?php foreach ($posts as $post): ?>
                <?= component('components/post-card', ['post' => $post]) ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
