<?php

namespace App\Services\GameIntelligence;

use App\Services\GameIntelligence\DTOs\DifficultyProfile;

class NumbersDrawGenerator
{
    /**
     * Generate a candidate numbers set. The target is selected later by solvability analysis.
     *
     * @return array{numbers: array<int, int>}
     */
    public function generate(DifficultyProfile $difficultyProfile): array
    {
        if (($difficultyProfile->metadata['large_numbers'] ?? null) !== null) {
            $smallCount = (int) ($difficultyProfile->metadata['small_numbers_count'] ?? 4);
            $smallMin = (int) ($difficultyProfile->metadata['small_numbers_min'] ?? 1);
            $smallMax = (int) ($difficultyProfile->metadata['small_numbers_max'] ?? 10);
            $largePool = $difficultyProfile->metadata['large_numbers'];

            $numbers = $this->generateRangeNumbers($smallCount, $smallMin, $smallMax);
            shuffle($largePool);
            $numbers = array_merge($numbers, array_slice($largePool, 0, $difficultyProfile->numbersCount - $smallCount));

            return [
                'numbers' => $this->balanceOrder($numbers),
            ];
        }

        return [
            'numbers' => $this->balanceOrder($this->generateRangeNumbers(
                $difficultyProfile->numbersCount,
                $difficultyProfile->numbersMin,
                $difficultyProfile->numbersMax,
            )),
        ];
    }

    /**
     * @return array<int, int>
     */
    private function generateRangeNumbers(int $count, int $min, int $max): array
    {
        $numbers = [];

        for ($index = 0; $index < $count; $index++) {
            $numbers[] = random_int($min, $max);
        }

        return $numbers;
    }

    /**
     * Shuffle while keeping a varied set visible to the player.
     *
     * @param  array<int, int>  $numbers
     * @return array<int, int>
     */
    private function balanceOrder(array $numbers): array
    {
        sort($numbers);

        $ordered = [];

        while ($numbers !== []) {
            $ordered[] = array_pop($numbers);

            if ($numbers !== []) {
                $ordered[] = array_shift($numbers);
            }
        }

        return $ordered;
    }
}
