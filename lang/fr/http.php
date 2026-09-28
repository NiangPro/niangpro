<?php

// Messages des erreurs HTTP (HttpException, Handler, pages d'erreur, middlewares d'authentification).
return [
    '401' => 'Authentification requise.',
    '403' => 'Action non autorisée.',
    '404' => 'Page introuvable.',
    '405' => 'Méthode non autorisée.',
    '419' => 'Jeton CSRF invalide ou expiré.',
    '429' => 'Trop de requêtes, réessayez plus tard.',
    'server_error' => 'Erreur serveur.',
    'database_error' => 'Erreur de base de données.',
    'other' => 'Erreur HTTP :status.',
    'forbidden_ability' => 'Action non autorisée : :ability',
    'csrf_mismatch' => 'Jeton CSRF invalide ou expiré. Rechargez la page et réessayez.',
    'unauthenticated' => 'Non authentifié.',
    'invalid_token' => 'Jeton API invalide ou révoqué.',
    'email_not_verified' => 'Adresse email non vérifiée.',
    'invalid_signature' => 'Lien invalide, expiré ou modifié.',
    'page_not_found' => "Cette page n'existe pas.",
    'page_forbidden' => "Vous n'avez pas le droit d'effectuer cette action.",
    'page_server_error' => "Une erreur est survenue. L'équipe a été notifiée.",
    'back_home' => "Retour à l'accueil",
];
