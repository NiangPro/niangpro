<?php

/*
 * Identité et contenu de démonstration du portfolio d'« Amina Sow » (personne fictive).
 * Tout ce que les pages affichent vient d'ici : remplacez ces valeurs par les vôtres.
 * Pour ajouter un projet, ajoutez une entrée à 'projects' : sa page /projets/<slug> existe aussitôt.
 */

return [
    'name' => env('SITE_NAME', 'Amina Sow'),
    'tagline' => 'Designer produit et développeuse front-end',
    'description' => "Portfolio d'Amina Sow, designer produit et développeuse front-end : applications, systèmes de design et sites accessibles.",

    'nav' => [
        ['label' => 'Accueil', 'href' => '/'],
        ['label' => 'Projets', 'href' => '/projets'],
        ['label' => 'À propos', 'href' => '/a-propos'],
        ['label' => 'Contact', 'href' => '/contact'],
    ],
    'cta' => ['label' => 'Travaillons ensemble', 'href' => '/contact'],

    'footer' => [
        [
            'title' => 'Explorer',
            'links' => [
                ['label' => 'Projets', 'href' => '/projets'],
                ['label' => 'À propos et CV', 'href' => '/a-propos'],
                ['label' => 'Contact', 'href' => '/contact'],
            ],
        ],
        [
            'title' => 'Me contacter',
            'links' => [
                ['label' => 'bonjour@amina-sow.example', 'href' => 'mailto:bonjour@amina-sow.example'],
                ['label' => 'Écrire un message', 'href' => '/contact'],
            ],
        ],
    ],
    'legal' => [],

    'contact' => [
        'email' => 'bonjour@amina-sow.example',
        'address' => 'Lyon · à distance dans toute la France',
        'hours' => 'Disponible à partir de janvier',
    ],

    'intro' => [
        'greeting' => 'Bonjour, je suis Amina.',
        'headline' => 'Je conçois des produits numériques clairs, accessibles et agréables à utiliser.',
        'text' => "Designer produit et développeuse front-end depuis huit ans, j'accompagne des équipes de la première maquette à la mise en production. Je crois qu'une interface réussie est une interface qu'on ne remarque pas.",
    ],

    'stats' => [
        ['value' => '8 ans', 'label' => "d'expérience"],
        ['value' => '35', 'label' => 'projets livrés'],
        ['value' => 'AA', 'label' => 'niveau d\'accessibilité visé'],
    ],

    'categories' => [
        'produit' => 'Design produit',
        'systeme' => 'Design system',
        'web' => 'Sites et e-commerce',
        'mobile' => 'Mobile',
    ],

    'projects' => [
        [
            'slug' => 'sillage-reservation',
            'title' => 'Sillage — refonte de l\'application de réservation',
            'category' => 'produit',
            'year' => 2026,
            'client' => 'Sillage (entreprise fictive)',
            'role' => 'Designer produit principale',
            'duration' => '5 mois',
            'glyph' => 'calendar',
            'summary' => "Réduire de moitié le temps nécessaire pour réserver un créneau, sur mobile comme sur ordinateur.",
            'challenge' => "L'application permettait de réserver en neuf étapes, avec un taux d'abandon de 62 %. Les équipes support recevaient chaque semaine des dizaines de demandes pour des réservations ratées.",
            'approach' => "Nous avons observé quinze utilisateurs réserver en conditions réelles, puis supprimé tout ce qui ne servait pas la décision : un seul écran de choix, un récapitulatif clair, une confirmation immédiate. Chaque écran a été testé au clavier et avec un lecteur d'écran avant d'être développé.",
            'results' => [
                ['value' => '−54 %', 'label' => 'de temps de réservation'],
                ['value' => '4 étapes', 'label' => 'au lieu de neuf'],
                ['value' => '−38 %', 'label' => 'de demandes au support'],
            ],
            'stack' => ['Figma', 'Tests utilisateurs', 'HTML/CSS', 'Accessibilité'],
            'gallery' => ['Écran de choix du créneau', 'Récapitulatif avant paiement', 'Confirmation de réservation'],
        ],
        [
            'slug' => 'prisme-design-system',
            'title' => 'Prisme — un système de design pour six produits',
            'category' => 'systeme',
            'year' => 2025,
            'client' => 'Groupe Prisme (entreprise fictive)',
            'role' => 'Lead design system',
            'duration' => '8 mois',
            'glyph' => 'layers',
            'summary' => "Une bibliothèque de composants et de principes partagée par six équipes, avec l'accessibilité intégrée dès le premier composant.",
            'challenge' => "Six produits, six boutons différents. Chaque équipe réinventait ses formulaires, avec des niveaux d'accessibilité très inégaux et un coût de maintenance croissant.",
            'approach' => "Un audit des interfaces existantes a fait émerger vingt-quatre composants récurrents. Nous les avons redessinés avec des variables (couleurs, espacements, typographie) pour permettre les déclinaisons par produit, documentés avec des exemples vivants, et livrés en CSS pur, sans dépendance.",
            'results' => [
                ['value' => '24', 'label' => 'composants documentés'],
                ['value' => '6', 'label' => 'produits migrés'],
                ['value' => '−30 %', 'label' => 'de temps de développement'],
            ],
            'stack' => ['CSS variables', 'Documentation', 'Design tokens', 'Accessibilité'],
            'gallery' => ['Palette et échelle typographique', 'Bibliothèque de formulaires', 'Page de documentation'],
        ],
        [
            'slug' => 'terre-battue-boutique',
            'title' => 'Terre Battue — boutique de céramique en ligne',
            'category' => 'web',
            'year' => 2025,
            'client' => 'Atelier Terre Battue (entreprise fictive)',
            'role' => 'Design et développement front-end',
            'duration' => '3 mois',
            'glyph' => 'bag',
            'summary' => "Une boutique légère et rapide pour une céramiste, avec des photos qui restent les vraies vedettes.",
            'challenge' => "L'ancien site, lourd et lent, faisait fuir les visiteurs mobiles. Les photos de pièces uniques étaient compressées au point de perdre leur texture.",
            'approach' => "Une mise en page sobre qui laisse la place aux images, des fiches produit qui racontent chaque pièce, et un parcours d'achat en trois écrans. Les images sont servies en plusieurs tailles, avec des dimensions réservées pour éviter tout saut de mise en page.",
            'results' => [
                ['value' => '1,1 s', 'label' => 'de chargement sur mobile'],
                ['value' => '+41 %', 'label' => 'de conversion'],
                ['value' => '100', 'label' => 'de score Lighthouse accessibilité'],
            ],
            'stack' => ['HTML/CSS', 'JavaScript vanilla', 'Performance', 'E-commerce'],
            'gallery' => ['Page d\'accueil', 'Fiche produit', 'Panier'],
        ],
        [
            'slug' => 'cadran-tableau-de-bord',
            'title' => 'Cadran — un tableau de bord lisible d\'un coup d\'œil',
            'category' => 'produit',
            'year' => 2024,
            'client' => 'Cadran Énergie (entreprise fictive)',
            'role' => 'Designer produit',
            'duration' => '4 mois',
            'glyph' => 'chart',
            'summary' => "Transformer trente indicateurs illisibles en cinq chiffres qui disent tout de suite si tout va bien.",
            'challenge' => "Les responsables de site passaient dix minutes chaque matin à interpréter un tableau de bord surchargé, et manquaient parfois des alertes importantes.",
            'approach' => "Nous avons demandé à chaque utilisateur quelle décision il prenait avec chaque indicateur, et supprimé ceux qui n'en déclenchaient aucune. Les alertes utilisent la couleur, une icône et un texte : jamais la couleur seule.",
            'results' => [
                ['value' => '5', 'label' => 'indicateurs clés'],
                ['value' => '−80 %', 'label' => 'de temps de lecture'],
                ['value' => '0', 'label' => 'alerte manquée en 6 mois'],
            ],
            'stack' => ['Data-visualisation', 'Tests utilisateurs', 'Accessibilité'],
            'gallery' => ['Vue d\'ensemble', 'Détail d\'une alerte', 'Version mobile'],
        ],
        [
            'slug' => 'fil-rouge-association',
            'title' => 'Fil Rouge — le site d\'une association, accessible à tous',
            'category' => 'web',
            'year' => 2024,
            'client' => 'Association Fil Rouge (fictive)',
            'role' => 'Design et développement',
            'duration' => '6 semaines',
            'glyph' => 'heart',
            'summary' => "Un site institutionnel conçu avec et pour des personnes en situation de handicap, conforme au niveau AA du référentiel.",
            'challenge' => "L'association accompagne des personnes en situation de handicap, mais son propre site ne leur était pas accessible : navigation au clavier impossible, contrastes insuffisants.",
            'approach' => "Nous avons travaillé avec un groupe d'utilisateurs testeurs à chaque étape. Le site est en HTML sémantique, sans JavaScript indispensable, avec un contraste élevé par défaut et une taille de texte réglable.",
            'results' => [
                ['value' => 'AA', 'label' => 'niveau atteint et audité'],
                ['value' => '12', 'label' => 'testeurs impliqués'],
                ['value' => '+70 %', 'label' => 'de dons en ligne'],
            ],
            'stack' => ['HTML sémantique', 'CSS', 'Audit RGAA', 'Tests avec lecteurs d\'écran'],
            'gallery' => ['Page d\'accueil', 'Formulaire de don', 'Réglages d\'affichage'],
        ],
        [
            'slug' => 'halte-application-mobile',
            'title' => 'Halte — une application de pause pour les équipes',
            'category' => 'mobile',
            'year' => 2023,
            'client' => 'Halte (start-up fictive)',
            'role' => 'Designer produit',
            'duration' => '4 mois',
            'glyph' => 'clock',
            'summary' => "Une application mobile qui propose une pause de deux minutes, au bon moment, sans jamais culpabiliser.",
            'challenge' => "Les applications de bien-être existantes ajoutaient de la pression : séries, badges, rappels insistants. L'équipe voulait l'inverse.",
            'approach' => "Nous avons retiré tous les mécanismes de gamification et conçu des rappels qui s'espacent d'eux-mêmes quand l'utilisateur les ignore. L'interface tient en trois écrans, utilisables d'une main.",
            'results' => [
                ['value' => '4,8/5', 'label' => 'de note moyenne'],
                ['value' => '3 écrans', 'label' => 'pour toute l\'application'],
                ['value' => '68 %', 'label' => 'd\'utilisateurs actifs après 3 mois'],
            ],
            'stack' => ['Design mobile', 'Prototypage', 'Tests utilisateurs'],
            'gallery' => ['Écran de pause', 'Réglage des rappels', 'Bilan de la semaine'],
        ],
    ],

    'about' => [
        'lead' => "Je suis designer produit et développeuse front-end. Je travaille aussi bien dans Figma que dans un éditeur de code, ce qui m'évite les allers-retours inutiles entre la maquette et l'écran.",
        'paragraphs' => [
            "J'ai commencé comme développeuse dans une agence lyonnaise, où j'ai vite compris que les meilleurs produits naissent quand ceux qui dessinent et ceux qui construisent se parlent dès le premier jour.",
            "Depuis, j'accompagne des équipes de toutes tailles : start-up, associations, grands groupes. Ma méthode tient en trois mots : observer, simplifier, vérifier. J'observe de vrais utilisateurs, je retire tout ce qui ne sert pas, et je vérifie que le résultat fonctionne pour tout le monde — clavier, lecteur d'écran, petit écran, connexion lente.",
            "Hors écran, je fais de la céramique, du vélo, et je cuisine trop pour une seule personne.",
        ],
    ],

    'skills' => [
        ['title' => 'Design', 'items' => ['Recherche utilisateur', 'Prototypage', 'Design system', 'Ateliers de conception']],
        ['title' => 'Développement', 'items' => ['HTML sémantique', 'CSS moderne', 'JavaScript', 'PHP']],
        ['title' => 'Qualité', 'items' => ['Accessibilité (RGAA, WCAG)', 'Performance web', 'Tests utilisateurs', 'Documentation']],
    ],

    'experience' => [
        ['period' => '2022 — aujourd\'hui', 'title' => 'Designer produit indépendante', 'text' => 'Missions pour des start-up et des associations : conception, systèmes de design, développement front-end.'],
        ['period' => '2019 — 2022', 'title' => 'Lead design, Groupe Prisme', 'text' => 'Création et animation du système de design partagé par six produits, encadrement de trois designers.'],
        ['period' => '2017 — 2019', 'title' => 'Développeuse front-end, agence Lumen', 'text' => 'Sites et applications pour une vingtaine de clients, avec un intérêt croissant pour l\'accessibilité.'],
    ],
    'education' => [
        ['period' => '2015 — 2017', 'title' => 'Master Design d\'interaction', 'text' => 'Université de Lyon.'],
        ['period' => '2012 — 2015', 'title' => 'Licence informatique', 'text' => 'Parcours développement web.'],
    ],
];
