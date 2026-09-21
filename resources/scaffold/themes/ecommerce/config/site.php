<?php

/*
 * Identité et contenu de démonstration de la boutique « Maison Nomade » (marque fictive).
 * Remplacez ces valeurs par les vôtres : les vues les lisent via config('site....').
 * Le nom peut aussi se régler avec SITE_NAME dans .env. Les produits, eux, sont en base de
 * données (voir database/seeders/DatabaseSeeder.php).
 */

return [
    'name' => env('SITE_NAME', 'Maison Nomade'),
    'tagline' => 'Objets du quotidien, choisis avec soin',
    'description' => 'Maison Nomade réunit des objets pour la maison, la cuisine et le bureau, fabriqués en petites séries par des artisans français.',

    'nav' => [
        ['label' => 'Accueil', 'href' => '/'],
        ['label' => 'Boutique', 'href' => '/boutique'],
        ['label' => 'À propos', 'href' => '/a-propos'],
        ['label' => 'FAQ', 'href' => '/faq'],
        ['label' => 'Contact', 'href' => '/contact'],
    ],

    'footer' => [
        [
            'title' => 'Boutique',
            'links' => [
                ['label' => 'Tous les produits', 'href' => '/boutique'],
                ['label' => 'Maison', 'href' => '/boutique?categorie=maison'],
                ['label' => 'Cuisine', 'href' => '/boutique?categorie=cuisine'],
                ['label' => 'Papeterie', 'href' => '/boutique?categorie=papeterie'],
            ],
        ],
        [
            'title' => 'Aide',
            'links' => [
                ['label' => 'Livraison et retours', 'href' => '/faq'],
                ['label' => 'Contact', 'href' => '/contact'],
                ['label' => 'Conditions générales de vente', 'href' => '/cgv'],
            ],
        ],
        [
            'title' => 'Mon compte',
            'links' => [
                ['label' => 'Connexion', 'href' => '/login'],
                ['label' => 'Créer un compte', 'href' => '/register'],
                ['label' => 'Mon panier', 'href' => '/panier'],
            ],
        ],
    ],
    'legal' => [
        ['label' => 'Conditions générales de vente', 'href' => '/cgv'],
        ['label' => 'Mentions légales', 'href' => '/mentions-legales'],
    ],

    'contact' => [
        'address' => '8 rue des Halles, 44000 Nantes',
        'email' => 'bonjour@maison-nomade.example',
        'phone' => '02 40 00 00 00',
        'hours' => 'Du lundi au vendredi, 9 h – 17 h',
    ],

    // Frais de livraison en centimes ; offerts à partir du seuil.
    'shipping' => ['flat_cents' => 490, 'free_from_cents' => 5000],

    // Affiché sur la page de confirmation tant qu'aucun paiement n'est branché (voir CheckoutController).
    'demo_notice' => true,

    'categories' => [
        'maison' => ['label' => 'Maison', 'icon' => 'home', 'text' => 'Bougies, plaids, coussins : de quoi rendre un intérieur plus doux.'],
        'cuisine' => ['label' => 'Cuisine', 'icon' => 'gift', 'text' => 'Tasses, théières et petits accessoires pour les rituels du quotidien.'],
        'papeterie' => ['label' => 'Papeterie', 'icon' => 'pen', 'text' => 'Carnets et trousses reliés, cousus et assemblés à la main.'],
    ],

    'assurances' => [
        ['icon' => 'truck', 'title' => 'Livraison offerte dès 50 €', 'text' => 'Expédié sous 48 h, suivi inclus.'],
        ['icon' => 'refresh', 'title' => 'Retours sous 30 jours', 'text' => "Vous changez d'avis ? Nous reprenons l'article."],
        ['icon' => 'shield', 'title' => 'Paiement sécurisé', 'text' => 'Vos données bancaires ne transitent jamais par nos serveurs.'],
        ['icon' => 'heart', 'title' => 'Fait par des artisans', 'text' => 'Petites séries, matières durables, atelier connu.'],
    ],

    'faq' => [
        ['q' => 'Quels sont les délais de livraison ?', 'a' => "Les commandes passées avant 14 h sont expédiées le jour même du lundi au vendredi. Comptez ensuite 2 à 4 jours ouvrés en France métropolitaine."],
        ['q' => 'Combien coûte la livraison ?', 'a' => 'La livraison est offerte dès 50 € d\'achat. En dessous, elle est facturée 4,90 € en France métropolitaine.'],
        ['q' => 'Livrez-vous à l\'étranger ?', 'a' => "Pour l'instant, uniquement en France métropolitaine, Belgique et Luxembourg. Écrivez-nous pour une autre destination, nous étudions chaque demande."],
        ['q' => 'Comment retourner un article ?', 'a' => "Vous disposez de 30 jours après réception. Écrivez-nous via le formulaire de contact : nous vous envoyons une étiquette de retour prépayée. Le remboursement intervient sous 5 jours après réception du colis."],
        ['q' => 'Et si mon colis arrive abîmé ?', 'a' => "Signalez-le-nous sous 48 h avec une photo : nous vous renvoyons l'article ou nous vous remboursons, sans que vous ayez à renvoyer quoi que ce soit."],
        ['q' => 'Puis-je modifier ou annuler ma commande ?', 'a' => "Tant qu'elle n'est pas expédiée, oui : contactez-nous rapidement par email en indiquant votre numéro de commande."],
    ],
];
