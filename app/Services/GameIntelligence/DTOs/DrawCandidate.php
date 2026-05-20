<?php

namespace App\Services\GameIntelligence\DTOs;

/**
 * Generated draw candidate along with the checks used to accept it.
 */
class DrawCandidate
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly string $gameType,
        public readonly array $payload,
        public readonly DifficultyProfile $difficultyProfile,
        public readonly SolvabilityReport $solvabilityReport,
        public readonly int $qualityScore,
        public readonly int $attempt,
    ) {
    }
}
