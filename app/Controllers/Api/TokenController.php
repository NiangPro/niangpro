<?php

namespace App\Controllers\Api;

use App\Models\User;
use Niang\Core\ApiToken;
use Niang\Core\Controller;
use Niang\Core\Hash;
use Niang\Core\Http\Request;
use Niang\Core\Http\Response;

/**
 * Émission d'un jeton API — minimal, sans UI autour (voir Niang\Core\ApiToken et
 * App\Middleware\AuthenticateWithToken). Ne touche jamais la session : un client API n'en a pas.
 */
class TokenController extends Controller
{
    public function store(Request $request): Response
    {
        $data = $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'required|string',
        ]);

        $user = User::where('email', $data['email'])[0] ?? null;

        if ($user === null || !Hash::check($data['password'], $user['password'])) {
            return $this->json(['message' => 'Identifiants invalides.'], 401);
        }

        return $this->json(['token' => ApiToken::issue($user, $data['device_name'])], 201);
    }
}
