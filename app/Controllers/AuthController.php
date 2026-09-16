<?php

namespace App\Controllers;

use App\Jobs\SendWelcomeEmailJob;
use App\Models\User;
use Niang\Core\Auth;
use Niang\Core\Controller;
use Niang\Core\Event;
use Niang\Core\Hash;
use Niang\Core\Http\Request;
use Niang\Core\Http\Response;
use Niang\Core\Queue;

class AuthController extends Controller
{
    public function showRegister(): Response
    {
        return $this->view('auth/register');
    }

    public function register(Request $request): Response
    {
        $data = $this->validate(
            $request,
            [
                'name' => 'required|string|min:2',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:8|confirmed',
            ],
            ['email.unique' => 'Cet email est déjà utilisé.']
        );

        $userId = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $user = User::find($userId);
        Auth::login($user);

        // Différé : ne bloque pas l'inscription sur l'envoi de l'email. Traité par `niang queue:work`.
        Queue::push(new SendWelcomeEmailJob($data['email']));

        // Découple la logique secondaire (journalisation, futurs écouteurs) du contrôleur.
        Event::dispatch('user.registered', $user);

        return $this->redirect('/');
    }

    public function showLogin(): Response
    {
        return $this->view('auth/login');
    }

    public function login(Request $request): Response
    {
        $data = $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($data['email'], $data['password'])) {
            return $this->redirect('/login')
                ->with('errors', ['email' => ['Identifiants invalides.']])
                ->with('old', ['email' => $data['email']]);
        }

        return $this->redirect('/');
    }

    public function logout(): Response
    {
        Auth::logout();
        return $this->redirect('/');
    }
}
