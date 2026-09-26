<?php

use Niang\Core\Database\Migration;
use Niang\Core\Database\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notifications', function ($table) {
            $table->id();
            $table->string('notifiable_type');
            // Texte plutôt qu'entier : accepte aussi un destinataire identifié par un UUID.
            $table->string('notifiable_id', 64);
            $table->string('type');
            $table->json('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['notifiable_type', 'notifiable_id']);
        });
    }

    public function down(): void
    {
        Schema::drop('notifications');
    }
};
