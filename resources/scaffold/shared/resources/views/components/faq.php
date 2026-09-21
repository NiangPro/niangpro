<?php
/**
 * Questions fréquentes en <details>/<summary> natifs : accessibles au clavier et sans JavaScript.
 *
 * @var list<array{q: string, a: string}> $items
 * @var bool|null $openFirst  ouvre la première question
 */
$openFirst = $openFirst ?? false;
?>
<div class="faq">
    <?php foreach ($items as $index => $item): ?>
        <details<?= $openFirst && $index === 0 ? ' open' : '' ?>>
            <summary><?= e($item['q']) ?> <?= component('components/icon', ['name' => 'chevron-down']) ?></summary>
            <div><p><?= e($item['a']) ?></p></div>
        </details>
    <?php endforeach; ?>
</div>
