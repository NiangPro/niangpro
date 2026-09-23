<?php
/**
 * Carte d'indicateur du tableau de bord.
 *
 * @var string $label
 * @var string $value      déjà formatée
 * @var string $icon       nom d'icône (components/icon-paths)
 * @var string|null $tone  teal (défaut), violet, amber, rose, sky
 * @var string|null $hint  précision sous la valeur
 * @var float|null $trend  évolution en %, positive ou négative
 */
$tone = $tone ?? 'teal';
$trend = $trend ?? null;
?>
<article class="admin-stat admin-stat--<?= e($tone) ?>">
    <div class="admin-stat__top">
        <span class="admin-stat__label"><?= e($label) ?></span>
        <span class="admin-stat__icon"><?= component('components/icon', ['name' => $icon]) ?></span>
    </div>
    <p class="admin-stat__value"><?= e($value) ?></p>
    <p class="admin-stat__hint">
        <?php if ($trend !== null): ?>
            <span class="admin-trend admin-trend--<?= $trend >= 0 ? 'up' : 'down' ?>"><?= $trend >= 0 ? '▲' : '▼' ?> <?= e(number_format(abs($trend), 0, ',', ' ')) ?> %</span>
        <?php endif; ?>
        <?= e($hint ?? '') ?>
    </p>
</article>
