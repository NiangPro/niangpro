<?php

namespace App\Controllers\Admin;

use App\Models\User;
use Niang\Core\Auth;
use Niang\Core\Controller;
use Niang\Core\Hash;
use Niang\Core\Http\Request;
use Niang\Core\Http\Response;

/**
 * Paramètres : identité du site (lue dans config/site.php) et compte de l'administrateur connecté.
 * C'est ici que l'on remplace les identifiants du compte de test créé par AdminUserSeeder.
 */
class SettingsController extends Controller
{
    public function index(): Response
    {
        return $this->view('admin/settings', ['user' => Auth::user()]);
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

        User::update($id, ['name' => $data['name'], 'email' => $data['email']]);

        return $this->redirect('/admin/parametres')->with('success', 'Profil mis à jour.');
    }

    public function updatePassword(Request $request): Response
    {
        $data = $this->validate($request, [
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($data['current_password'], (string) $user['password'])) {
            return $this->redirect('/admin/parametres')
                ->with('errors', ['current_password' => ['Mot de passe actuel incorrect.']]);
        }

        User::update($user['id'], ['password' => Hash::make($data['password'])]);

        return $this->redirect('/admin/parametres')->with('success', 'Mot de passe modifié.');
    }
}
