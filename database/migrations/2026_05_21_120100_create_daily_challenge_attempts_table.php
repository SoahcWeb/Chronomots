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
        Schema::create('daily_challenge_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_challenge_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_session_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('score')->default(0);
            $table->string('submitted_word')->nullable();
            $table->string('submitted_solution')->nullable();
            $table->json('result_payload')->nullable();
            $table->boolean('is_perfect')->default(false);
            $table->timestamp('attempted_at');
            $table->timestamps();

            $table->unique(['daily_challenge_id', 'user_id']);
            $table->index(['daily_challenge_id', 'score']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_challenge_attempts');
    }
};
