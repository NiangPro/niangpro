<?php

use Niang\Core\Database\Migration;
use Niang\Core\Database\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category');
            $table->text('description');
            // Montants en centimes d'euro (entiers) : pas de flottants pour de l'argent.
            $table->integer('price_cents');
            $table->integer('old_price_cents')->nullable();
            $table->string('image')->nullable();
            $table->integer('stock')->default(0);
            $table->boolean('featured')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::drop('products');
    }
};
