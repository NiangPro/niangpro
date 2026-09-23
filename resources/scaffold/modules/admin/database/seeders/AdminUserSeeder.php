<?php

use App\Models\User;
use Niang\Core\Database\Seeder;
use Niang\Core\Hash;

/**
 * Compte administrateur de TEST, créé par `niang db:seed` (DatabaseSeeder l'appelle) ou seul avec
 * `niang db:seed AdminUser`. Identifiants par défaut : admin@example.com / admin1234, réglables
 * avec ADMIN_EMAIL et ADMIN_PASSWORD dans .env.
 *
 * À changer avant toute mise en ligne : /admin/parametres permet de modifier email et mot de passe.
 * Relancer le seeder ne touche pas à un compte existant (ni à son mot de passe déjà changé).
 */
return new class extends Seeder {
    public const DEFAULT_EMAIL = 'admin@example.com';

    public const DEFAULT_PASSWORD = 'admin1234';

    public function run(): void
    {
        $email = (string) env('ADMIN_EMAIL', self::DEFAULT_EMAIL);

        if (User::query()->where('email', $email)->first()) {
            return;
        }

        User::create([
            'name' => 'Administrateur',
            'email' => $email,
            'password' => Hash::make((string) env('ADMIN_PASSWORD', self::DEFAULT_PASSWORD)),
            'role' => 'admin',
            'email_verified_at' => date('Y-m-d H:i:s'),
        ]);
    }
};
