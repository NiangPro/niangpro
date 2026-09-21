<?php

use Niang\Core\Database\DB;
use Niang\Core\Database\Migration;
use Niang\Core\Database\Schema;

/**
 * Enrichit la table posts de démonstration pour un vrai blog : URL lisible (slug), chapeau,
 * catégorie et auteur. Toutes les colonnes sont facultatives : les articles créés avant cette
 * migration (ou par PostController::store) continuent d'exister.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('posts', function ($table) {
            $table->string('slug')->nullable();
            $table->text('excerpt')->nullable();
            $table->string('category')->nullable();
            $table->string('author')->nullable();
            $table->index('slug');
        });
    }

    public function down(): void
    {
        // SQLite refuse de supprimer une colonne encore indexée : l'index part d'abord.
        $driver = DB::connection()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        DB::statement($driver === 'mysql' ? 'DROP INDEX posts_slug_index ON posts' : 'DROP INDEX posts_slug_index');

        Schema::table('posts', function ($table) {
            $table->dropColumn('slug');
            $table->dropColumn('excerpt');
            $table->dropColumn('category');
            $table->dropColumn('author');
        });
    }
};
