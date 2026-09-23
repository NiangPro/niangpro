<?php

/*
 * Identité du blog « Le Carnet Clair » (publication fictive). Remplacez ces valeurs par les vôtres ;
 * les articles, eux, sont en base de données (voir database/seeders/DatabaseSeeder.php).
 * Le nom peut aussi se régler avec SITE_NAME dans .env.
 */

return [
    'name' => env('SITE_NAME', 'Le Carnet Clair'),
    'tagline' => 'Idées, coulisses et guides pour mieux concevoir le web',
    'description' => 'Le Carnet Clair publie des idées, des coulisses de projets et des guides pratiques sur le design, l\'accessibilité et la performance du web.',

    // Nombre d'articles par page sur /blog (lu par PostController::page).
    'posts_per_page' => 6,

    'nav' => [
        ['label' => 'Accueil', 'href' => '/'],
        ['label' => 'Articles', 'href' => '/blog'],
        ['label' => 'Thèmes', 'href' => '/tags'],
        ['label' => 'À propos', 'href' => '/a-propos'],
        ['label' => 'Contact', 'href' => '/contact'],
    ],

    'footer' => [
        [
            'title' => 'Lire',
            'links' => [
                ['label' => 'Tous les articles', 'href' => '/blog'],
                ['label' => 'Catégories et thèmes', 'href' => '/tags'],
            ],
        ],
        [
            'title' => 'Catégories',
            'links' => [
                ['label' => 'Idées', 'href' => '/categories/idees'],
                ['label' => 'Coulisses', 'href' => '/categories/coulisses'],
                ['label' => 'Guides', 'href' => '/categories/guides'],
            ],
        ],
        [
            'title' => 'Le blog',
            'links' => [
                ['label' => 'À propos', 'href' => '/a-propos'],
                ['label' => 'Contact', 'href' => '/contact'],
                ['label' => 'Espace rédaction', 'href' => '/login'],
            ],
        ],
    ],
    'legal' => [],

    'contact' => [
        'email' => 'redaction@carnet-clair.example',
    ],

    'categories' => [
        'idees' => ['label' => 'Idées', 'icon' => 'sparkles', 'text' => 'Des points de vue sur la façon de concevoir et de construire pour le web.'],
        'coulisses' => ['label' => 'Coulisses', 'icon' => 'eye', 'text' => 'Comment nous travaillons vraiment : projets, échecs, routines.'],
        'guides' => ['label' => 'Guides', 'icon' => 'book', 'text' => 'Des pas-à-pas concrets, à appliquer dès aujourd\'hui.'],
    ],
];
