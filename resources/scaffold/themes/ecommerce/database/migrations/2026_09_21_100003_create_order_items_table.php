<?php

use Niang\Core\Database\Migration;
use Niang\Core\Database\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('order_items', function ($table) {
            $table->id();
            $table->integer('order_id');
            $table->integer('product_id')->nullable();
            // Copiés à l'achat : une commande passée ne change jamais si le produit est modifié ou supprimé.
            $table->string('name');
            $table->integer('unit_price_cents');
            $table->integer('quantity');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::drop('order_items');
    }
};
