<?php

namespace App\Controllers\Admin;

use App\Models\User;
use Niang\Core\Auth;
use Niang\Core\Controller;
use Niang\Core\Hash;
use Niang\Core\Http\Request;
use Niang\Core\Http\Response;

/**
 * Équipe du blog : pas d'inscription publique, les comptes sont créés ici. Un administrateur peut
 * donner ou retirer l'accès à l'administration — jamais à lui-même, pour ne pas s'enfermer dehors.
 */
class UserController extends Controller
{
    public function index(): Response
    {
        return $this->view('admin/users', [
            'users' => User::query()->orderBy('id')->get(),
            'currentId' => (int) Auth::id(),
        ]);
    }

    public function store(Request $request): Response
    {
        $data = $this->validate(
            $request,
            [
                'name' => 'required|string|min:2|max:120',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:8',
                'role' => 'required|in:admin,user',
            ],
            ['email.unique' => 'Cet email est déjà utilisé.'],
            ['name' => 'nom', 'password' => 'mot de passe', 'role' => 'rôle']
        );

        // forceCreate : `role` et `email_verified_at` sont hors de $fillable ; ici un administrateur
        // les attribue, après validation (role: in:admin,user).
        User::forceCreate([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'email_verified_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->redirect('/admin/utilisateurs')->with('success', "Compte de {$data['name']} créé.");
    }

    public function updateRole(Request $request, string $id): Response
    {
        $user = User::find((int) $id) ?? abort(404, 'Utilisateur introuvable.');
        $data = $this->validate($request, ['role' => 'required|in:admin,user']);

        if ((int) $user['id'] === (int) Auth::id()) {
            return $this->redirect('/admin/utilisateurs')->with('error', 'Vous ne pouvez pas modifier votre propre rôle.');
        }

        User::forceUpdate($user['id'], ['role' => $data['role']]); // décision d'un admin, validée ci-dessus

        return $this->redirect('/admin/utilisateurs')->with('success', $data['role'] === 'admin'
            ? "{$user['name']} a maintenant accès à l'administration."
            : "{$user['name']} n'a plus accès à l'administration.");
    }
}
