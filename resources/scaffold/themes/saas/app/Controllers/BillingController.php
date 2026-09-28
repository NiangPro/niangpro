<?php

namespace App\Controllers;

use App\Billing\Billing;
use App\Support\Team;
use Niang\Core\Controller;
use Niang\Core\Http\Request;
use Niang\Core\Http\Response;

/** Plan et abonnement de l'organisation (administrateurs). */
class BillingController extends Controller
{
    public function show(): Response
    {
        return $this->view('team/billing', [
            'organization' => Team::current(),
            'plans' => Billing::plans(),
            'current' => Team::current()['plan'],
            'isOwner' => Team::hasRole('owner'),
        ]);
    }

    public function checkout(Request $request): Response
    {
        $data = $this->validate($request, ['plan' => 'required|in:' . implode(',', array_keys(Billing::plans()))]);

        return $this->redirect(Billing::provider()->checkout(Team::current(), $data['plan'], url(Team::url('/abonnement'))))
            ->with('success', 'Abonnement mis à jour.');
    }

    public function cancel(): Response
    {
        Billing::provider()->cancel(Team::current());

        return $this->redirect(Team::url('/abonnement'))->with('success', 'Abonnement résilié : retour au plan gratuit.');
    }

    /** Appelé par le prestataire de paiement (sans session ni CSRF : sa signature fait foi). */
    public function webhook(Request $request): Response
    {
        return Billing::provider()->webhook($request) ? Response::html('', 204) : Response::json(['message' => 'Événement refusé.'], 400);
    }
}
