<?php

namespace App\Services\GameIntelligence;

use App\Models\AgeGroup;
use App\Services\DrawStatisticsService;
use App\Services\GameIntelligence\DTOs\DrawCandidate;

class BalancedDrawService
{
    public function __construct(
        private readonly AgeDifficultyProfileService $ageDifficultyProfileService,
        private readonly DrawStatisticsService $drawStatisticsService,
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
        $bestValidCandidate = null;
        $attemptCandidates = [];
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
            $attemptCandidates[$attempt] = $candidate;

            if ($bestCandidate === null || $candidate->qualityScore > $bestCandidate->qualityScore) {
                $bestCandidate = $candidate;
            }

            if ($solvabilityReport->valid && ($bestValidCandidate === null || $candidate->qualityScore > $bestValidCandidate->qualityScore)) {
                $bestValidCandidate = $candidate;
            }

            if ($solvabilityReport->valid && $qualityScore >= $acceptScore) {
                $this->recordLettersStatistics($ageGroup, $attemptCandidates, $candidate);

                return $candidate;
            }

            if (microtime(true) >= $deadline && $bestCandidate !== null) {
                break;
            }
        }

        if ($bestValidCandidate !== null) {
            $this->recordLettersStatistics($ageGroup, $attemptCandidates, $bestValidCandidate);

            return $bestValidCandidate;
        }

        $fallbackPayload = $this->lettersDrawGenerator->generateSeededFallback($ageGroup, $difficultyProfile);

        if ($fallbackPayload !== null) {
            $solvabilityReport = $this->lettersSolvabilityService->analyze(
                $fallbackPayload['letters'],
                $ageGroup,
                $difficultyProfile,
            );

            if ($solvabilityReport->valid) {
                $acceptedCandidate = new DrawCandidate(
                    gameType: 'letters',
                    payload: $fallbackPayload,
                    difficultyProfile: $difficultyProfile,
                    solvabilityReport: $solvabilityReport,
                    qualityScore: $this->drawQualityScorer->scoreLetters($difficultyProfile, $solvabilityReport, $fallbackPayload),
                    attempt: $difficultyProfile->maxGenerationAttempts + 1,
                );
                $this->recordLettersStatistics($ageGroup, $attemptCandidates, $acceptedCandidate);

                return $acceptedCandidate;
            }
        }

        $curatedFallbackPayload = $this->lettersDrawGenerator->generateCuratedFallback($ageGroup, $difficultyProfile);

        if ($curatedFallbackPayload !== null) {
            $solvabilityReport = $this->lettersSolvabilityService->analyze(
                $curatedFallbackPayload['letters'],
                $ageGroup,
                $difficultyProfile,
            );

            if ($solvabilityReport->valid) {
                $acceptedCandidate = new DrawCandidate(
                    gameType: 'letters',
                    payload: $curatedFallbackPayload,
                    difficultyProfile: $difficultyProfile,
                    solvabilityReport: $solvabilityReport,
                    qualityScore: $this->drawQualityScorer->scoreLetters($difficultyProfile, $solvabilityReport, $curatedFallbackPayload),
                    attempt: $difficultyProfile->maxGenerationAttempts + 2,
                );
                $this->recordLettersStatistics($ageGroup, $attemptCandidates, $acceptedCandidate);

                return $acceptedCandidate;
            }
        }

        logger()->warning('Unable to generate a solvable letters draw within bounded attempts.', [
            'age_group_id' => $ageGroup->id,
            'age_group_name' => $ageGroup->name,
            'letters_count' => $difficultyProfile->lettersCount,
            'best_candidate_score' => $bestCandidate?->qualityScore,
            'best_candidate_valid' => $bestCandidate?->solvabilityReport->valid,
            'seeded_fallback_valid' => $fallbackPayload !== null,
            'curated_fallback_valid' => $curatedFallbackPayload !== null,
        ]);

        if ($bestCandidate !== null) {
            $lastResortPayload = $bestCandidate->payload;
            $lastResortReport = $this->lettersSolvabilityService->analyze(
                $lastResortPayload['letters'],
                $ageGroup,
                $difficultyProfile,
            );

            if ($lastResortReport->valid) {
                $acceptedCandidate = new DrawCandidate(
                    gameType: 'letters',
                    payload: $lastResortPayload,
                    difficultyProfile: $difficultyProfile,
                    solvabilityReport: $lastResortReport,
                    qualityScore: $this->drawQualityScorer->scoreLetters($difficultyProfile, $lastResortReport, $lastResortPayload),
                    attempt: $bestCandidate->attempt,
                );
                $this->recordLettersStatistics($ageGroup, $attemptCandidates, $acceptedCandidate);

                return $acceptedCandidate;
            }
        }

        $guaranteedPayload = $curatedFallbackPayload ?? $fallbackPayload ?? $this->lettersDrawGenerator->generateSafeFallback($difficultyProfile);
        $guaranteedReport = $this->lettersSolvabilityService->analyze(
            $guaranteedPayload['letters'],
            $ageGroup,
            $difficultyProfile,
        );

        $acceptedCandidate = new DrawCandidate(
            gameType: 'letters',
            payload: $guaranteedPayload,
            difficultyProfile: $difficultyProfile,
            solvabilityReport: $guaranteedReport,
            qualityScore: $this->drawQualityScorer->scoreLetters($difficultyProfile, $guaranteedReport, $guaranteedPayload),
            attempt: $difficultyProfile->maxGenerationAttempts + 3,
        );
        $this->recordLettersStatistics($ageGroup, $attemptCandidates, $acceptedCandidate);

        return $acceptedCandidate;
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

    /**
     * @param  array<int, DrawCandidate>  $attemptCandidates
     */
    private function recordLettersStatistics(AgeGroup $ageGroup, array $attemptCandidates, DrawCandidate $acceptedCandidate): void
    {
        foreach ($attemptCandidates as $attempt => $candidate) {
            $this->drawStatisticsService->recordLettersDrawAttempt(
                $ageGroup,
                $candidate->payload['letters'],
                $candidate->solvabilityReport,
                $candidate->qualityScore,
                $attempt === $acceptedCandidate->attempt,
            );
        }

        if (! isset($attemptCandidates[$acceptedCandidate->attempt])) {
            $this->drawStatisticsService->recordLettersDrawAttempt(
                $ageGroup,
                $acceptedCandidate->payload['letters'],
                $acceptedCandidate->solvabilityReport,
                $acceptedCandidate->qualityScore,
                true,
            );
        }
    }
}
