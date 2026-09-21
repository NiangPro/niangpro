<?php
/**
 * Témoignages courts (contenu fictif de démonstration à remplacer par de vrais retours).
 *
 * @var list<array{quote: string, name: string, role: string}> $items
 */
?>
<div class="grid grid--3">
    <?php foreach ($items as $item): ?>
        <figure class="card quote reveal">
            <blockquote><?= e($item['quote']) ?></blockquote>
            <figcaption>
                <span class="avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($item['name'], 0, 1))) ?></span>
                <div><strong><?= e($item['name']) ?></strong><span><?= e($item['role']) ?></span></div>
            </figcaption>
        </figure>
    <?php endforeach; ?>
</div>
