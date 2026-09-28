<?php

namespace App\Mailables;

use Niang\Core\Mailable;

class InvitationMailable extends Mailable
{
    public function __construct(private string $organization, private string $inviter, private string $url)
    {
    }

    public function subject(): string
    {
        return "Invitation à rejoindre {$this->organization}";
    }

    public function body(): string
    {
        return "{$this->inviter} vous invite à rejoindre « {$this->organization} ».\n\n"
            . "Acceptez l'invitation ici (valable 7 jours) : {$this->url}\n\n"
            . 'Si vous ne vous attendiez pas à cette invitation, ignorez cet email.';
    }
}
