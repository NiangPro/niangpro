<?php

namespace App\Jobs;

use Niang\Core\Job;
use Niang\Core\Log;

class SendWelcomeEmailJob extends Job
{
    public function __construct(private string $email)
    {
    }

    public function handle(): void
    {
        // Ici : appel à un vrai service d'emailing. Pour l'instant, on journalise.
        Log::info('Email de bienvenue envoyé à {email}', ['email' => $this->email]);
    }
}
