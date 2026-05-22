<?php

namespace Tests\Feature;

use App\Models\AgeGroup;
use App\Models\DrawStatistic;
use App\Models\DrawStatisticHistogram;
use App\Models\GameSession;
use App\Models\LetterRound;
use App\Models\User;
use App\Services\DrawStatisticsService;
use App\Services\GameIntelligence\DTOs\SolvabilityReport;
use Database\Seeders\AgeGroupSeeder;
use Database\Seeders\WordSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DrawStatisticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_records_advanced_letters_statistics_for_accepted_and_rejected_draws(): void
    {
        $this->seed(AgeGroupSeeder::class);

        $ageGroup = AgeGroup::query()->where('min_age', '>=', 10)->orderBy('min_age')->firstOrFail();
        $service = app(DrawStatisticsService::class);

        $rejectedReport = new SolvabilityReport(
            valid: false,
            solutionsCount: 1,
            bestWord: 'MOT',
            metadata: [
                'best_length' => 3,
                'average_length' => 3.0,
                'total_possible_word_length' => 3,
                'target_word_count' => 0,
            ],
        );
        $acceptedReport = new SolvabilityReport(
            valid: true,
            solutionsCount: 4,
            bestWord: 'MAISON',
            metadata: [
                'best_length' => 6,
                'average_length' => 4.25,
                'total_possible_word_length' => 17,
                'target_word_count' => 2,
            ],
        );

        $service->recordLettersDrawAttempt($ageGroup, ['B', 'C', 'D', 'F', 'G', 'A', 'E', 'L'], $rejectedReport, 55, false);
        $service->recordLettersDrawAttempt($ageGroup, ['M', 'A', 'I', 'S', 'O', 'N', 'L', 'R'], $acceptedReport, 84, true);

        $globalStatistic = DrawStatistic::query()->where('scope_key', 'letters:global')->firstOrFail();
        $ageStatistic = DrawStatistic::query()->where('scope_key', 'letters:age:'.$ageGroup->id)->firstOrFail();

        $this->assertSame(1, $globalStatistic->accepted_draws_count);
        $this->assertSame(1, $globalStatistic->rejected_draws_count);
        $this->assertSame(16, $globalStatistic->total_letters_drawn);
        $this->assertSame(5, $globalStatistic->total_possible_words);
        $this->assertSame(20, $globalStatistic->total_possible_word_length);
        $this->assertSame('4.00', (string) $globalStatistic->average_possible_word_length);
        $this->assertSame('0.5000', (string) $globalStatistic->rejection_rate);
        $this->assertSame($globalStatistic->accepted_draws_count, $ageStatistic->accepted_draws_count);
        $this->assertSame($globalStatistic->rejected_draws_count, $ageStatistic->rejected_draws_count);
        $this->assertSame(2, $globalStatistic->letter_frequency['A']);
        $this->assertSame(1, $globalStatistic->letter_frequency['M']);

        $difficultyHistogram = DrawStatisticHistogram::query()
            ->where('draw_statistic_id', $globalStatistic->id)
            ->where('metric', 'difficulty_score')
            ->get();

        $this->assertCount(2, $difficultyHistogram);
    }

    public function test_rebuild_command_recomputes_letters_statistics_from_historical_sessions(): void
    {
        $this->seed([AgeGroupSeeder::class, WordSeeder::class]);

        $ageGroup = AgeGroup::query()->where('min_age', '>=', 10)->orderBy('min_age')->firstOrFail();
        $user = User::factory()->create();
        $session = GameSession::query()->create([
            'user_id' => $user->id,
            'age_group_id' => $ageGroup->id,
            'game_type' => 'letters',
            'score' => 60,
            'status' => 'completed',
            'started_at' => now()->subMinute(),
            'expires_at' => now(),
            'completed_at' => now(),
        ]);

        LetterRound::query()->create([
            'game_session_id' => $session->id,
            'letters' => 'MAISONLR',
            'submitted_word' => 'MAISON',
            'best_word' => 'MAISON',
            'score' => 60,
        ]);

        $this->artisan('draw-statistics:rebuild')
            ->expectsOutput('Letters draw statistics rebuilt successfully.')
            ->assertSuccessful();

        $statistic = DrawStatistic::query()->where('scope_key', 'letters:age:'.$ageGroup->id)->firstOrFail();

        $this->assertSame(1, $statistic->accepted_draws_count);
        $this->assertSame(0, $statistic->rejected_draws_count);
        $this->assertGreaterThan(0, $statistic->total_possible_words);
        $this->assertNotNull($statistic->last_rebuilt_at);
    }

    public function test_report_command_displays_letters_statistics_summary(): void
    {
        $this->seed(AgeGroupSeeder::class);

        $ageGroup = AgeGroup::query()->where('min_age', '>=', 10)->orderBy('min_age')->firstOrFail();
        app(DrawStatisticsService::class)->recordLettersDrawAttempt(
            $ageGroup,
            ['M', 'A', 'I', 'S', 'O', 'N', 'L', 'R'],
            new SolvabilityReport(
                valid: true,
                solutionsCount: 4,
                bestWord: 'MAISON',
                metadata: [
                    'best_length' => 6,
                    'average_length' => 4.25,
                    'total_possible_word_length' => 17,
                    'target_word_count' => 2,
                ],
            ),
            84,
            true,
        );

        $this->artisan('draw-statistics:report')
            ->expectsTable(
                ['Scope', 'Age Group', 'Accepted', 'Rejected', 'Reject Rate', 'Avg Word Length', 'Avg Difficulty'],
                [
                    ['age_group', $ageGroup->name, 1, 0, '0.0000', '4.25', (string) DrawStatistic::query()->where('scope_key', 'letters:age:'.$ageGroup->id)->value('average_difficulty_score')],
                    ['global', 'All', 1, 0, '0.0000', '4.25', (string) DrawStatistic::query()->where('scope_key', 'letters:global')->value('average_difficulty_score')],
                ],
            )
            ->assertSuccessful();
    }
}
