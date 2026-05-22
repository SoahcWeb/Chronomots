<?php

namespace App\Console\Commands;

use App\Services\DailyChallengeGenerator;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class GenerateDailyLetterChallengeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'daily-letter-challenges:generate {--date= : Challenge date in YYYY-MM-DD format}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate the production daily letters challenge for a given date.';

    public function __construct(
        private readonly DailyChallengeGenerator $dailyChallengeGenerator,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dateOption = $this->option('date');
        $challengeDate = is_string($dateOption) && $dateOption !== ''
            ? CarbonImmutable::parse($dateOption)->startOfDay()
            : CarbonImmutable::today();

        $challenge = $this->dailyChallengeGenerator->createDailyLetterChallenge($challengeDate);

        $this->info(sprintf(
            'Daily letter challenge ready for %s [%s] with %d letters and max score %d.',
            $challenge->challenge_date->toDateString(),
            $challenge->difficulty_level->value,
            count($challenge->letters),
            $challenge->max_score,
        ));

        return self::SUCCESS;
    }
}
