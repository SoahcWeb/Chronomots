<?php

namespace Tests\Feature;

use App\Models\AgeGroup;
use App\Models\DailyChallenge;
use App\Models\DailyChallengeAttempt;
use App\Models\GameSession;
use App\Models\User;
use App\Models\Word;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DailyChallengeTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_challenge_index_displays_today_and_history(): void
    {
        $user = User::factory()->create();
        $ageGroup = AgeGroup::query()->create([
            'name' => '10-13 ans',
            'min_age' => 10,
            'max_age' => 13,
            'description' => 'Mode entraînement',
            'letters_timer_seconds' => 60,
            'numbers_timer_seconds' => 90,
        ]);

        $todayLetters = DailyChallenge::query()->create([
            'public_id' => (string) Str::ulid(),
            'challenge_date' => now()->toDateString(),
            'game_type' => 'letters',
            'age_group_id' => $ageGroup->id,
            'payload' => ['letters' => ['M', 'O', 'T', 'S'], 'timer_seconds' => 60],
            'solution_payload' => ['perfect_score' => 40, 'best_length' => 4],
            'starts_at' => now()->startOfDay(),
            'ends_at' => now()->endOfDay(),
        ]);

        DailyChallenge::query()->create([
            'public_id' => (string) Str::ulid(),
            'challenge_date' => now()->toDateString(),
            'game_type' => 'numbers',
            'age_group_id' => $ageGroup->id,
            'payload' => ['numbers' => [100, 25, 5, 4, 3, 2], 'target_number' => 130, 'timer_seconds' => 90],
            'solution_payload' => ['perfect_score' => 100, 'best_value' => 130],
            'starts_at' => now()->startOfDay(),
            'ends_at' => now()->endOfDay(),
        ]);

        $pastChallenge = DailyChallenge::query()->create([
            'public_id' => (string) Str::ulid(),
            'challenge_date' => now()->subDay()->toDateString(),
            'game_type' => 'letters',
            'age_group_id' => $ageGroup->id,
            'payload' => ['letters' => ['J', 'O', 'U', 'R'], 'timer_seconds' => 60],
            'solution_payload' => ['perfect_score' => 40, 'best_length' => 4],
            'starts_at' => now()->subDay()->startOfDay(),
            'ends_at' => now()->subDay()->endOfDay(),
        ]);

        DailyChallengeAttempt::query()->create([
            'daily_challenge_id' => $pastChallenge->id,
            'user_id' => $user->id,
            'score' => 40,
            'submitted_word' => 'JOUR',
            'result_payload' => ['perfect_score' => 40],
            'is_perfect' => true,
            'attempted_at' => now()->subDay()->setHour(12),
        ]);

        DailyChallengeAttempt::query()->create([
            'daily_challenge_id' => $todayLetters->id,
            'user_id' => User::factory()->create()->id,
            'score' => 30,
            'submitted_word' => 'MOT',
            'result_payload' => ['perfect_score' => 40],
            'is_perfect' => false,
            'attempted_at' => now()->setHour(9),
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('daily-challenges.index'));

        $response
            ->assertOk()
            ->assertSee('Défis quotidiens')
            ->assertSee('Défi lettres')
            ->assertSee('Défi chiffres')
            ->assertSee('Historique des défis')
            ->assertSee('Badge parfait du jour');
    }

    public function test_daily_letters_challenge_creates_one_attempt_only(): void
    {
        $user = User::factory()->create();
        $ageGroup = AgeGroup::query()->create([
            'name' => '10-13 ans',
            'min_age' => 10,
            'max_age' => 13,
            'description' => 'Mode entraînement',
            'letters_timer_seconds' => 60,
            'numbers_timer_seconds' => 90,
        ]);

        Word::query()->create([
            'word' => 'mots',
            'normalized_word' => 'MOTS',
            'length' => 4,
            'frequency' => 95,
            'age_level' => '7-9',
        ]);

        $challenge = DailyChallenge::query()->create([
            'public_id' => (string) Str::ulid(),
            'challenge_date' => now()->toDateString(),
            'game_type' => 'letters',
            'age_group_id' => $ageGroup->id,
            'payload' => ['letters' => ['M', 'O', 'T', 'S'], 'timer_seconds' => 60],
            'solution_payload' => ['perfect_score' => 40, 'best_length' => 4],
            'starts_at' => now()->startOfDay(),
            'ends_at' => now()->endOfDay(),
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('daily-challenges.submit', $challenge), [
                'submitted_word' => 'mots',
            ]);

        $response
            ->assertOk()
            ->assertSee('Score parfait')
            ->assertSee('Badge parfait du jour');

        $this->assertDatabaseHas('game_sessions', [
            'user_id' => $user->id,
            'daily_challenge_id' => $challenge->id,
            'game_type' => 'letters',
            'score' => 40,
        ]);

        $this->assertDatabaseHas('daily_challenge_attempts', [
            'daily_challenge_id' => $challenge->id,
            'user_id' => $user->id,
            'score' => 40,
            'submitted_word' => 'MOTS',
            'is_perfect' => true,
        ]);

        $secondResponse = $this
            ->actingAs($user)
            ->post(route('daily-challenges.submit', $challenge), [
                'submitted_word' => 'mots',
            ]);

        $secondResponse->assertRedirect(route('daily-challenges.show', $challenge));

        $this->assertDatabaseCount('daily_challenge_attempts', 1);
    }

    public function test_daily_numbers_challenge_shows_daily_leaderboard(): void
    {
        $user = User::factory()->create(['name' => 'Alice']);
        $otherUser = User::factory()->create(['name' => 'Bastien']);
        $ageGroup = AgeGroup::query()->create([
            'name' => '14+',
            'min_age' => 14,
            'max_age' => null,
            'description' => 'Mode expert',
            'letters_timer_seconds' => 45,
            'numbers_timer_seconds' => 60,
        ]);

        $challenge = DailyChallenge::query()->create([
            'public_id' => (string) Str::ulid(),
            'challenge_date' => now()->toDateString(),
            'game_type' => 'numbers',
            'age_group_id' => $ageGroup->id,
            'payload' => ['numbers' => [100, 25, 5, 4, 3, 2], 'target_number' => 130, 'timer_seconds' => 60],
            'solution_payload' => ['perfect_score' => 100, 'best_value' => 130],
            'starts_at' => now()->startOfDay(),
            'ends_at' => now()->endOfDay(),
        ]);

        $otherSession = GameSession::query()->create([
            'user_id' => $otherUser->id,
            'age_group_id' => $ageGroup->id,
            'daily_challenge_id' => $challenge->id,
            'game_type' => 'numbers',
            'score' => 50,
            'status' => 'completed',
            'started_at' => now()->subMinutes(30),
            'completed_at' => now()->subMinutes(29),
        ]);

        DailyChallengeAttempt::query()->create([
            'daily_challenge_id' => $challenge->id,
            'user_id' => $otherUser->id,
            'game_session_id' => $otherSession->id,
            'score' => 50,
            'submitted_solution' => '100 + 25',
            'result_payload' => ['difference' => 5, 'perfect_score' => 100],
            'is_perfect' => false,
            'attempted_at' => now()->subMinutes(29),
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('daily-challenges.submit', $challenge), [
                'submitted_solution' => '100 + 25 + 5',
            ]);

        $response
            ->assertOk()
            ->assertSee('Classement du jour')
            ->assertSee('Alice')
            ->assertSee('Bastien')
            ->assertSee('Badge parfait du jour');
    }
}
