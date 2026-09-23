<?php

use Niang\Core\Database\Migration;
use Niang\Core\Database\Schema;

/**
 * Rôle de l'utilisateur : « admin » ouvre l'espace d'administration (/admin), tout autre valeur
 * est un compte ordinaire (client de la boutique, lecteur du blog). Voir App\Models\User::isAdmin().
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function ($table) {
            $table->string('role')->default('user');
        });
    }

    public function down(): void
    {
        Schema::table('users', function ($table) {
            $table->dropColumn('role');
        });
    }
};
