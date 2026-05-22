<?php

namespace App\Services\GameIntelligence;

class LetterPoolService
{
    /**
     * @var array<string, int>
     */
    private const WEIGHTS = [
        'E' => 14,
        'A' => 9,
        'I' => 8,
        'S' => 7,
        'N' => 7,
        'R' => 6,
        'T' => 6,
        'O' => 5,
        'L' => 5,
        'U' => 5,
        'D' => 4,
        'C' => 3,
        'M' => 3,
        'P' => 3,
        'G' => 2,
        'B' => 2,
        'F' => 2,
        'H' => 2,
        'V' => 2,
        'J' => 1,
        'K' => 1,
        'Q' => 1,
        'W' => 1,
        'X' => 1,
        'Y' => 1,
        'Z' => 1,
    ];

    /**
     * @var array<int, string>
     */
    private const VOWELS = ['A', 'E', 'I', 'O', 'U'];

    /**
     * @var array<int, string>
     */
    private const RARE_LETTERS = ['J', 'K', 'Q', 'W', 'X', 'Y', 'Z'];

    /**
     * @return array<string, int>
     */
    public function weightsForType(string $type): array
    {
        $normalizedType = strtolower($type);

        return collect(self::WEIGHTS)
            ->filter(fn (int $weight, string $letter) => $normalizedType === 'vowel'
                ? $this->isVowel($letter)
                : ! $this->isVowel($letter))
            ->all();
    }

    /**
     * @return array<string, int>
     */
    public function allWeights(): array
    {
        return self::WEIGHTS;
    }

    public function isVowel(string $letter): bool
    {
        return in_array(strtoupper($letter), self::VOWELS, true);
    }

    public function isRareLetter(string $letter): bool
    {
        return in_array(strtoupper($letter), self::RARE_LETTERS, true);
    }
}
