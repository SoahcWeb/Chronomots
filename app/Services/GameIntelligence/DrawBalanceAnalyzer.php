<?php

namespace App\Services\GameIntelligence;

class DrawBalanceAnalyzer
{
    private const MIN_VOWELS = 3;

    private const MAX_VOWELS = 6;

    private const MAX_RARE_LETTERS = 1;

    private const MAX_CONSECUTIVE_DIFFICULT_CONSONANTS = 3;

    /**
     * Common consonants that should not be treated as difficult in clustered runs.
     *
     * @var array<int, string>
     */
    private const EASY_CONSONANTS = ['L', 'N', 'R', 'S', 'T'];

    public function __construct(
        private readonly LetterPoolService $letterPoolService,
    ) {
    }

    /**
     * Analyze a draw and return its balance metrics together with any detected issues.
     *
     * @param  array<int, string>  $letters
     * @return array{
     *     vowel_count: int,
     *     consonant_count: int,
     *     rare_letters_count: int,
     *     balance_score: int,
     *     problems: array<int, string>
     * }
     */
    public function analyze(array $letters): array
    {
        $normalizedLetters = $this->normalizeLetters($letters);

        $vowelCount = count(array_filter(
            $normalizedLetters,
            fn (string $letter): bool => $this->letterPoolService->isVowel($letter),
        ));
        $consonantCount = count($normalizedLetters) - $vowelCount;
        $rareLettersCount = count(array_filter(
            $normalizedLetters,
            fn (string $letter): bool => $this->letterPoolService->isRareLetter($letter),
        ));
        $longestConsonantRun = $this->longestConsecutiveDifficultConsonantRun($normalizedLetters);
        $problems = [];
        $balanceScore = 100;

        if ($vowelCount < self::MIN_VOWELS) {
            $problems[] = sprintf('Not enough vowels: %d found, %d required.', $vowelCount, self::MIN_VOWELS);
            $balanceScore -= min(30, (self::MIN_VOWELS - $vowelCount) * 10);
        }

        if ($vowelCount > self::MAX_VOWELS) {
            $problems[] = sprintf('Too many vowels: %d found, maximum allowed is %d.', $vowelCount, self::MAX_VOWELS);
            $balanceScore -= min(30, ($vowelCount - self::MAX_VOWELS) * 10);
        }

        if ($rareLettersCount > self::MAX_RARE_LETTERS) {
            $problems[] = sprintf(
                'Too many rare letters: %d found, maximum allowed is %d.',
                $rareLettersCount,
                self::MAX_RARE_LETTERS,
            );
            $balanceScore -= min(25, ($rareLettersCount - self::MAX_RARE_LETTERS) * 15);
        }

        if ($longestConsonantRun > self::MAX_CONSECUTIVE_DIFFICULT_CONSONANTS) {
            $problems[] = sprintf(
                'Too many consecutive difficult consonants: longest run is %d, maximum allowed is %d.',
                $longestConsonantRun,
                self::MAX_CONSECUTIVE_DIFFICULT_CONSONANTS,
            );
            $balanceScore -= min(30, ($longestConsonantRun - self::MAX_CONSECUTIVE_DIFFICULT_CONSONANTS) * 10);
        }

        return [
            'vowel_count' => $vowelCount,
            'consonant_count' => $consonantCount,
            'rare_letters_count' => $rareLettersCount,
            'balance_score' => max(0, $balanceScore),
            'problems' => $problems,
        ];
    }

    /**
     * Determine whether a draw satisfies all current balancing rules.
     *
     * @param  array<int, string>  $letters
     */
    public function isBalanced(array $letters): bool
    {
        return $this->analyze($this->normalizeLetters($letters))['problems'] === [];
    }

    /**
     * @param  array<int, string>  $letters
     */
    private function normalizeLetters(array $letters): array
    {
        return array_values(array_map(
            static fn (string $letter): string => strtoupper($letter),
            $letters,
        ));
    }

    /**
     * @param  array<int, string>  $letters
     */
    private function longestConsecutiveDifficultConsonantRun(array $letters): int
    {
        $longestRun = 0;
        $currentRun = 0;

        foreach ($letters as $letter) {
            if (! $this->isDifficultConsonant($letter)) {
                $currentRun = 0;
                continue;
            }

            $currentRun++;
            $longestRun = max($longestRun, $currentRun);
        }

        return $longestRun;
    }

    private function isDifficultConsonant(string $letter): bool
    {
        if ($this->letterPoolService->isVowel($letter)) {
            return false;
        }

        return ! in_array($letter, self::EASY_CONSONANTS, true);
    }
}
