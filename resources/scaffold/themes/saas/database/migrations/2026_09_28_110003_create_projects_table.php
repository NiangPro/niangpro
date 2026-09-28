<?php

use Niang\Core\Database\Migration;
use Niang\Core\Database\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Ressource d'exemple d'une organisation : remplacez-la par celles de votre produit.
        Schema::create('projects', function ($table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants');
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
