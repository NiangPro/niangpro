<?php

namespace App\Controllers;

use App\Models\User;
use Niang\Core\Auth;
use Niang\Core\Controller;
use Niang\Core\Exceptions\OAuthException;
use Niang\Core\Hash;
use Niang\Core\Http\Request;
use Niang\Core\Http\Response;
use Niang\Core\OAuth;

/** Connexion avec Google ou GitHub (voir config/oauth.php). */
class SocialAuthController extends Controller
{
    public function redirectToProvider(string $provider): Response
    {
        if (!in_array($provider, OAuth::configured(), true)) {
            abort(404);
        }

        return OAuth::redirect($provider);
    }

    public function callback(string $provider, Request $request): Response
    {
        if (!in_array($provider, OAuth::configured(), true)) {
            abort(404);
        }

        try {
            $profile = OAuth::user($provider, $request);
        } catch (OAuthException $e) {
            return $this->redirect('/login')->with('errors', ['email' => [$e->getMessage()]]);
        }

        // Seul un email certifié par le fournisseur peut désigner un compte : sinon, n'importe qui
        // créant chez lui une adresse non vérifiée identique à celle d'un de nos comptes s'y connecterait.
        if ($profile['email'] === null || !$profile['email_verified']) {
            return $this->redirect('/login')->with('errors', ['email' => ['Votre adresse email doit être vérifiée chez ' . ucfirst($provider) . '.']]);
        }

        $user = User::where('email', $profile['email'])[0] ?? null;

        if ($user === null) {
            $id = User::create([
                'name' => $profile['name'] ?? $profile['email'],
                'email' => $profile['email'],
                // Mot de passe aléatoire inconnu de tous : « mot de passe oublié » permet d'en choisir un.
                'password' => Hash::make(bin2hex(random_bytes(32))),
            ]);
            User::forceUpdate($id, ['email_verified_at' => date('Y-m-d H:i:s')]);
            $user = User::find($id);
        }

        Auth::loginOrRequireTwoFactor($user);

        if (Auth::twoFactorPending()) {
            return $this->redirect('/two-factor-challenge');
        }

        return $this->redirect(User::homePath(Auth::user()));
    }
}
