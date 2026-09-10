<?php

namespace App\Providers;

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
    }
}
