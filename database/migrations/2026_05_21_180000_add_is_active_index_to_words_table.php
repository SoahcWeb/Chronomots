<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if ($this->hasIndex('words', 'words_is_active_index')) {
            return;
        }

        Schema::table('words', function (Blueprint $table) {
            $table->index('is_active', 'words_is_active_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! $this->hasIndex('words', 'words_is_active_index')) {
            return;
        }

        Schema::table('words', function (Blueprint $table) {
            $table->dropIndex('words_is_active_index');
        });
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        return match (DB::getDriverName()) {
            'sqlite' => collect(DB::select("PRAGMA index_list('{$table}')"))
                ->contains(fn (object $index): bool => ($index->name ?? null) === $indexName),
            'mysql' => collect(DB::select("SHOW INDEX FROM `{$table}`"))
                ->contains(fn (object $index): bool => ($index->Key_name ?? null) === $indexName),
            default => false,
        };
    }
};
