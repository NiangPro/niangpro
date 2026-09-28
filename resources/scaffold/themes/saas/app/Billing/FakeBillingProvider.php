<?php

namespace App\Billing;

use Niang\Core\Http\Request;

/**
 * Sans paiement : le plan choisi est actif immédiatement. Pour le développement et les démonstrations,
 * jamais en production (l'application refuse de l'utiliser avec APP_ENV=production).
 */
class FakeBillingProvider implements BillingProvider
{
    public function checkout(array $organization, string $plan, string $returnUrl): string
    {
        Billing::apply($organization['id'], ['plan' => $plan, 'subscription_status' => 'active', 'subscription_ends_at' => date('Y-m-d H:i:s', strtotime('+1 month')), 'billing_reference' => 'fake_' . $organization['id']]);

        return $returnUrl;
    }

    public function cancel(array $organization): void
    {
        Billing::apply($organization['id'], ['plan' => config('billing.default_plan', 'free'), 'subscription_status' => 'canceled', 'subscription_ends_at' => null]);
    }

    public function webhook(Request $request): bool
    {
        return false;
    }
}
