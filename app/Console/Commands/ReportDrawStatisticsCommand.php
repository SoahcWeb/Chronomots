<?php

namespace App\Console\Commands;

use App\Services\DrawStatisticsService;
use Illuminate\Console\Command;

class ReportDrawStatisticsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'draw-statistics:report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display a summary report of aggregated draw statistics.';

    public function __construct(
        private readonly DrawStatisticsService $drawStatisticsService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $statistics = $this->drawStatisticsService->allLettersStatistics();

        if ($statistics->isEmpty()) {
            $this->warn('No letters draw statistics are available yet.');

            return self::SUCCESS;
        }

        $this->table(
            ['Scope', 'Age Group', 'Accepted', 'Rejected', 'Reject Rate', 'Avg Word Length', 'Avg Difficulty'],
            $statistics->map(fn ($statistic) => [
                $statistic->scope,
                $statistic->ageGroup?->name ?? 'All',
                $statistic->accepted_draws_count,
                $statistic->rejected_draws_count,
                $statistic->rejection_rate,
                $statistic->average_possible_word_length,
                $statistic->average_difficulty_score,
            ])->all(),
        );

        return self::SUCCESS;
    }
}
