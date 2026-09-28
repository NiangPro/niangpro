<?php

use Niang\Core\Database\Migration;
use Niang\Core\Database\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Une ligne par organisation (locataire).
        Schema::create('tenants', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('domain')->nullable()->unique();
            $table->string('plan')->default('free');
            $table->string('subscription_status')->nullable();   // active, canceled...
            $table->timestamp('subscription_ends_at')->nullable();
            $table->string('billing_reference')->nullable();     // identifiant chez le prestataire de paiement
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
