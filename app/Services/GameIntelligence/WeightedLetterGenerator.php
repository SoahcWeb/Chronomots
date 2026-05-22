<?php

namespace App\Services\GameIntelligence;

use App\Services\GameIntelligence\DTOs\DifficultyProfile;

class WeightedLetterGenerator
{
    public function __construct(
        private readonly DrawConstraintService $drawConstraintService,
        private readonly LetterPoolService $letterPoolService,
    ) {
    }

    /**
     * @param  array<int, string>  $existingLetters
     * @param  array<int, string>  $preferredLetters
     */
    public function pick(string $type, array $existingLetters, DifficultyProfile $difficultyProfile, array $preferredLetters = []): string
    {
        $preferredCounts = array_count_values($preferredLetters);
        $seedBonus = (int) ($difficultyProfile->metadata['seed_letter_bonus'] ?? 6);
        $weights = $this->letterPoolService->weightsForType($type);
        $weightedCandidates = [];

        foreach ([0, 1, 2, 3] as $relaxationLevel) {
            $weightedCandidates = [];
            $preferredCandidates = [];

            foreach ($this->drawConstraintService->candidateLettersForType($type, $existingLetters, $difficultyProfile, $relaxationLevel) as $letter) {
                $weight = ($weights[$letter] ?? 1) + (($preferredCounts[$letter] ?? 0) * $seedBonus);
                $weightedCandidates[$letter] = $weight;

                if (($preferredCounts[$letter] ?? 0) > 0) {
                    $preferredCandidates[$letter] = $weight;
                }
            }

            if ($preferredCandidates !== []) {
                $weightedCandidates = $preferredCandidates;
            }

            if ($weightedCandidates !== []) {
                break;
            }
        }

        if ($weightedCandidates === []) {
            foreach ($this->drawConstraintService->allowedChoiceTypes($existingLetters, $difficultyProfile) as $fallbackType) {
                $fallbackWeights = $this->letterPoolService->weightsForType($fallbackType);

                foreach ([0, 1, 2, 3] as $relaxationLevel) {
                    $weightedCandidates = [];

                    foreach ($this->drawConstraintService->candidateLettersForType($fallbackType, $existingLetters, $difficultyProfile, $relaxationLevel) as $letter) {
                        $weightedCandidates[$letter] = $fallbackWeights[$letter] ?? 1;
                    }

                    if ($weightedCandidates !== []) {
                        break 2;
                    }
                }
            }
        }

        if ($weightedCandidates === []) {
            foreach ($weights as $fallbackLetter => $_weight) {
                if ($this->drawConstraintService->canCompleteWithLetter($fallbackLetter, $existingLetters, $difficultyProfile)) {
                    return $fallbackLetter;
                }
            }

            return array_key_first($weights) ?? 'E';
        }

        $threshold = random_int(1, array_sum($weightedCandidates));
        $running = 0;

        foreach ($weightedCandidates as $letter => $weight) {
            $running += $weight;

            if ($threshold <= $running) {
                return $letter;
            }
        }

        return array_key_first($weightedCandidates);
    }
}
