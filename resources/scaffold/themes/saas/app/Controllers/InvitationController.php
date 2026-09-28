<?php

namespace App\Controllers;

use App\Billing\Billing;
use App\Models\Invitation;
use App\Models\Membership;
use Niang\Core\Auth;
use Niang\Core\Controller;
use Niang\Core\Exceptions\NotFoundException;
use Niang\Core\Http\Response;
use Niang\Core\Tenancy;

/** Lien reçu par email : /invitations/{token}. La personne doit être connectée avec l'adresse invitée. */
class InvitationController extends Controller
{
    public function show(string $token): Response
    {
        [$invitation, $organization] = $this->find($token);

        return $this->view('invitations/show', [
            'invitation' => $invitation,
            'organization' => $organization,
            'token' => $token,
            'matches' => strcasecmp((string) Auth::user()['email'], $invitation['email']) === 0,
        ]);
    }

    public function accept(string $token): Response
    {
        [$invitation, $organization] = $this->find($token);

        if (strcasecmp((string) Auth::user()['email'], $invitation['email']) !== 0) {
            return $this->redirect("/invitations/$token")->with('error', "Cette invitation est destinée à {$invitation['email']}.");
        }

        $joined = Tenancy::run($organization, function () use ($invitation): bool {
            if (Membership::query()->where('user_id', Auth::id())->first() === null) {
                // Les invitations en attente ne comptent pas dans la limite : elle est vérifiée ici.
                if (!Billing::allows('members')) {
                    return false;
                }

                Membership::forceCreate(['user_id' => Auth::id(), 'role' => $invitation['role']]);
            }

            Invitation::forceDestroy($invitation['id']);

            return true;
        });

        if (!$joined) {
            return $this->redirect("/invitations/$token")->with('error', "« {$organization['name']} » a atteint la limite de membres de son plan : prévenez la personne qui vous a invité.");
        }

        return $this->redirect('/o/' . $organization['slug'])->with('success', "Bienvenue dans « {$organization['name']} ».");
    }

    /** @return array{0: array<string, mixed>, 1: array<string, mixed>} */
    private function find(string $token): array
    {
        $invitation = Tenancy::central(fn () => Invitation::query()
            ->where('token_hash', hash('sha256', $token))
            ->where('expires_at', '>', date('Y-m-d H:i:s'))
            ->first()) ?? throw new NotFoundException();

        return [$invitation, Tenancy::find('id', $invitation['tenant_id']) ?? throw new NotFoundException()];
    }
}
