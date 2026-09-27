<?php

/*
 * Abonnements. 'provider' : classe qui implémente App\Billing\BillingProvider. Le fournisseur
 * « fake » active un plan immédiatement, sans paiement : pour le développement et les démonstrations.
 * Pour Stripe, Paddle, PayDunya, CinetPay... écrivez votre propre classe (checkout, cancel, webhook).
 */
return [
    'provider' => env('BILLING_PROVIDER', \App\Billing\FakeBillingProvider::class),

    // Plan d'une nouvelle organisation.
    'default_plan' => 'free',

    // Limites : null = illimité.
    'plans' => [
        'free' => ['name' => 'Gratuit', 'price' => 0, 'limits' => ['projects' => 3, 'members' => 2]],
        'pro' => ['name' => 'Pro', 'price' => 29, 'limits' => ['projects' => null, 'members' => 25]],
    ],

    'currency' => '€',
];
