<?php

namespace App\Services\GameIntelligence;

use App\Services\GameIntelligence\DTOs\DifficultyProfile;
use App\Services\GameIntelligence\DTOs\SolvabilityReport;

class DrawQualityScorer
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function scoreLetters(DifficultyProfile $difficultyProfile, SolvabilityReport $solvabilityReport, array $payload): int
    {
        $score = $solvabilityReport->valid ? 50 : 15;
        $score += min(20, $solvabilityReport->solutionsCount * 4);
        $score += min(20, (int) ($solvabilityReport->metadata['target_word_count'] ?? 0) * 7);
        $score += min(15, max(0, ((int) ($solvabilityReport->metadata['best_length'] ?? 0) - $difficultyProfile->minBestLength + 1)) * 5);

        $minVowels = (int) ($difficultyProfile->metadata['min_vowels'] ?? $difficultyProfile->vowelsCount);
        $maxVowels = (int) ($difficultyProfile->metadata['max_vowels'] ?? $difficultyProfile->vowelsCount);
        $vowelCount = (int) ($payload['vowel_count'] ?? 0);
        $rareLettersCount = (int) ($payload['rare_letters_count'] ?? 0);
        $score += $vowelCount >= $minVowels && $vowelCount <= $maxVowels ? 10 : 0;
        $score -= max(0, $rareLettersCount - (int) ($difficultyProfile->metadata['max_rare_letters'] ?? 1)) * 4;

        return max(0, min(100, $score));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function scoreNumbers(DifficultyProfile $difficultyProfile, SolvabilityReport $solvabilityReport, array $payload): int
    {
        $score = $solvabilityReport->valid ? 55 : 20;
        $score += min(20, $solvabilityReport->solutionsCount * 2);
        $score += $solvabilityReport->bestValue !== null ? 10 : 0;
        $score += (int) (($solvabilityReport->metadata['selected_target']['quality'] ?? 0) / 2);

        return max(0, min(100, $score));
    }
}
