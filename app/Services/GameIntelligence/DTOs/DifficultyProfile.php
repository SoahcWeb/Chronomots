<?php

namespace App\Services\GameIntelligence\DTOs;

/**
 * Small immutable profile describing how a draw should behave for one mode.
 */
class DifficultyProfile
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public readonly string $gameType,
        public readonly int $maxGenerationAttempts,
        public readonly int $lettersCount = 0,
        public readonly int $vowelsCount = 0,
        public readonly int $numbersCount = 0,
        public readonly int $numbersMin = 0,
        public readonly int $numbersMax = 0,
        public readonly int $targetMin = 0,
        public readonly int $targetMax = 0,
        public readonly int $minSolutions = 1,
        public readonly int $minBestLength = 0,
        public readonly array $metadata = [],
    ) {
    }
}
