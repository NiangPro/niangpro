<?php

use Niang\Core\Database\Migration;
use Niang\Core\Database\Schema;

/** Tables du pilote QUEUE_DRIVER=database (voir config/queue.php). */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('jobs', function ($table) {
            $table->id();
            $table->string('job_id', 64)->unique();
            $table->string('queue');
            // serialize() en base64 : PostgreSQL refuse l'octet nul des propriétés privées sérialisées.
            $table->text('payload');
            $table->integer('attempts')->default(0);
            $table->bigInteger('available_at');
            $table->bigInteger('reserved_at')->nullable();
            $table->index(['queue', 'available_at']);
        });

        Schema::create('failed_jobs', function ($table) {
            $table->id();
            $table->string('job_id', 64)->unique();
            $table->string('queue');
            $table->text('payload');
            $table->text('error');
            $table->timestamp('failed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::drop('failed_jobs');
        Schema::drop('jobs');
    }
};
