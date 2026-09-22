<?php

namespace App\Mailables;

use Niang\Core\Mailable;

class ResetPasswordMailable extends Mailable
{
    public function __construct(private string $signedUrl)
    {
    }

    public function subject(): string
    {
        return 'Réinitialisation de votre mot de passe';
    }

    public function body(): string
    {
        return "Vous avez demandé la réinitialisation de votre mot de passe.\n\n"
            . "Cliquez sur ce lien pour choisir un nouveau mot de passe : {$this->signedUrl}\n\n"
            . "Si vous n'êtes pas à l'origine de cette demande, ignorez cet email.";
    }
}
