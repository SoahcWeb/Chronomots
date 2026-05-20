<?php

namespace App\Services\GameIntelligence\DTOs;

/**
 * Result of a solvability analysis for a generated draw.
 */
class SolvabilityReport
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public readonly bool $valid,
        public readonly int $solutionsCount = 0,
        public readonly ?string $message = null,
        public readonly ?string $bestWord = null,
        public readonly ?int $bestValue = null,
        public readonly array $metadata = [],
    ) {
    }
}
