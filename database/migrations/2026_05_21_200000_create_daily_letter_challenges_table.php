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
        Schema::create('daily_letter_challenges', function (Blueprint $table) {
            $table->id();
            $table->date('challenge_date')->unique();
            $table->string('difficulty_level', 16);
            $table->foreignId('age_group_id')->constrained()->cascadeOnDelete();
            $table->json('letters');
            $table->string('solution_word')->nullable();
            $table->unsignedInteger('max_score')->default(0);
            $table->unsignedTinyInteger('quality_score')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->timestamp('generated_at');
            $table->timestamps();

            $table->index(['difficulty_level', 'challenge_date']);
            $table->index(['age_group_id', 'challenge_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_letter_challenges');
    }
};
