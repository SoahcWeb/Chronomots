<?php

namespace Tests\Feature;

use App\Enums\DifficultyLevel;
use App\Models\DailyLetterChallenge;
use App\Services\DailyChallengeGenerator;
use Carbon\Carbon;
use Database\Seeders\AgeGroupSeeder;
use Database\Seeders\WordSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DailyLetterChallengeGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_and_stores_one_daily_letter_challenge_per_day(): void
    {
        $this->seed([AgeGroupSeeder::class, WordSeeder::class]);

        $generator = app(DailyChallengeGenerator::class);
        $date = now()->startOfDay();

        $firstChallenge = $generator->createDailyLetterChallenge($date);
        $secondChallenge = $generator->createDailyLetterChallenge($date);

        $this->assertTrue($firstChallenge->is($secondChallenge));
        $this->assertDatabaseCount('daily_letter_challenges', 1);
        $this->assertCount(count($firstChallenge->letters), $secondChallenge->letters);
        $this->assertGreaterThan(0, $firstChallenge->max_score);
        $this->assertNotEmpty($firstChallenge->letters);
        $this->assertNotNull($firstChallenge->difficulty_level);
    }

    public function test_daily_letter_challenge_payload_contains_a_solution_and_quality_metadata(): void
    {
        $this->seed([AgeGroupSeeder::class, WordSeeder::class]);

        $generator = app(DailyChallengeGenerator::class);
        $challenge = $generator->createDailyLetterChallenge(now()->startOfDay());

        $this->assertInstanceOf(DifficultyLevel::class, $challenge->difficulty_level);
        $this->assertNotNull($challenge->solution_word);
        $this->assertGreaterThan(0, $challenge->max_score);
        $this->assertGreaterThanOrEqual(0, $challenge->quality_score);
        $this->assertArrayHasKey('best_length', $challenge->metadata);
        $this->assertArrayHasKey('solutions_count', $challenge->metadata);
        $this->assertArrayHasKey('timer_seconds', $challenge->metadata);
    }

    public function test_difficulty_progresses_deterministically_during_the_week(): void
    {
        $generator = app(DailyChallengeGenerator::class);

        $this->assertSame(DifficultyLevel::EASY, $generator->difficultyLevelForDate(Carbon::parse('2026-05-25')));
        $this->assertSame(DifficultyLevel::NORMAL, $generator->difficultyLevelForDate(Carbon::parse('2026-05-26')));
        $this->assertSame(DifficultyLevel::HARD, $generator->difficultyLevelForDate(Carbon::parse('2026-05-28')));
        $this->assertSame(DifficultyLevel::EXPERT, $generator->difficultyLevelForDate(Carbon::parse('2026-05-30')));
    }

    public function test_artisan_command_generates_the_daily_letter_challenge_idempotently(): void
    {
        $this->seed([AgeGroupSeeder::class, WordSeeder::class]);

        $date = '2026-05-30';

        $this->artisan('daily-letter-challenges:generate', ['--date' => $date])
            ->expectsOutputToContain('Daily letter challenge ready for '.$date)
            ->assertSuccessful();

        $this->artisan('daily-letter-challenges:generate', ['--date' => $date])
            ->expectsOutputToContain('Daily letter challenge ready for '.$date)
            ->assertSuccessful();

        $this->assertDatabaseCount('daily_letter_challenges', 1);
        $this->assertDatabaseHas('daily_letter_challenges', [
            'challenge_date' => $date,
        ]);

        $challenge = DailyLetterChallenge::query()->sole();

        $this->assertSame($date, $challenge->challenge_date->toDateString());
        $this->assertSame(
            $date,
            DB::table('daily_letter_challenges')->value('challenge_date'),
        );
    }

    public function test_daily_letter_generation_command_is_scheduled_for_midnight(): void
    {
        $events = app(Schedule::class)->events();
        $matchingEvent = collect($events)->first(
            fn ($event) => str_contains((string) $event->command, 'daily-letter-challenges:generate'),
        );

        $this->assertNotNull($matchingEvent);
        $this->assertSame('0 0 * * *', $matchingEvent->expression);
    }
}
