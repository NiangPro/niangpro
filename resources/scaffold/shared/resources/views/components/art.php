<?php
/**
 * Illustration abstraite en SVG inline, générée de façon déterministe à partir de `seed` : deux
 * appels avec la même graine donnent la même image. Remplace les photos de démonstration sans
 * fichier binaire, sans requête réseau et sans dépendance — remplacez-la par un <img> (avec son
 * alt) le jour où vous avez de vraies photos.
 *
 * @var string|int $seed   graine (un identifiant, un slug...)
 * @var string $label      description lue par les lecteurs d'écran (équivalent de alt)
 * @var string|null $ratio  proportions CSS, ex. '16 / 10' (défaut) ou '1 / 1'
 * @var string|null $glyph  nom d'une icône (components/icon) à poser au centre
 */
$palettes = [
    ['#2dd4bf', '#0e7490', '#facc15'],
    ['#facc15', '#b45309', '#2dd4bf'],
    ['#a78bfa', '#4338ca', '#5eead4'],
    ['#fb7185', '#9f1239', '#facc15'],
    ['#38bdf8', '#1d4ed8', '#5eead4'],
    ['#4ade80', '#166534', '#facc15'],
];

$hash = crc32((string) $seed);
[$from, $to, $spark] = $palettes[$hash % count($palettes)];
$id = 'art' . dechex($hash);
$ratio = $ratio ?? '16 / 10';
$glyph = $glyph ?? null;

// Trois formes dont la position et la taille varient avec la graine.
$x1 = 60 + ($hash % 200);
$y1 = 60 + (($hash >> 3) % 140);
$x2 = 300 + (($hash >> 6) % 260);
$y2 = 120 + (($hash >> 9) % 200);
$r1 = 90 + (($hash >> 12) % 90);
$r2 = 60 + (($hash >> 15) % 80);
?>
<div class="art" style="aspect-ratio: <?= e($ratio) ?>">
    <svg viewBox="0 0 640 400" preserveAspectRatio="xMidYMid slice" role="img" aria-label="<?= e($label) ?>">
        <defs>
            <linearGradient id="<?= e($id) ?>" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0" stop-color="<?= e($from) ?>"/>
                <stop offset="1" stop-color="<?= e($to) ?>"/>
            </linearGradient>
        </defs>
        <rect width="640" height="400" fill="url(#<?= e($id) ?>)"/>
        <circle cx="<?= $x1 ?>" cy="<?= $y1 ?>" r="<?= $r1 ?>" fill="#fff" opacity="0.16"/>
        <circle cx="<?= $x2 ?>" cy="<?= $y2 ?>" r="<?= $r2 ?>" fill="<?= e($spark) ?>" opacity="0.5"/>
        <rect x="<?= $x2 - 260 ?>" y="<?= $y1 + 140 ?>" width="220" height="220" rx="36" fill="#000" opacity="0.14" transform="rotate(<?= ($hash % 40) - 20 ?> <?= $x2 - 150 ?> <?= $y1 + 250 ?>)"/>
        <?php if ($glyph): ?>
            <g transform="translate(272 152) scale(4.5)" fill="none" stroke="#fff" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round" opacity="0.92">
                <?= component('components/icon-paths', ['name' => $glyph]) ?>
            </g>
        <?php endif; ?>
    </svg>
</div>
