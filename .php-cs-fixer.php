<?php

$finder = (new PhpCsFixer\Finder())
    ->in([__DIR__ . '/src', __DIR__ . '/app', __DIR__ . '/tests', __DIR__ . '/resources/scaffold'])
    // Même périmètre que la racine du projet : ni vues, ni routes, ni config, ni migrations.
    ->notPath(['#/resources/#', '#/routes/#', '#/config/#', '#/database/#', '#/public/#']);

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'no_unused_imports' => true,
        'ordered_imports' => true,
        'trailing_comma_in_multiline' => true,
        'single_quote' => true,
    ])
    ->setFinder($finder);
