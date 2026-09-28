<?php

namespace App\Controllers\Api\V1;

use App\Models\User;
use App\Requests\LoginRequest;
use App\Requests\RegisterRequest;
use App\Resources\UserResource;
use Niang\Core\ApiToken;
use Niang\Core\Auth;
use Niang\Core\Controller;
use Niang\Core\Hash;
use Niang\Core\Http\Request;
use Niang\Core\Http\Response;
use Niang\Core\TwoFactor;

/** Comptes et jetons d'accès : un jeton par appareil, envoyé ensuite dans Authorization: Bearer <jeton>. */
class AuthController extends Controller
{
    /** Crée un compte et renvoie son premier jeton. */
    public function register(RegisterRequest $request): Response
    {
        $data = $request->validated();
        $id = User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => Hash::make($data['password'])]);
        $user = User::find($id);

        return $this->json([
            'token' => ApiToken::issue($user, $data['device_name']),
            'user' => (new UserResource($user))->toArray(),
        ], 201);
    }

    /** Échange email et mot de passe (et le code de double authentification s'il est activé) contre un jeton. */
    public function login(LoginRequest $request): Response
    {
        $data = $request->validated();
        $user = User::where('email', $data['email'])[0] ?? null;

        if ($user === null) {
            Hash::make($data['password']); // même durée qu'un mauvais mot de passe : ne révèle pas les comptes existants
            return $this->json(['message' => 'Identifiants invalides.'], 401);
        }

        if (!Hash::check($data['password'], $user['password'])) {
            return $this->json(['message' => 'Identifiants invalides.'], 401);
        }

        if (TwoFactor::enabled($user) && !TwoFactor::verify($user, (string) ($data['code'] ?? ''))) {
            return $this->json(['message' => 'Code de double authentification requis ou invalide.', 'two_factor' => true], 401);
        }

        return $this->json(['token' => ApiToken::issue($user, $data['device_name'])], 201);
    }

    /** Révoque le jeton utilisé pour cette requête (déconnexion de cet appareil). */
    public function logout(Request $request): Response
    {
        ApiToken::revoke(substr((string) $request->header('Authorization', ''), 7));

        return Response::html('', 204);
    }

    /** Le compte associé au jeton. */
    public function me(): Response
    {
        return (new UserResource(Auth::user()))->toResponse();
    }
}
