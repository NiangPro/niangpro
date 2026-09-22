<?php

use Niang\Core\Database\Expression;
use Niang\Core\Database\Migration;
use Niang\Core\Database\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('personal_access_tokens', function ($table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('name');
            // Hash déterministe (HMAC-SHA256, voir Niang\Core\ApiToken) : recherche indexée par
            // valeur, ce qu'un hash salé façon Hash::make() ne permet pas.
            $table->string('token_hash')->unique();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('created_at')->default(new Expression('CURRENT_TIMESTAMP'));
        });
    }

    public function down(): void
    {
        Schema::drop('personal_access_tokens');
    }
};
