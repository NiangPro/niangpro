<?php
/**
 * Liste de barres horizontales (répartition, classement).
 *
 * @var list<array{label: string, value: int|float, display?: string, href?: string}> $rows
 * @var string|null $empty  texte affiché si toutes les valeurs sont nulles
 */
$max = max(0, ...array_map(static fn (array $r): float => (float) $r['value'], $rows ?: [['value' => 0]]));
?>
<?php if ($max <= 0): ?>
    <p class="admin-muted"><?= e($empty ?? 'Aucune donnée pour le moment.') ?></p>
<?php else: ?>
    <ul class="admin-bars">
        <?php foreach ($rows as $i => $row): ?>
            <li>
                <div class="admin-bars__head">
                    <?php if (!empty($row['href'])): ?>
                        <a href="<?= e($row['href']) ?>"><?= e($row['label']) ?></a>
                    <?php else: ?>
                        <span><?= e($row['label']) ?></span>
                    <?php endif; ?>
                    <strong><?= e($row['display'] ?? (string) $row['value']) ?></strong>
                </div>
                <span class="admin-bars__track"><span class="admin-bars__fill admin-bars__fill--<?= $i % 5 ?>" style="width: <?= round((float) $row['value'] * 100 / $max, 1) ?>%"></span></span>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
