<?php

namespace App\Listeners;

use App\Mailables\VerifyEmailMailable;
use Niang\Core\Contracts\ShouldQueue;
use Niang\Core\Mail;

/**
 * ShouldQueue : l'envoi de l'email de vérification (avec son lien signé) ne doit pas bloquer la
 * requête d'inscription qui déclenche 'user.registered' — voir Event::dispatch() et
 * AppServiceProvider, où ce listener est enregistré comme classe (pas comme closure : une
 * closure resterait toujours synchrone, elle ne survivrait pas sérialisée sur la file).
 */
class SendVerificationEmailListener implements ShouldQueue
{
    public function handle(array $user): void
    {
        $hours = (int) config('auth.email_verification_expire_hours', 24);
        $signedUrl = signedRoute('verification.verify', ['id' => $user['id']], $hours * 3600);

        Mail::to($user['email'])->send(new VerifyEmailMailable($signedUrl));
    }
}
