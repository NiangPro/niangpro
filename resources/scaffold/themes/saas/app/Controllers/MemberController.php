<?php

namespace App\Controllers;

use App\Billing\Billing;
use App\Mailables\InvitationMailable;
use App\Models\Invitation;
use App\Models\Membership;
use App\Support\Team;
use Niang\Core\Auth;
use Niang\Core\Controller;
use Niang\Core\Database\QueryBuilder;
use Niang\Core\Exceptions\HttpException;
use Niang\Core\Exceptions\NotFoundException;
use Niang\Core\Http\Request;
use Niang\Core\Http\Response;
use Niang\Core\Mail;

/** Membres, rôles et invitations. Écriture réservée aux administrateurs (voir routes/web.php). */
class MemberController extends Controller
{
    public function index(): Response
    {
        $members = (new QueryBuilder('memberships'))
            ->select('memberships.id', 'memberships.role', 'memberships.user_id', 'users.name', 'users.email')
            ->join('users', 'users.id', '=', 'memberships.user_id')
            ->where('memberships.tenant_id', Team::current()['id'])
            ->orderBy('users.name')
            ->get();

        return $this->view('team/members', [
            'members' => $members,
            'invitations' => Invitation::query()->where('expires_at', '>', date('Y-m-d H:i:s'))->orderBy('id', 'desc')->get(),
            'isAdmin' => Team::hasRole('admin'),
            'isOwner' => Team::hasRole('owner'),
            'canInvite' => Billing::allows('members'),
        ]);
    }

    public function invite(Request $request): Response
    {
        $data = $this->validate($request, ['email' => 'required|email', 'role' => 'required|in:member,admin'], [], ['email' => 'adresse email']);
        $email = strtolower($data['email']);

        if (!Billing::allows('members')) {
            return $this->redirect(Team::url('/membres'))->with('error', 'Limite de membres de votre plan atteinte : passez au plan supérieur.');
        }

        $alreadyMember = (new QueryBuilder('memberships'))
            ->join('users', 'users.id', '=', 'memberships.user_id')
            ->where('memberships.tenant_id', Team::current()['id'])
            ->where('users.email', $email)
            ->count() > 0;

        if ($alreadyMember) {
            return $this->redirect(Team::url('/membres'))->with('errors', ['email' => ['Cette personne est déjà membre.']]);
        }

        $token = bin2hex(random_bytes(32));
        Invitation::query()->where('email', $email)->delete();
        Invitation::create(['email' => $email, 'role' => $data['role'], 'token_hash' => hash('sha256', $token), 'expires_at' => date('Y-m-d H:i:s', strtotime('+7 days'))]);

        Mail::to($email)->send(new InvitationMailable(Team::current()['name'], Auth::user()['name'], url("/invitations/$token")));

        return $this->redirect(Team::url('/membres'))->with('success', "Invitation envoyée à $email.");
    }

    public function cancelInvitation(string $id): Response
    {
        Invitation::find($id) ?? throw new NotFoundException();
        Invitation::forceDestroy($id);

        return $this->redirect(Team::url('/membres'))->with('success', 'Invitation annulée.');
    }

    public function updateRole(Request $request, string $id): Response
    {
        $membership = Membership::find($id) ?? throw new NotFoundException();
        $data = $this->validate($request, ['role' => 'required|in:member,admin,owner']);

        // Seul un propriétaire nomme ou retire un propriétaire ; il en reste toujours au moins un.
        if (($data['role'] === 'owner' || $membership['role'] === 'owner') && !Team::hasRole('owner')) {
            throw new HttpException(403, 'Réservé aux propriétaires de l\'organisation.');
        }

        if ($membership['role'] === 'owner' && $data['role'] !== 'owner' && Team::ownerCount() === 1) {
            return $this->redirect(Team::url('/membres'))->with('error', 'Il doit rester au moins un propriétaire.');
        }

        Membership::update($id, ['role' => $data['role']]);

        return $this->redirect(Team::url('/membres'))->with('success', 'Rôle mis à jour.');
    }

    public function remove(string $id): Response
    {
        $membership = Membership::find($id) ?? throw new NotFoundException();

        if ($membership['role'] === 'owner' && !Team::hasRole('owner')) {
            throw new HttpException(403, 'Réservé aux propriétaires de l\'organisation.');
        }

        if ($membership['role'] === 'owner' && Team::ownerCount() === 1) {
            return $this->redirect(Team::url('/membres'))->with('error', 'Il doit rester au moins un propriétaire.');
        }

        Membership::forceDestroy($id);

        return $this->redirect(Team::url('/membres'))->with('success', 'Membre retiré.');
    }

    /** Quitter l'organisation (tout membre, sauf le dernier propriétaire). */
    public function leave(): Response
    {
        $membership = Membership::query()->where('user_id', Auth::id())->first() ?? throw new NotFoundException();

        if ($membership['role'] === 'owner' && Team::ownerCount() === 1) {
            return $this->redirect(Team::url('/membres'))->with('error', 'Nommez un autre propriétaire avant de quitter l\'organisation.');
        }

        Membership::forceDestroy($membership['id']);

        return $this->redirect('/organisations?liste=1')->with('success', 'Vous avez quitté l\'organisation.');
    }
}
