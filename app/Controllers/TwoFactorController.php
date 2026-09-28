<?php

namespace App\Controllers;

use App\Models\User;
use Niang\Core\Auth;
use Niang\Core\Controller;
use Niang\Core\Hash;
use Niang\Core\Http\Request;
use Niang\Core\Http\Response;
use Niang\Core\TwoFactor;

/** Double authentification : saisie du code à la connexion, et page de réglage du compte. */
class TwoFactorController extends Controller
{
    public function showChallenge(): Response
    {
        if (!Auth::twoFactorPending()) {
            return $this->redirect('/login');
        }

        return $this->view('auth/two-factor-challenge');
    }

    public function challenge(Request $request): Response
    {
        if (!Auth::twoFactorPending()) {
            return $this->redirect('/login')->with('errors', ['email' => ['Délai dépassé, reconnectez-vous.']]);
        }

        if (!Auth::completeTwoFactor((string) $request->input('code', ''))) {
            return $this->redirect('/two-factor-challenge')->with('errors', ['code' => ['Code invalide.']]);
        }

        return $this->redirect(User::homePath(Auth::user()));
    }

    public function show(): Response
    {
        $user = Auth::user();

        return $this->view('auth/two-factor', [
            'enabled' => TwoFactor::enabled($user),
            'remaining' => TwoFactor::enabled($user) ? TwoFactor::remainingRecoveryCodes($user) : 0,
            'setup' => null,
        ]);
    }

    /** Affiche le secret et les codes de secours UNE fois, dans la réponse (jamais en session ni en flash). */
    public function enable(Request $request): Response
    {
        $this->confirmPassword($request);

        return $this->view('auth/two-factor', ['enabled' => false, 'remaining' => 0, 'setup' => TwoFactor::enable(Auth::user())]);
    }

    public function confirm(Request $request): Response
    {
        if (!TwoFactor::confirm(Auth::user(), (string) $request->input('code', ''))) {
            return $this->redirect('/user/two-factor')->with('errors', ['code' => ['Code invalide : recommencez l\'activation.']]);
        }

        return $this->redirect('/user/two-factor')->with('success', 'Double authentification activée.');
    }

    public function disable(Request $request): Response
    {
        $this->confirmPassword($request);
        TwoFactor::disable(Auth::user());

        return $this->redirect('/user/two-factor')->with('success', 'Double authentification désactivée.');
    }

    /** Activer ou désactiver exige le mot de passe : une session volée ne suffit pas. */
    private function confirmPassword(Request $request): void
    {
        $user = Auth::user();

        if (!Hash::check((string) $request->input('password', ''), (string) $user['password'])) {
            abort(403, 'Mot de passe incorrect.');
        }
    }
}
