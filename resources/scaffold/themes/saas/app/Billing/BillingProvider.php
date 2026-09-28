<?php

namespace App\Billing;

use Niang\Core\Http\Request;

/**
 * Prestataire de paiement (config/billing.php, 'provider'). Trois moments :
 *  - checkout() : l'administrateur choisit un plan ; renvoie l'URL où l'envoyer payer (page du
 *    prestataire), ou $returnUrl si l'abonnement est déjà actif ;
 *  - cancel()   : résiliation demandée depuis l'application ;
 *  - webhook()  : le prestataire confirme un paiement, un renouvellement, un échec... ; vérifiez sa
 *    signature, puis appliquez le changement avec Billing::apply().
 */
interface BillingProvider
{
    /** @param array<string, mixed> $organization */
    public function checkout(array $organization, string $plan, string $returnUrl): string;

    /** @param array<string, mixed> $organization */
    public function cancel(array $organization): void;

    /** Vrai si l'événement était valide et a été traité ; faux (réponse 400) sinon. */
    public function webhook(Request $request): bool;
}
