<?php

use Niang\Core\Database\Migration;
use Niang\Core\Database\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notes', function ($table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('title');
            $table->text('body')->nullable();
            $table->boolean('done')->default(false);
            $table->timestamps();
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
