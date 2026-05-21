<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

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

            if (! Schema::hasColumn('words', 'source')) {
                $table->string('source')->nullable()->after('difficulty_level');
            }
        });

        DB::table('words')
            ->orderBy('id')
            ->chunkById(1000, function ($words): void {
                foreach ($words as $word) {
                    $sourceWord = Str::of((string) $word->word)
                        ->replace(['’', '`', '´'], "'")
                        ->replaceMatches('/\s+/', ' ')
                        ->trim()
                        ->lower()
                        ->toString();

                    $normalizedWord = Str::of($sourceWord)
                        ->ascii()
                        ->lower()
                        ->replaceMatches('/[^a-z]/', '')
                        ->toString();

                    DB::table('words')
                        ->where('id', $word->id)
                        ->update([
                            'word' => $sourceWord,
                            'normalized_word' => $normalizedWord,
                            'length' => strlen($normalizedWord),
                        ]);
                }
            });

        Schema::table('words', function (Blueprint $table) {
            $table->index(['length', 'age_level'], 'words_length_age_level_index');
            $table->index('difficulty_level', 'words_difficulty_level_index');
            $table->index('source', 'words_source_index');
            $table->index(['source', 'length'], 'words_source_length_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('words', function (Blueprint $table) {
            $table->dropIndex('words_length_age_level_index');
            $table->dropIndex('words_difficulty_level_index');
            $table->dropIndex('words_source_index');
            $table->dropIndex('words_source_length_index');
            $table->dropColumn(['difficulty_level', 'source']);
        });
    }
};
