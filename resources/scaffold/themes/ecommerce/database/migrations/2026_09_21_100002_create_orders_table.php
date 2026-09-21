<?php

use Niang\Core\Database\Migration;
use Niang\Core\Database\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function ($table) {
            $table->id();
            // Null pour une commande passée sans compte.
            $table->integer('user_id')->nullable();
            $table->string('reference')->unique();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('address');
            $table->string('postal_code');
            $table->string('city');
            $table->integer('subtotal_cents');
            $table->integer('shipping_cents');
            $table->integer('total_cents');
            // pending (en attente de paiement) | paid | shipped | cancelled
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::drop('orders');
    }
};
