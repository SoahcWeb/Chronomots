<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('words', function (Blueprint $table) {
            if (! Schema::hasColumn('words', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('source');
            }

            // normalized_word is already indexed by the unique constraint from the base migration.
            $table->index('length', 'words_length_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('words', function (Blueprint $table) {
            $table->dropIndex('words_length_index');

            if (Schema::hasColumn('words', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};
