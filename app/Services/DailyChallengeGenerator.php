<?php

namespace App\Services;

use App\Models\AgeGroup;
use App\Services\GameIntelligence\BalancedDrawService;
use App\Services\GameIntelligence\NumbersSolvabilityService;
use Carbon\CarbonInterface;

class DailyChallengeGenerator
{
    public function __construct(
        private readonly BalancedDrawService $balancedDrawService,
        private readonly NumbersSolvabilityService $numbersSolvabilityService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function generateLetters(CarbonInterface $date): array
    {
        $ageGroup = $this->challengeAgeGroup();
        $candidate = $this->balancedDrawService->generateLetters($ageGroup);
        $bestLength = (int) ($candidate->solvabilityReport->metadata['best_length'] ?? 0);

        return [
            'challenge_date' => $date->toDateString(),
            'game_type' => 'letters',
            'age_group_id' => $ageGroup->id,
            'payload' => [
                'letters' => $candidate->payload['letters'],
                'timer_seconds' => $ageGroup->letters_timer_seconds,
            ],
            'solution_payload' => [
                'best_word' => $candidate->solvabilityReport->bestWord,
                'best_length' => $bestLength,
                'perfect_score' => $bestLength * 10,
                'quality_score' => $candidate->qualityScore,
            ],
            'starts_at' => $date->copy()->startOfDay(),
            'ends_at' => $date->copy()->endOfDay(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function generateNumbers(CarbonInterface $date): array
    {
        $ageGroup = $this->challengeAgeGroup();
        $candidate = $this->balancedDrawService->generateNumbers($ageGroup);
        $targetNumber = (int) $candidate->payload['target_number'];
        $options = $this->numbersSolvabilityService->findTargetOptions($candidate->payload['numbers'], $targetNumber);
        $perfectOption = collect($options)->firstWhere('difference', 0) ?? ($options[0] ?? null);

        return [
            'challenge_date' => $date->toDateString(),
            'game_type' => 'numbers',
            'age_group_id' => $ageGroup->id,
            'payload' => [
                'numbers' => $candidate->payload['numbers'],
                'target_number' => $targetNumber,
                'timer_seconds' => $ageGroup->numbers_timer_seconds,
            ],
            'solution_payload' => [
                'perfect_score' => 100,
                'best_value' => $perfectOption['value'] ?? $targetNumber,
                'best_expression' => $perfectOption['expression'] ?? null,
                'quality_score' => $candidate->qualityScore,
            ],
            'starts_at' => $date->copy()->startOfDay(),
            'ends_at' => $date->copy()->endOfDay(),
        ];
    }

    private function challengeAgeGroup(): AgeGroup
    {
        return AgeGroup::query()
            ->where('min_age', '>=', 10)
            ->orderBy('min_age')
            ->first()
            ?? AgeGroup::query()->orderBy('min_age')->firstOrFail();
    }
}
