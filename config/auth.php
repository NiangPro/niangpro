<?php

return [
    /*
     * Durée de validité du lien de réinitialisation de mot de passe (signedRoute()), en minutes.
     * Volontairement courte : un lien de reset qui traîne dans une boîte mail est une fenêtre
     * d'attaque.
     */
    'password_reset_expire_minutes' => (int) env('PASSWORD_RESET_EXPIRE_MINUTES', 60),

    /*
     * Durée de validité du lien de vérification d'email (signedRoute()), en heures. Plus longue
     * que le reset de mot de passe : rien de sensible n'est débloqué par ce lien à lui seul.
     */
    'email_verification_expire_hours' => (int) env('EMAIL_VERIFICATION_EXPIRE_HOURS', 24),
];
