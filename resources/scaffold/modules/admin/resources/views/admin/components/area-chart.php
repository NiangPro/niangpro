<?php
/**
 * Courbe en aire, en SVG généré côté serveur : aucune bibliothèque JavaScript.
 *
 * @var list<array{label: string, value: int|float, display: string}> $points
 * @var string|null $caption  description accessible du graphique
 */
$points = array_values($points);
$count = count($points);
$width = 640;
$height = 220;
$padTop = 16;
$padBottom = 28;
$max = max(1, ...array_map(static fn (array $p): float => (float) $p['value'], $points ?: [['value' => 1]]));
$plotHeight = $height - $padTop - $padBottom;
$x = static fn (int $i): float => $count > 1 ? round($i * $width / ($count - 1), 2) : $width / 2;
$y = static fn (float $v): float => round($padTop + $plotHeight - $v / $max * $plotHeight, 2);

$line = '';
foreach ($points as $i => $point) {
    $line .= ($i === 0 ? 'M' : ' L') . $x($i) . ' ' . $y((float) $point['value']);
}
$area = $line . " L$width " . ($padTop + $plotHeight) . ' L0 ' . ($padTop + $plotHeight) . ' Z';
$labelEvery = max(1, (int) ceil($count / 7));
$gradientId = 'area-' . substr(md5($line), 0, 8);
?>
<figure class="admin-chart">
    <svg viewBox="0 0 <?= $width ?> <?= $height ?>" preserveAspectRatio="none" role="img" aria-label="<?= e($caption ?? 'Graphique') ?>">
        <defs>
            <linearGradient id="<?= $gradientId ?>" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="var(--a-accent)" stop-opacity="0.35"/>
                <stop offset="100%" stop-color="var(--a-accent)" stop-opacity="0"/>
            </linearGradient>
        </defs>
        <?php for ($g = 0; $g <= 3; $g++): $gy = $padTop + $plotHeight * $g / 3; ?>
            <line class="admin-chart__grid" x1="0" x2="<?= $width ?>" y1="<?= $gy ?>" y2="<?= $gy ?>"/>
        <?php endfor; ?>
        <?php if ($count > 0): ?>
            <path d="<?= $area ?>" fill="url(#<?= $gradientId ?>)"/>
            <path class="admin-chart__line" d="<?= $line ?>" fill="none" vector-effect="non-scaling-stroke"/>
        <?php endif; ?>
    </svg>
    <div class="admin-chart__dots" aria-hidden="true">
        <?php foreach ($points as $i => $point): ?>
            <span class="admin-chart__dot" style="left: <?= $count > 1 ? round($i * 100 / ($count - 1), 3) : 50 ?>%; top: <?= round($y((float) $point['value']) * 100 / $height, 3) ?>%">
                <span class="admin-chart__tip"><strong><?= e($point['display']) ?></strong><?= e($point['label']) ?></span>
            </span>
        <?php endforeach; ?>
    </div>
    <div class="admin-chart__labels" aria-hidden="true">
        <?php foreach ($points as $i => $point): ?>
            <span style="left: <?= $count > 1 ? round($i * 100 / ($count - 1), 3) : 50 ?>%"><?= $i % $labelEvery === 0 || $i === $count - 1 ? e($point['label']) : '' ?></span>
        <?php endforeach; ?>
    </div>
    <table class="visually-hidden">
        <caption><?= e($caption ?? 'Graphique') ?></caption>
        <?php foreach ($points as $point): ?><tr><th scope="row"><?= e($point['label']) ?></th><td><?= e($point['display']) ?></td></tr><?php endforeach; ?>
    </table>
</figure>
