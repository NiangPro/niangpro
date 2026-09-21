<?php
/**
 * @var string $content
 * @var string|null $title
 * @var string|null $description
 */
layout('layouts.base', [
    'title' => $title ?? null,
    'description' => $description ?? null,
    'bodyClass' => 'theme-landing',
    'header' => component('components/site-header', [
        'brand' => config('site.name'),
        'nav' => config('site.nav'),
        'cta' => config('site.cta'),
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
