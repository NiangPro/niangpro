<?php

use Niang\Core\Database\Migration;
use Niang\Core\Database\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function ($table) {
            $table->timestamp('email_verified_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function ($table) {
            $table->dropColumn('email_verified_at');
        });
    }
};
