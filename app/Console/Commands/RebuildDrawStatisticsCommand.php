<?php

namespace App\Console\Commands;

use App\Services\DrawStatisticsRebuilder;
use Illuminate\Console\Command;

class RebuildDrawStatisticsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'draw-statistics:rebuild';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rebuild aggregated draw statistics for the letters engine.';

    public function __construct(
        private readonly DrawStatisticsRebuilder $drawStatisticsRebuilder,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->drawStatisticsRebuilder->rebuildLettersStatistics();

        $this->info('Letters draw statistics rebuilt successfully.');

        return self::SUCCESS;
    }
}
