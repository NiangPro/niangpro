<?php

namespace Niang\Core;

/**
 * Repose sur la convention app/Models/User.php. Pour un autre modèle : Auth::useModel(MyUser::class).
 * Volontairement une simple classe statique (pas de "guards" multiples façon Laravel) : un site a une
 * seule notion d'utilisateur connecté dans l'immense majorité des cas.
 */
class Auth
{
    private const SESSION_KEY = '_auth_user_id';

    private static string $model = 'App\\Models\\User';

    /**
     * Utilisateur résolu par jeton API (Bearer), pour la durée d'une requête — voir
     * App\Middleware\AuthenticateWithToken et Niang\Core\ApiToken. Prioritaire sur la session
     * dans id()/user() : une requête /api/* authentifiée par jeton ne doit pas hériter d'une
     * session de navigateur par ailleurs active dans le même process. Le middleware la
     * réinitialise dans un finally après chaque requête, jamais deux requêtes n'en héritent.
     */
    private static ?array $tokenUser = null;

    public static function useModel(string $model): void
    {
        self::$model = $model;
    }

    /** Le modèle utilisateur courant (App\Models\User par défaut, ou celui passé à useModel()). */
    public static function model(): string
    {
        return self::$model;
    }

    /** @internal utilisé par App\Middleware\AuthenticateWithToken, jamais appelé directement par une application. */
    public static function resolveViaToken(?array $user): void
    {
        self::$tokenUser = $user;
    }

    public static function attempt(string $email, string $password, string $emailField = 'email', string $passwordField = 'password'): bool
    {
        $model = self::$model;
        $user = $model::where($emailField, $email)[0] ?? null;

        if (!$user || !Hash::check($password, $user[$passwordField])) {
            return false;
        }

        self::login($user);
        return true;
    }

    public static function login(array $user, string $key = 'id'): void
    {
        Session::regenerate();
        Session::put(self::SESSION_KEY, $user[$key]);
    }

    public static function logout(): void
    {
        Session::forget(self::SESSION_KEY);
        Session::regenerate();
    }

    public static function id(): int|string|null
    {
        if (self::$tokenUser !== null) {
            return self::$tokenUser['id'] ?? null;
        }

        return Session::get(self::SESSION_KEY);
    }

    public static function check(): bool
    {
        return self::id() !== null;
    }

    public static function guest(): bool
    {
        return !self::check();
    }

    public static function user(): ?array
    {
        if (self::$tokenUser !== null) {
            return self::$tokenUser;
        }

        $id = self::id();

        if ($id === null) {
            return null;
        }

        $model = self::$model;
        return $model::find($id);
    }
}
