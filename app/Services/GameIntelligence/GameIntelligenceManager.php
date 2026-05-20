<?php

namespace App\Services\GameIntelligence;

use App\Models\AgeGroup;
use Illuminate\Support\Str;

class GameIntelligenceManager
{
    public function __construct(
        private readonly BalancedDrawService $balancedDrawService,
    ) {
    }

    /**
     * Build the letters payload expected by the current controllers/session flow.
     *
     * @return array{draw_id: string, letters: array<int, string>, started_at: string}
     */
    public function createLettersDraw(AgeGroup $ageGroup): array
    {
        $candidate = $this->balancedDrawService->generateLetters($ageGroup);

        return [
            'draw_id' => Str::uuid()->toString(),
            'letters' => $candidate->payload['letters'],
            'started_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Build the numbers payload expected by the current controllers/session flow.
     *
     * @return array{draw_id: string, numbers: array<int, int>, target_number: int, started_at: string}
     */
    public function createNumbersDraw(AgeGroup $ageGroup): array
    {
        $candidate = $this->balancedDrawService->generateNumbers($ageGroup);

        return [
            'draw_id' => Str::uuid()->toString(),
            'numbers' => $candidate->payload['numbers'],
            'target_number' => $candidate->payload['target_number'],
            'started_at' => now()->toDateTimeString(),
        ];
    }
}
