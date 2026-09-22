<?php

namespace App\Providers;

use App\Listeners\SendVerificationEmailListener;
use Niang\Core\Event;
use Niang\Core\Log;
use Niang\Core\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen('user.registered', function (array $user): void {
            Log::info('Nouvel utilisateur inscrit : {email}', ['email' => $user['email']]);
        });

        // Enregistré comme classe (ShouldQueue) plutôt que comme closure : différé sur Queue,
        // ne bloque pas la requête d'inscription (voir SendVerificationEmailListener).
        Event::listen('user.registered', SendVerificationEmailListener::class);
    }
}
