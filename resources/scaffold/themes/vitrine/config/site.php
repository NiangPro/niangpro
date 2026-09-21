<?php

/*
 * Identité et contenu de démonstration du site vitrine « Atelier Lumière » (entreprise fictive).
 * Tout ce que les pages affichent vient d'ici : remplacez ces valeurs par les vôtres, sans toucher
 * aux vues. Le nom peut aussi se régler avec SITE_NAME dans .env.
 */

return [
    'name' => env('SITE_NAME', 'Atelier Lumière'),
    'tagline' => "Architecture d'intérieur et rénovation à Lyon",
    'description' => "Atelier Lumière conçoit et rénove des intérieurs sur mesure à Lyon : logements, commerces et bureaux, de l'esquisse à la remise des clés.",

    'nav' => [
        ['label' => 'Accueil', 'href' => '/'],
        ['label' => 'À propos', 'href' => '/a-propos'],
        ['label' => 'Services', 'href' => '/services'],
        ['label' => 'Réalisations', 'href' => '/realisations'],
        ['label' => 'FAQ', 'href' => '/faq'],
        ['label' => 'Contact', 'href' => '/contact'],
    ],
    'cta' => ['label' => 'Demander un devis', 'href' => '/contact'],

    'footer' => [
        [
            'title' => 'Le studio',
            'links' => [
                ['label' => 'À propos', 'href' => '/a-propos'],
                ['label' => 'Réalisations', 'href' => '/realisations'],
                ['label' => 'FAQ', 'href' => '/faq'],
            ],
        ],
        [
            'title' => 'Nos services',
            'links' => [
                ['label' => 'Conception intérieure', 'href' => '/services'],
                ['label' => 'Rénovation clé en main', 'href' => '/services'],
                ['label' => 'Agencement sur mesure', 'href' => '/services'],
            ],
        ],
        [
            'title' => 'Contact',
            'links' => [
                ['label' => 'Demander un devis', 'href' => '/contact'],
                ['label' => 'bonjour@atelier-lumiere.example', 'href' => 'mailto:bonjour@atelier-lumiere.example'],
            ],
        ],
    ],
    'legal' => [
        ['label' => 'Mentions légales', 'href' => '/mentions-legales'],
        ['label' => 'Politique de confidentialité', 'href' => '/politique-de-confidentialite'],
    ],

    'contact' => [
        'address' => '14 rue des Tanneurs, 69001 Lyon',
        'email' => 'bonjour@atelier-lumiere.example',
        'phone' => '04 78 00 00 00',
        'hours' => 'Du lundi au vendredi, 9 h – 18 h',
    ],

    'stats' => [
        ['value' => '15 ans', 'label' => "d'expérience"],
        ['value' => '220+', 'label' => 'projets livrés'],
        ['value' => '4,9/5', 'label' => 'de satisfaction client'],
        ['value' => '96 %', 'label' => 'de chantiers dans les délais'],
    ],

    'highlights' => [
        ['icon' => 'heart', 'title' => 'Une écoute attentive', 'text' => 'Nous partons de votre façon de vivre, pas de nos habitudes : chaque projet commence par une longue conversation.'],
        ['icon' => 'pen', 'title' => 'Du sur-mesure', 'text' => "Mobilier, rangements, éclairage : ce qui n'existe pas dans le commerce, nous le dessinons et le faisons fabriquer."],
        ['icon' => 'clock', 'title' => 'Des délais tenus', 'text' => 'Un calendrier partagé dès la signature, et un point hebdomadaire sur chantier. Pas de mauvaise surprise.'],
        ['icon' => 'users', 'title' => 'Des artisans de la région', 'text' => "Nous travaillons avec les mêmes menuisiers, peintres et électriciens lyonnais depuis dix ans."],
    ],

    'services' => [
        [
            'icon' => 'palette',
            'title' => 'Conception intérieure',
            'text' => "Plans, ambiances, matériaux et mobilier : une proposition complète et chiffrée, avec des rendus 3D pour vous projeter avant de décider.",
            'includes' => ['Relevé et plans avant/après', 'Planches d\'ambiance et rendus 3D', 'Sélection du mobilier et des matériaux'],
        ],
        [
            'icon' => 'tool',
            'title' => 'Rénovation clé en main',
            'text' => "Nous coordonnons tous les corps de métier et vous n'avez qu'un seul interlocuteur, de la démolition à la remise des clés.",
            'includes' => ['Consultation et devis des artisans', 'Planning et suivi de chantier', 'Réception et levée des réserves'],
        ],
        [
            'icon' => 'layers',
            'title' => 'Agencement sur mesure',
            'text' => 'Cuisines, dressings, bibliothèques, banques d\'accueil : des ensembles dessinés pour votre espace, fabriqués en atelier local.',
            'includes' => ['Dessin technique détaillé', 'Fabrication en atelier partenaire', 'Pose et finitions'],
        ],
        [
            'icon' => 'sparkles',
            'title' => "Conseil en éclairage",
            'text' => "La lumière fait ou défait une pièce. Nous étudions l'éclairage naturel et artificiel pour des espaces agréables à toute heure.",
            'includes' => ['Étude de l\'éclairage existant', 'Plan d\'implantation des luminaires', 'Choix des sources et des variateurs'],
        ],
        [
            'icon' => 'home',
            'title' => 'Home staging',
            'text' => "Vous vendez ou vous louez ? Nous valorisons votre bien avec un budget maîtrisé pour qu'il se démarque, photos à l'appui.",
            'includes' => ['Diagnostic et plan d\'action', 'Mise en scène et location de mobilier', 'Shooting photo du bien'],
        ],
        [
            'icon' => 'briefcase',
            'title' => 'Commerces et bureaux',
            'text' => "Boutiques, restaurants, cabinets, open spaces : des lieux qui racontent votre marque et où l'on travaille mieux.",
            'includes' => ['Programme et parcours clients', 'Mise en conformité ERP', 'Chantier phasé pour rester ouvert'],
        ],
    ],

    'process' => [
        ['title' => 'Rencontre', 'text' => 'Un premier rendez-vous gratuit, chez vous, pour comprendre vos besoins et votre budget.'],
        ['title' => 'Conception', 'text' => 'Plans, ambiances et chiffrage détaillé. Vous validez chaque étape avant la suivante.'],
        ['title' => 'Chantier', 'text' => "Nous pilotons les artisans, contrôlons la qualité et vous tenons informé chaque semaine."],
        ['title' => 'Remise des clés', 'text' => 'Réception avec vous, levée des réserves et conseils d\'entretien. Votre intérieur est prêt.'],
    ],

    'projects' => [
        ['slug' => 'appartement-haussmannien', 'title' => 'Appartement haussmannien', 'type' => 'Logement', 'year' => 2025, 'surface' => '112 m²', 'glyph' => 'home'],
        ['slug' => 'maison-sous-les-tilleuls', 'title' => 'Maison Sous les Tilleuls', 'type' => 'Rénovation', 'year' => 2025, 'surface' => '160 m²', 'glyph' => 'tool'],
        ['slug' => 'boutique-fil-et-aiguille', 'title' => 'Boutique Fil & Aiguille', 'type' => 'Commerce', 'year' => 2024, 'surface' => '48 m²', 'glyph' => 'bag'],
        ['slug' => 'restaurant-le-comptoir-vert', 'title' => 'Restaurant Le Comptoir Vert', 'type' => 'Restauration', 'year' => 2024, 'surface' => '95 m²', 'glyph' => 'leaf'],
        ['slug' => 'bureaux-des-cinq-ponts', 'title' => 'Bureaux des Cinq Ponts', 'type' => 'Bureaux', 'year' => 2023, 'surface' => '320 m²', 'glyph' => 'briefcase'],
        ['slug' => 'loft-de-la-manufacture', 'title' => 'Loft de la Manufacture', 'type' => 'Logement', 'year' => 2023, 'surface' => '140 m²', 'glyph' => 'layers'],
    ],

    'testimonials' => [
        ['quote' => "Ils ont transformé notre trois-pièces sombre en un appartement lumineux. Le chantier s'est déroulé exactement comme prévu, budget compris.", 'name' => 'Camille R.', 'role' => 'Appartement, Lyon 6e'],
        ['quote' => "Une vraie écoute et beaucoup de goût. Notre boutique ne ressemble à aucune autre, et nos clients nous le disent tous les jours.", 'name' => 'Mehdi B.', 'role' => 'Gérant de boutique'],
        ['quote' => "Un seul interlocuteur pour tout le chantier : c'est ce qui nous a convaincus. Nous n'avons jamais eu à relancer un artisan.", 'name' => 'Sophie et Antoine L.', 'role' => 'Maison, Caluire-et-Cuire'],
    ],

    'faq' => [
        ['q' => 'Le premier rendez-vous est-il payant ?', 'a' => "Non. La première rencontre, chez vous ou sur place, est gratuite et sans engagement. Elle nous permet de comprendre votre projet et de vous donner une première fourchette de budget."],
        ['q' => 'Quel budget faut-il prévoir ?', 'a' => "Cela dépend de la surface et de l'ampleur des travaux. À titre indicatif, une rénovation complète démarre autour de 1 200 € par m². Nous établissons toujours un devis détaillé, poste par poste, avant tout engagement."],
        ['q' => 'Combien de temps dure un projet ?', 'a' => "Comptez de 4 à 8 semaines de conception, puis de 2 à 6 mois de chantier selon l'ampleur. Un calendrier précis vous est remis à la signature."],
        ['q' => 'Intervenez-vous en dehors de Lyon ?', 'a' => "Oui, dans un rayon d'environ 100 km autour de Lyon. Au-delà, nous étudions chaque demande : n'hésitez pas à nous écrire."],
        ['q' => 'Pouvez-vous reprendre un projet déjà commencé ?', 'a' => "Oui, si les plans et les devis existants le permettent. Nous faisons d'abord un audit gratuit de ce qui a été fait et de ce qu'il reste à faire."],
        ['q' => 'Proposez-vous une garantie sur les travaux ?', 'a' => "Tous nos chantiers sont couverts par la garantie de parfait achèvement (1 an) et la garantie décennale de nos artisans. Nous restons joignables après la remise des clés."],
    ],

    'team' => [
        ['name' => 'Inès Marchand', 'role' => 'Architecte d\'intérieur, fondatrice'],
        ['name' => 'Karim Diallo', 'role' => 'Conducteur de travaux'],
        ['name' => 'Léa Fontaine', 'role' => 'Designer mobilier'],
    ],
];
