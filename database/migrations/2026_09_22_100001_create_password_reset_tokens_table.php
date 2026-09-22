<?php

use Niang\Core\Database\Expression;
use Niang\Core\Database\Migration;
use Niang\Core\Database\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('password_reset_tokens', function ($table) {
            $table->id();
            $table->string('email');
            // Jamais le jeton en clair, même logique que Hash pour les mots de passe (voir AuthController).
            $table->string('token_hash');
            $table->timestamp('created_at')->default(new Expression('CURRENT_TIMESTAMP'));
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::drop('password_reset_tokens');
    }
};
