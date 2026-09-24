<?php

use Niang\Core\Database\Migration;
use Niang\Core\Database\Schema;

// Utilisée seulement avec SESSION_DRIVER=database (voir config/session.php) : sessions partagées
// entre plusieurs serveurs web. Sans effet avec le pilote par défaut (file).
return new class extends Migration {
    public function up(): void
    {
        Schema::create('sessions', function ($table) {
            $table->string('id', 128)->unique();
            $table->text('payload');
            $table->integer('last_activity');
            $table->index('last_activity');
        });
    }

    public function down(): void
    {
        Schema::drop('sessions');
    }
};
