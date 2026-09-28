<?php

use Niang\Core\Database\Migration;
use Niang\Core\Database\Schema;

// Utilisées seulement avec CACHE_DRIVER=database (voir config/cache.php) : cache et compteurs de
// limitation de débit partagés entre plusieurs serveurs web. Sans effet avec le pilote file.
// Pas de colonne nommée « key » : mot réservé en MySQL.
return new class extends Migration {
    public function up(): void
    {
        Schema::create('cache_entries', function ($table) {
            $table->string('cache_key', 64)->unique();
            $table->text('value');
            $table->integer('expiration')->nullable();
        });

        Schema::create('rate_limits', function ($table) {
            $table->string('limit_key', 64)->unique();
            $table->integer('attempts');
            $table->integer('reset_at');
        });
    }

    public function down(): void
    {
        Schema::drop('rate_limits');
        Schema::drop('cache_entries');
    }
};
