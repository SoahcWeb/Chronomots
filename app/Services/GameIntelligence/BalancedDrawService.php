<?php

namespace App\Services\GameIntelligence;

use App\Models\AgeGroup;
use App\Services\GameIntelligence\DTOs\DrawCandidate;

class BalancedDrawService
{
    public function __construct(
        private readonly AgeDifficultyProfileService $ageDifficultyProfileService,
        private readonly LettersDrawGenerator $lettersDrawGenerator,
        private readonly NumbersDrawGenerator $numbersDrawGenerator,
        private readonly LettersSolvabilityService $lettersSolvabilityService,
        private readonly NumbersSolvabilityService $numbersSolvabilityService,
        private readonly DrawQualityScorer $drawQualityScorer,
    ) {
    }

    public function generateLetters(AgeGroup $ageGroup): DrawCandidate
    {
        $difficultyProfile = $this->ageDifficultyProfileService->forLetters($ageGroup);
        $bestCandidate = null;
        $acceptScore = (int) ($difficultyProfile->metadata['accept_score'] ?? 75);
        $deadline = microtime(true) + (((int) ($difficultyProfile->metadata['generation_timeout_ms'] ?? 800)) / 1000);

        for ($attempt = 1; $attempt <= $difficultyProfile->maxGenerationAttempts; $attempt++) {
            $payload = $this->lettersDrawGenerator->generate($ageGroup, $difficultyProfile);
            $solvabilityReport = $this->lettersSolvabilityService->analyze(
                $payload['letters'],
                $ageGroup,
                $difficultyProfile,
            );
            $qualityScore = $this->drawQualityScorer->scoreLetters($difficultyProfile, $solvabilityReport, $payload);

            $candidate = new DrawCandidate(
                gameType: 'letters',
                payload: $payload,
                difficultyProfile: $difficultyProfile,
                solvabilityReport: $solvabilityReport,
                qualityScore: $qualityScore,
                attempt: $attempt,
            );

            if ($bestCandidate === null || $candidate->qualityScore > $bestCandidate->qualityScore) {
                $bestCandidate = $candidate;
            }

            if ($solvabilityReport->valid && $qualityScore >= $acceptScore) {
                return $candidate;
            }

            if (microtime(true) >= $deadline && $bestCandidate !== null) {
                break;
            }
        }

        /** @var DrawCandidate $bestCandidate */
        return $bestCandidate;
    }

    public function generateNumbers(AgeGroup $ageGroup): DrawCandidate
    {
        $difficultyProfile = $this->ageDifficultyProfileService->forNumbers($ageGroup);
        $bestCandidate = null;
        $acceptScore = (int) ($difficultyProfile->metadata['accept_score'] ?? 72);

        for ($attempt = 1; $attempt <= $difficultyProfile->maxGenerationAttempts; $attempt++) {
            $payload = $this->numbersDrawGenerator->generate($difficultyProfile);
            $solvabilityReport = $this->numbersSolvabilityService->analyze($payload['numbers'], $difficultyProfile);

            $payload['target_number'] = $solvabilityReport->bestValue ?? $difficultyProfile->targetMin;
            $qualityScore = $this->drawQualityScorer->scoreNumbers($difficultyProfile, $solvabilityReport, $payload);

            $candidate = new DrawCandidate(
                gameType: 'numbers',
                payload: $payload,
                difficultyProfile: $difficultyProfile,
                solvabilityReport: $solvabilityReport,
                qualityScore: $qualityScore,
                attempt: $attempt,
            );

            if ($bestCandidate === null || $candidate->qualityScore > $bestCandidate->qualityScore) {
                $bestCandidate = $candidate;
            }

            if ($solvabilityReport->valid && $qualityScore >= $acceptScore) {
                return $candidate;
            }
        }

        /** @var DrawCandidate $bestCandidate */
        return $bestCandidate;
    }
}
