<?php

use Niang\Core\Database\Migration;
use Niang\Core\Database\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('comments', function ($table) {
            $table->id();
            $table->foreignId('post_id');
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::drop('comments');
    }
};
