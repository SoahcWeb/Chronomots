<?php

namespace App\Services;

use App\Models\DrawStatistic;
use App\Models\DrawStatisticHistogram;
use App\Models\GameSession;
use App\Services\GameIntelligence\AgeDifficultyProfileService;
use App\Services\GameIntelligence\LettersSolvabilityService;
use Illuminate\Support\Facades\DB;

class DrawStatisticsRebuilder
{
    public function __construct(
        private readonly AgeDifficultyProfileService $ageDifficultyProfileService,
        private readonly DrawStatisticsService $drawStatisticsService,
        private readonly LettersSolvabilityService $lettersSolvabilityService,
    ) {
    }

    public function rebuildLettersStatistics(): void
    {
        DB::transaction(function (): void {
            DrawStatisticHistogram::query()
                ->whereIn('draw_statistic_id', DrawStatistic::query()->where('game_type', 'letters')->pluck('id'))
                ->delete();

            DrawStatistic::query()->where('game_type', 'letters')->delete();
        });

        GameSession::query()
            ->with(['ageGroup', 'letterRounds'])
            ->where('game_type', 'letters')
            ->where('status', 'completed')
            ->chunkById(100, function ($sessions): void {
                foreach ($sessions as $session) {
                    $letterRound = $session->letterRounds->first();

                    if ($session->ageGroup === null || $letterRound === null || $letterRound->letters === null) {
                        continue;
                    }

                    $letters = str_split((string) $letterRound->letters);
                    $profile = $this->ageDifficultyProfileService->forLetters($session->ageGroup);
                    $report = $this->lettersSolvabilityService->analyze($letters, $session->ageGroup, $profile);

                    $this->drawStatisticsService->recordLettersDrawAttempt(
                        $session->ageGroup,
                        $letters,
                        $report,
                        (int) $session->score,
                        true,
                    );
                }
            });

        DrawStatistic::query()
            ->where('game_type', 'letters')
            ->update(['last_rebuilt_at' => now()]);
    }
}
