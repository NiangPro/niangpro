<?php

use Niang\Core\Database\Migration;
use Niang\Core\Database\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('memberships', function ($table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants');
            $table->foreignId('user_id')->constrained();
            $table->string('role')->default('member');   // owner, admin, member
            $table->timestamps();
            $table->unique(['tenant_id', 'user_id']);
        });

        Schema::create('invitations', function ($table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants');
            $table->string('email');
            $table->string('role')->default('member');
            $table->string('token_hash')->unique();   // jamais le jeton en clair
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitations');
        Schema::dropIfExists('memberships');
    }
};
