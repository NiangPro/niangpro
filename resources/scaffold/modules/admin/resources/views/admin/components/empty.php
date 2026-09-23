<?php
/**
 * @var string $title
 * @var string|null $text
 * @var string|null $icon
 * @var string|null $action  HTML déjà sûr (bouton)
 */
?>
<div class="admin-empty">
    <span class="admin-empty__icon"><?= component('components/icon', ['name' => $icon ?? 'inbox']) ?></span>
    <p class="admin-empty__title"><?= e($title) ?></p>
    <?php if (!empty($text)): ?><p class="admin-muted"><?= e($text) ?></p><?php endif; ?>
    <?= $action ?? '' ?>
</div>
