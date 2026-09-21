<?php
/**
 * Layout du thème : assemble l'en-tête et le pied de page à partir de config/site.php, puis
 * délègue le squelette HTML à layouts/base.
 *
 * @var string $content
 * @var string|null $title
 * @var string|null $description
 * @var string|null $active  chemin de la page courante (pour aria-current dans la navigation)
 */
layout('layouts.base', [
    'title' => $title ?? null,
    'description' => $description ?? null,
    'bodyClass' => 'theme-vitrine',
    'header' => component('components/site-header', [
        'brand' => config('site.name'),
        'nav' => config('site.nav'),
        'cta' => config('site.cta'),
        'active' => $active ?? '',
    ]),
    'footer' => component('components/site-footer', [
        'brand' => config('site.name'),
        'tagline' => config('site.tagline'),
        'columns' => config('site.footer'),
        'legal' => config('site.legal'),
    ]),
]);
?>
<?= $content ?>
