<?php

namespace App\Services;

use App\Enums\DifficultyLevel;
use App\Models\AgeGroup;
use App\Models\DailyLetterChallenge;
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

    public function createDailyLetterChallenge(CarbonInterface $date): DailyLetterChallenge
    {
        $challengeDate = $date->copy()->startOfDay();
        $existingChallenge = DailyLetterChallenge::query()
            ->with('ageGroup')
            ->whereDate('challenge_date', $challengeDate->toDateString())
            ->first();

        if ($existingChallenge) {
            return $existingChallenge;
        }

        $payload = $this->generateDailyLetterChallengePayload($challengeDate);

        try {
            return DailyLetterChallenge::query()
                ->create($payload)
                ->load('ageGroup');
        } catch (\Throwable) {
            return DailyLetterChallenge::query()
                ->with('ageGroup')
                ->whereDate('challenge_date', $challengeDate->toDateString())
                ->firstOrFail();
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function generateDailyLetterChallengePayload(CarbonInterface $date): array
    {
        $difficultyLevel = $this->difficultyLevelForDate($date);
        $ageGroup = $this->challengeAgeGroupForDifficulty($difficultyLevel);
        $candidate = $this->balancedDrawService->generateLetters($ageGroup);
        $bestWord = $candidate->solvabilityReport->bestWord;
        $bestLength = (int) ($candidate->solvabilityReport->metadata['best_length'] ?? strlen((string) $bestWord));
        $maxScore = $bestLength * 10;

        return [
            'challenge_date' => $date->toDateString(),
            'difficulty_level' => $difficultyLevel,
            'age_group_id' => $ageGroup->id,
            'letters' => $candidate->payload['letters'],
            'solution_word' => $bestWord !== null ? strtoupper($bestWord) : null,
            'max_score' => $maxScore,
            'quality_score' => $candidate->qualityScore,
            'metadata' => [
                'best_length' => $bestLength,
                'solutions_count' => $candidate->solvabilityReport->solutionsCount,
                'timer_seconds' => $ageGroup->letters_timer_seconds,
                'seed_word' => $candidate->payload['seed_word'] ?? null,
                'generation_attempt' => $candidate->attempt,
            ],
            'starts_at' => $date->copy()->startOfDay(),
            'ends_at' => $date->copy()->endOfDay(),
            'generated_at' => now(),
        ];
    }

    public function difficultyLevelForDate(CarbonInterface $date): DifficultyLevel
    {
        return match ($date->dayOfWeekIso) {
            1 => DifficultyLevel::EASY,
            2, 3 => DifficultyLevel::NORMAL,
            4, 5 => DifficultyLevel::HARD,
            default => DifficultyLevel::EXPERT,
        };
    }

    private function challengeAgeGroup(): AgeGroup
    {
        return AgeGroup::query()
            ->where('min_age', '>=', 10)
            ->orderBy('min_age')
            ->first()
            ?? AgeGroup::query()->orderBy('min_age')->firstOrFail();
    }

    private function challengeAgeGroupForDifficulty(DifficultyLevel $difficultyLevel): AgeGroup
    {
        return match ($difficultyLevel) {
            DifficultyLevel::EASY => AgeGroup::query()
                ->where('min_age', '<', 10)
                ->orderBy('min_age')
                ->first()
                ?? $this->challengeAgeGroup(),
            DifficultyLevel::NORMAL => AgeGroup::query()
                ->where('min_age', '>=', 10)
                ->where('min_age', '<', 14)
                ->orderBy('min_age')
                ->first()
                ?? $this->challengeAgeGroup(),
            DifficultyLevel::HARD, DifficultyLevel::EXPERT => AgeGroup::query()
                ->where('min_age', '>=', 14)
                ->orderBy('min_age')
                ->first()
                ?? $this->challengeAgeGroup(),
        };
    }
}
