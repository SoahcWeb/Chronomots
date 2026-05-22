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
        Schema::create('draw_statistics', function (Blueprint $table) {
            $table->id();
            $table->string('scope_key')->unique();
            $table->string('scope', 24);
            $table->string('game_type', 24);
            $table->foreignId('age_group_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('accepted_draws_count')->default(0);
            $table->unsignedBigInteger('rejected_draws_count')->default(0);
            $table->unsignedBigInteger('total_letters_drawn')->default(0);
            $table->unsignedBigInteger('total_possible_words')->default(0);
            $table->unsignedBigInteger('total_possible_word_length')->default(0);
            $table->unsignedBigInteger('total_difficulty_score')->default(0);
            $table->decimal('average_possible_word_length', 8, 2)->default(0);
            $table->decimal('average_difficulty_score', 8, 2)->default(0);
            $table->decimal('rejection_rate', 8, 4)->default(0);
            $table->json('letter_frequency')->nullable();
            $table->timestamp('last_rebuilt_at')->nullable();
            $table->timestamps();

            $table->index(['game_type', 'scope']);
            $table->index(['game_type', 'age_group_id']);
        });

        Schema::create('draw_statistic_histograms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('draw_statistic_id')->constrained()->cascadeOnDelete();
            $table->string('metric', 64);
            $table->string('bucket', 32);
            $table->unsignedBigInteger('entries_count')->default(0);
            $table->timestamps();

            $table->unique(['draw_statistic_id', 'metric', 'bucket']);
            $table->index(['metric', 'bucket']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('draw_statistic_histograms');
        Schema::dropIfExists('draw_statistics');
    }
};
