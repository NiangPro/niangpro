<?php

use Niang\Core\Database\Migration;
use Niang\Core\Database\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('post_tag', function ($table) {
            $table->foreignId('post_id');
            $table->foreignId('tag_id');
        });
    }

    public function down(): void
    {
        Schema::drop('post_tag');
    }
};
