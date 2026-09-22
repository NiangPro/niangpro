<?php

namespace App\Controllers;

use App\Jobs\SendWelcomeEmailJob;
use App\Mailables\ResetPasswordMailable;
use App\Mailables\VerifyEmailMailable;
use App\Models\PasswordResetToken;
use App\Models\User;
use Niang\Core\Auth;
use Niang\Core\Controller;
use Niang\Core\Event;
use Niang\Core\Hash;
use Niang\Core\Http\Request;
use Niang\Core\Http\Response;
use Niang\Core\Mail;
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

        $this->sendVerificationEmail($user);

        return $this->redirect('/');
    }

    private function sendVerificationEmail(array $user): void
    {
        $hours = (int) config('auth.email_verification_expire_hours', 24);
        $signedUrl = signedRoute('verification.verify', ['id' => $user['id']], $hours * 3600);

        Mail::to($user['email'])->send(new VerifyEmailMailable($signedUrl));
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

    public function showForgotPassword(): Response
    {
        return $this->view('auth/forgot-password');
    }

    /**
     * Réponse strictement identique que l'email corresponde à un compte ou non, pour ne jamais
     * révéler quels comptes existent.
     */
    public function sendResetLink(Request $request): Response
    {
        $data = $this->validate($request, ['email' => 'required|email']);

        $user = User::where('email', $data['email'])[0] ?? null;

        if ($user !== null) {
            $token = bin2hex(random_bytes(32));

            PasswordResetToken::deleteForEmail($data['email']);
            PasswordResetToken::create([
                'email' => $data['email'],
                'token_hash' => Hash::make($token),
            ]);

            $minutes = (int) config('auth.password_reset_expire_minutes', 60);
            $signedUrl = signedRoute('password.reset', ['token' => $token, 'email' => $data['email']], $minutes * 60);

            Mail::to($data['email'])->send(new ResetPasswordMailable($signedUrl));
        }

        return $this->redirect('/forgot-password')
            ->with('success', "Si un compte existe pour cet email, un lien de réinitialisation vient d'être envoyé.");
    }

    /** Protégée par ValidateSignature (voir routes/web.php) : le token et l'email viennent de l'URL signée. */
    public function showResetPassword(Request $request): Response
    {
        $action = $request->uri . ($request->query ? '?' . http_build_query($request->query) : '');

        return $this->view('auth/reset-password', ['action' => $action]);
    }

    /**
     * Protégée par ValidateSignature (voir routes/web.php) ET par le jeton en base, à usage unique.
     * Les deux sont nécessaires : la signature prouve que le lien n'a pas été altéré ni forgé,
     * le jeton en base garantit qu'il ne peut servir qu'une seule fois.
     */
    public function resetPassword(Request $request): Response
    {
        $data = $this->validate($request, ['password' => 'required|string|min:8|confirmed']);

        $token = (string) $request->param('token');
        $email = (string) $request->param('email');

        $record = $this->matchingResetToken($email, $token);

        if ($record === null) {
            return $this->redirect('/forgot-password')
                ->with('errors', ['email' => ['Ce lien de réinitialisation est invalide ou a déjà été utilisé.']]);
        }

        $user = User::where('email', $email)[0] ?? null;

        if ($user !== null) {
            User::update($user['id'], ['password' => Hash::make($data['password'])]);
        }

        // À usage unique : le jeton disparaît qu'il ait servi ou non à retrouver un utilisateur.
        PasswordResetToken::destroy($record['id']);

        return $this->redirect('/login')->with('success', 'Mot de passe réinitialisé, vous pouvez vous connecter.');
    }

    private function matchingResetToken(string $email, string $token): ?array
    {
        foreach (PasswordResetToken::where('email', $email) as $record) {
            if (Hash::check($token, $record['token_hash'])) {
                return $record;
            }
        }

        return null;
    }

    /** Protégée par ValidateSignature (voir routes/web.php) : {id} vient d'un lien signé et expirable. */
    public function verifyEmail(Request $request): Response
    {
        $id = (int) $request->param('id');
        $user = User::find($id);

        if ($user !== null && $user['email_verified_at'] === null) {
            User::update($id, ['email_verified_at' => date('Y-m-d H:i:s')]);
        }

        return $this->redirect('/login')->with('success', 'Email confirmé, merci !');
    }
}
