<?php

use Niang\Core\Database\Migration;
use Niang\Core\Database\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function ($table) {
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->bigInteger('two_factor_last_step')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function ($table) {
            $table->dropColumn('two_factor_last_step');
            $table->dropColumn('two_factor_confirmed_at');
            $table->dropColumn('two_factor_recovery_codes');
            $table->dropColumn('two_factor_secret');
        });
    }
};
