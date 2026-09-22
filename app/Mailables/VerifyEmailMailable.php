<?php

namespace App\Mailables;

use Niang\Core\Mailable;

class VerifyEmailMailable extends Mailable
{
    public function __construct(private string $signedUrl)
    {
    }

    public function subject(): string
    {
        return 'Confirmez votre adresse email';
    }

    public function body(): string
    {
        return "Bienvenue !\n\n"
            . "Cliquez sur ce lien pour confirmer votre adresse email : {$this->signedUrl}\n\n"
            . "Si vous n'êtes pas à l'origine de cette inscription, ignorez cet email.";
    }
}
