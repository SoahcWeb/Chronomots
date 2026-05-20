<?php

namespace App\Services\GameIntelligence;

use App\Models\AgeGroup;

class OpponentAiService
{
    private const LEVELS = [
        'facile' => 'Facile',
        'moyen' => 'Moyen',
        'difficile' => 'Difficile',
        'expert' => 'Expert',
    ];

    public function __construct(
        private readonly LettersOpponentStrategy $lettersOpponentStrategy,
        private readonly NumbersOpponentStrategy $numbersOpponentStrategy,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function levels(): array
    {
        return self::LEVELS;
    }

    public function normalizeLevel(?string $level): ?string
    {
        if ($level === null) {
            return null;
        }

        return array_key_exists($level, self::LEVELS) ? $level : null;
    }

    public function labelForLevel(?string $level): ?string
    {
        return $level !== null ? (self::LEVELS[$level] ?? null) : null;
    }

    /**
     * @param  array<int, string>  $letters
     * @return array{submitted_word: string, score: int, quality_label: string, level: string, level_label: string}
     */
    public function playLetters(array $letters, AgeGroup $ageGroup, string $level): array
    {
        $normalizedLevel = $this->normalizeLevel($level) ?? 'moyen';
        $result = $this->lettersOpponentStrategy->play($letters, $ageGroup, $normalizedLevel);

        return [
            ...$result,
            'level' => $normalizedLevel,
            'level_label' => $this->labelForLevel($normalizedLevel) ?? 'Moyen',
        ];
    }

    /**
     * @param  array<int, int>  $numbers
     * @return array{submitted_solution: string, result_value: int, difference: int, score: int, quality_label: string, level: string, level_label: string}
     */
    public function playNumbers(array $numbers, int $targetNumber, string $level): array
    {
        $normalizedLevel = $this->normalizeLevel($level) ?? 'moyen';
        $result = $this->numbersOpponentStrategy->play($numbers, $targetNumber, $normalizedLevel);

        return [
            ...$result,
            'level' => $normalizedLevel,
            'level_label' => $this->labelForLevel($normalizedLevel) ?? 'Moyen',
        ];
    }
}
