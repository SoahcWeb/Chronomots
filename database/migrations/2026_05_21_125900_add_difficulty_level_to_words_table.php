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
            if (! Schema::hasColumn('words', 'difficulty_level')) {
                $table->unsignedTinyInteger('difficulty_level')->nullable()->after('frequency');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('words', 'difficulty_level')) {
            return;
        }

        Schema::table('words', function (Blueprint $table) {
            $table->dropColumn('difficulty_level');
        });
    }
};
