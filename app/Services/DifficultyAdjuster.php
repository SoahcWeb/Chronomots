<?php

namespace App\Services;

use App\Enums\DifficultyLevel;

class DifficultyAdjuster
{
    private const VOWELS = ['A', 'E', 'I', 'O', 'U'];

    private const FREQUENT_LETTERS = ['E', 'A', 'I', 'S', 'N', 'R', 'T', 'O', 'L', 'U'];

    private const COMPLEX_CONSONANTS = ['C', 'D', 'G', 'H', 'M', 'P', 'V'];

    private const RARE_LETTERS = ['J', 'K', 'Q', 'W', 'X', 'Y', 'Z'];

    public function __construct(
        private readonly LetterBagService $letterBagService,
    ) {
    }

    /**
     * Apply a gameplay difficulty profile to the active letter bag distribution.
     */
    public function applyDifficulty(DifficultyLevel $difficulty): void
    {
        $distribution = $this->letterBagService->getBaseDistribution();

        $adjustedDistribution = match ($difficulty) {
            DifficultyLevel::EASY => $this->applyEasyProfile($distribution),
            DifficultyLevel::NORMAL => $distribution,
            DifficultyLevel::HARD => $this->applyHardProfile($distribution),
            DifficultyLevel::EXPERT => $this->applyExpertProfile($distribution),
        };

        $this->letterBagService->setDistribution($adjustedDistribution);
    }

    /**
     * @param  array<string, int>  $distribution
     * @return array<string, int>
     */
    private function applyEasyProfile(array $distribution): array
    {
        foreach (self::VOWELS as $letter) {
            $distribution[$letter] = ($distribution[$letter] ?? 0) + 2;
        }

        foreach (self::FREQUENT_LETTERS as $letter) {
            $distribution[$letter] = ($distribution[$letter] ?? 0) + 1;
        }

        foreach (self::RARE_LETTERS as $letter) {
            $distribution[$letter] = max(0, ($distribution[$letter] ?? 0) - 1);
        }

        return $distribution;
    }

    /**
     * @param  array<string, int>  $distribution
     * @return array<string, int>
     */
    private function applyHardProfile(array $distribution): array
    {
        foreach (self::VOWELS as $letter) {
            $distribution[$letter] = max(1, ($distribution[$letter] ?? 0) - 1);
        }

        foreach (self::COMPLEX_CONSONANTS as $letter) {
            $distribution[$letter] = ($distribution[$letter] ?? 0) + 2;
        }

        return $distribution;
    }

    /**
     * @param  array<string, int>  $distribution
     * @return array<string, int>
     */
    private function applyExpertProfile(array $distribution): array
    {
        foreach (self::VOWELS as $letter) {
            $distribution[$letter] = max(1, ($distribution[$letter] ?? 0) - 1);
        }

        foreach (self::RARE_LETTERS as $letter) {
            $distribution[$letter] = ($distribution[$letter] ?? 0) + 2;
        }

        foreach (self::COMPLEX_CONSONANTS as $letter) {
            $distribution[$letter] = ($distribution[$letter] ?? 0) + 1;
        }

        return $distribution;
    }
}
