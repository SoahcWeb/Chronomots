<?php

namespace App\Services\GameIntelligence;

use App\Models\AgeGroup;
use App\Services\GameIntelligence\DTOs\DifficultyProfile;

class AgeDifficultyProfileService
{
    /**
     * Centralize letters difficulty rules so controllers do not branch on age.
     */
    public function forLetters(AgeGroup $ageGroup): DifficultyProfile
    {
        if ($ageGroup->min_age >= 14) {
            return new DifficultyProfile(
                gameType: 'letters',
                maxGenerationAttempts: 18,
                lettersCount: 10,
                vowelsCount: 4,
                minSolutions: 2,
                minBestLength: 7,
                metadata: [
                    'seed_min_length' => 6,
                    'min_vowels' => 3,
                    'max_vowels' => 6,
                    'max_rare_letters' => 2,
                    'max_consecutive_consonants' => 3,
                    'max_consecutive_hard_letters' => 1,
                    'seed_pool_per_length' => 100,
                    'seed_evaluation_limit' => 8,
                    'seed_solution_check_limit' => 180,
                    'seed_letter_bonus' => 7,
                    'accept_score' => 82,
                    'generation_timeout_ms' => 850,
                ],
            );
        }

        if ($ageGroup->min_age >= 10) {
            return new DifficultyProfile(
                gameType: 'letters',
                maxGenerationAttempts: 16,
                lettersCount: 8,
                vowelsCount: 4,
                minSolutions: 2,
                minBestLength: 6,
                metadata: [
                    'seed_min_length' => 6,
                    'min_vowels' => 3,
                    'max_vowels' => 5,
                    'max_rare_letters' => 1,
                    'max_consecutive_consonants' => 3,
                    'max_consecutive_hard_letters' => 1,
                    'seed_pool_per_length' => 90,
                    'seed_evaluation_limit' => 7,
                    'seed_solution_check_limit' => 160,
                    'seed_letter_bonus' => 7,
                    'accept_score' => 78,
                    'generation_timeout_ms' => 800,
                ],
            );
        }

        return new DifficultyProfile(
            gameType: 'letters',
            maxGenerationAttempts: 14,
            lettersCount: 7,
            vowelsCount: 3,
            minSolutions: 3,
            minBestLength: 4,
            metadata: [
                'seed_min_length' => 4,
                'min_vowels' => 3,
                'max_vowels' => 4,
                'max_rare_letters' => 1,
                'max_consecutive_consonants' => 2,
                'max_consecutive_hard_letters' => 1,
                'seed_pool_per_length' => 80,
                'seed_evaluation_limit' => 6,
                'seed_solution_check_limit' => 140,
                'seed_letter_bonus' => 6,
                'accept_score' => 74,
                'generation_timeout_ms' => 750,
            ],
        );
    }

    /**
     * Centralize numbers difficulty rules and target ranges by age.
     */
    public function forNumbers(AgeGroup $ageGroup): DifficultyProfile
    {
        if ($ageGroup->min_age >= 14) {
            return new DifficultyProfile(
                gameType: 'numbers',
                maxGenerationAttempts: 12,
                numbersCount: 6,
                targetMin: 100,
                targetMax: 999,
                minSolutions: 1,
                metadata: [
                    'large_numbers' => [25, 50, 75, 100],
                    'small_numbers_min' => 1,
                    'small_numbers_max' => 10,
                    'small_numbers_count' => 4,
                    'preferred_min_operations' => 3,
                    'preferred_max_operations' => 5,
                    'preferred_min_numbers_used' => 4,
                    'avoid_input_targets' => true,
                    'accept_score' => 80,
                ],
            );
        }

        if ($ageGroup->min_age >= 10) {
            return new DifficultyProfile(
                gameType: 'numbers',
                maxGenerationAttempts: 10,
                numbersCount: 5,
                numbersMin: 1,
                numbersMax: 25,
                targetMin: 50,
                targetMax: 300,
                minSolutions: 1,
                metadata: [
                    'preferred_min_operations' => 2,
                    'preferred_max_operations' => 4,
                    'preferred_min_numbers_used' => 3,
                    'avoid_input_targets' => true,
                    'accept_score' => 74,
                ],
            );
        }

        return new DifficultyProfile(
            gameType: 'numbers',
            maxGenerationAttempts: 10,
            numbersCount: 4,
                numbersMin: 1,
                numbersMax: 10,
                targetMin: 10,
                targetMax: 50,
                minSolutions: 1,
                metadata: [
                    'preferred_min_operations' => 1,
                    'preferred_max_operations' => 3,
                    'preferred_min_numbers_used' => 2,
                    'avoid_input_targets' => false,
                    'accept_score' => 68,
                ],
            );
    }
}
