<?php

/*
 * Identité et contenu de démonstration de la landing page « Boussole » (produit fictif).
 * Tout ce que la page affiche vient d'ici : remplacez ces valeurs par les vôtres.
 * Le nom peut aussi se régler avec SITE_NAME dans .env.
 */

return [
    'name' => env('SITE_NAME', 'Boussole'),
    'tagline' => "L'outil de planification qui tient sur une page",
    'description' => "Boussole aide les équipes à planifier leur semaine en cinq minutes : vue partagée, rappels intelligents, aucune configuration.",

    // Où mènent les boutons « Essayer » : remplacez par l'adresse de votre application ou de votre inscription.
    'signup_url' => 'mailto:bonjour@boussole.example?subject=Essai%20gratuit%20de%20Boussole',

    // Les liens commencent par « / » : ils fonctionnent aussi depuis la page de mentions légales.
    'nav' => [
        ['label' => 'Fonctionnalités', 'href' => '/#fonctionnalites'],
        ['label' => 'Comment ça marche', 'href' => '/#fonctionnement'],
        ['label' => 'Avis', 'href' => '/#temoignages'],
        ['label' => 'Tarifs', 'href' => '/#tarifs'],
        ['label' => 'FAQ', 'href' => '/#faq'],
    ],
    'cta' => ['label' => 'Essayer gratuitement', 'href' => '/#tarifs'],

    'footer' => [
        [
            'title' => 'Produit',
            'links' => [
                ['label' => 'Fonctionnalités', 'href' => '/#fonctionnalites'],
                ['label' => 'Tarifs', 'href' => '/#tarifs'],
                ['label' => 'FAQ', 'href' => '/#faq'],
            ],
        ],
        [
            'title' => 'Contact',
            'links' => [
                ['label' => 'bonjour@boussole.example', 'href' => 'mailto:bonjour@boussole.example'],
            ],
        ],
    ],
    'legal' => [
        ['label' => 'Mentions légales', 'href' => '/mentions-legales'],
    ],

    'contact' => [
        'address' => '22 quai des Chartrons, 33000 Bordeaux',
        'email' => 'bonjour@boussole.example',
    ],

    'hero' => [
        'badge' => 'Nouveau · Version 2.0',
        'title' => 'Planifiez la semaine de votre équipe en cinq minutes',
        'text' => "Boussole remplace vos tableurs et vos fils de messages par une seule vue partagée : qui fait quoi, pour quand, sans réunion de suivi.",
        'trust' => 'Déjà utilisé par plus de 1 200 équipes',
    ],

    'features' => [
        ['icon' => 'calendar', 'title' => 'Une vue semaine partagée', 'text' => "Toute l'équipe voit d'un coup d'œil qui fait quoi, jour par jour. Un glisser-déposer suffit à replanifier."],
        ['icon' => 'zap', 'title' => 'Rappels intelligents', 'text' => "Boussole prévient au bon moment, et se tait quand tout est sous contrôle. Fini les notifications inutiles."],
        ['icon' => 'link', 'title' => 'Connecté à vos outils', 'text' => "Synchronisation avec votre calendrier et votre messagerie, sans configuration ni compte supplémentaire."],
        ['icon' => 'lock', 'title' => 'Rôles et permissions', 'text' => "Choisissez qui peut voir, modifier ou seulement commenter. Vos données restent hébergées en France."],
        ['icon' => 'refresh', 'title' => 'Fonctionne hors ligne', 'text' => "Modifiez votre planning dans le train : tout se synchronise dès que la connexion revient."],
        ['icon' => 'chart', 'title' => 'Bilans automatiques', 'text' => "Un résumé chaque vendredi : ce qui est terminé, ce qui a glissé, la charge de chacun."],
    ],

    'steps' => [
        ['title' => 'Créez votre équipe', 'text' => 'Invitez vos collègues par email. Aucune installation, aucun mot de passe à retenir.'],
        ['title' => 'Ajoutez vos tâches', 'text' => 'Importez un tableur ou saisissez-les au fil de l\'eau. Boussole propose des dates réalistes.'],
        ['title' => 'Suivez l\'avancement', 'text' => 'Chacun coche ses tâches, le reste se met à jour tout seul. Le bilan arrive le vendredi.'],
    ],

    'testimonials' => [
        ['quote' => "Nous avons supprimé notre réunion du lundi matin. Tout le monde sait déjà ce qu'il a à faire en arrivant.", 'name' => 'Clara M.', 'role' => 'Cheffe de projet, studio de design'],
        ['quote' => "Enfin un outil que l'équipe utilise vraiment. Adopté en une semaine, sans formation.", 'name' => 'Yann T.', 'role' => 'Directeur technique, PME industrielle'],
        ['quote' => "Le bilan du vendredi nous a fait prendre conscience de la charge réelle de chacun. Précieux.", 'name' => 'Nadia K.', 'role' => 'Responsable d\'équipe, association'],
    ],

    'plans' => [
        [
            'name' => 'Gratuit',
            'audience' => 'Pour essayer avec une petite équipe',
            'monthly' => 0,
            'yearly' => 0,
            'unit' => '',
            'featured' => false,
            'cta' => 'Commencer gratuitement',
            'features' => ["Jusqu'à 5 personnes", 'Vue semaine partagée', '30 jours d\'historique'],
        ],
        [
            'name' => 'Équipe',
            'audience' => 'Pour les équipes qui planifient chaque semaine',
            'monthly' => 9,
            'yearly' => 7,
            'unit' => 'par personne et par mois',
            'featured' => true,
            'cta' => 'Essayer 30 jours',
            'features' => ['Personnes illimitées', 'Rappels intelligents', 'Synchronisation calendrier', 'Bilans hebdomadaires', 'Historique illimité'],
        ],
        [
            'name' => 'Entreprise',
            'audience' => 'Pour les organisations avec des besoins spécifiques',
            'monthly' => null,
            'yearly' => null,
            'unit' => 'sur devis',
            'featured' => false,
            'cta' => 'Nous contacter',
            'features' => ['Tout le plan Équipe', 'Connexion SSO', 'Journal d\'audit', 'Accompagnement dédié'],
        ],
    ],

    'faq' => [
        ['q' => "Puis-je essayer Boussole sans carte bancaire ?", 'a' => "Oui. Le plan Gratuit ne demande aucune carte, et l'essai de 30 jours du plan Équipe non plus. Vous ne payez que si vous décidez de continuer."],
        ['q' => "Où sont hébergées mes données ?", 'a' => "En France, chez un hébergeur certifié. Elles ne sont jamais revendues, ni utilisées pour entraîner quoi que ce soit."],
        ['q' => "Puis-je importer mes tâches existantes ?", 'a' => "Oui, depuis un fichier CSV ou un tableur. L'import prend quelques minutes, et nous vous aidons si besoin."],
        ['q' => "Que se passe-t-il si j'arrête ?", 'a' => "Vous pouvez exporter toutes vos données à tout moment, dans un format standard. Aucune durée d'engagement, aucune pénalité."],
        ['q' => "Boussole fonctionne-t-il sur mobile ?", 'a' => "Oui, dans le navigateur de votre téléphone, avec le même confort que sur ordinateur, et hors ligne."],
        ['q' => "Proposez-vous des tarifs pour les associations ?", 'a' => "Oui : 50 % de réduction sur le plan Équipe pour les associations et les établissements d'enseignement. Écrivez-nous."],
    ],
];
