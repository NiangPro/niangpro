<?php

namespace App\Controllers;

use App\Models\User;
use Niang\Core\Auth;
use Niang\Core\Controller;
use Niang\Core\Database\DB;
use Niang\Core\Hash;
use Niang\Core\Http\Request;
use Niang\Core\Http\Response;
use Niang\Core\Notification;
use Niang\Core\TwoFactor;

/**
 * Le compte de l'utilisateur connecté : profil, mot de passe, double authentification (lien vers
 * /user/two-factor) et suppression définitive.
 */
class AccountController extends Controller
{
    public function show(): Response
    {
        $user = Auth::user();

        return $this->view('account/show', ['user' => $user, 'twoFactor' => TwoFactor::enabled($user)]);
    }

    public function updateProfile(Request $request): Response
    {
        $id = (int) Auth::id();
        $data = $this->validate(
            $request,
            [
                'name' => 'required|string|min:2|max:120',
                'email' => "required|email|unique:users,email,$id",
            ],
            ['email.unique' => 'Cet email est déjà utilisé par un autre compte.']
        );

        $changes = ['name' => $data['name'], 'email' => $data['email']];

        // Nouvelle adresse : elle n'est plus vérifiée.
        if ($data['email'] !== Auth::user()['email']) {
            User::forceUpdate($id, ['email_verified_at' => null]);
        }

        User::update($id, $changes);

        return $this->redirect('/compte')->with('success', 'Profil mis à jour.');
    }

    public function updatePassword(Request $request): Response
    {
        $data = $this->validate($request, [
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($data['current_password'], (string) $user['password'])) {
            return $this->redirect('/compte')->with('errors', ['current_password' => ['Mot de passe actuel incorrect.']]);
        }

        // Les autres appareils « mémorisés » doivent se reconnecter avec le nouveau mot de passe.
        User::forceUpdate($user['id'], ['password' => Hash::make($data['password']), 'remember_token' => null]);

        return $this->redirect('/compte')->with('success', 'Mot de passe modifié.');
    }

    public function destroy(Request $request): Response
    {
        $data = $this->validate($request, ['delete_password' => 'required|string']);
        $user = Auth::user();

        if (!Hash::check($data['delete_password'], (string) $user['password'])) {
            return $this->redirect('/compte')->with('errors', ['delete_password' => ['Mot de passe incorrect.']]);
        }

        DB::transaction(function () use ($user): void {
            DB::statement('DELETE FROM personal_access_tokens WHERE user_id = ?', [$user['id']]);
            DB::statement('DELETE FROM password_reset_tokens WHERE email = ?', [$user['email']]);
            DB::statement('DELETE FROM notifications WHERE notifiable_type = ? AND notifiable_id = ?', [Notification::notifiableType(), (string) $user['id']]);
            User::forceDestroy($user['id']);
        });

        Auth::logout();

        return $this->redirect('/')->with('success', 'Votre compte a été supprimé.');
    }
}
