<?php

namespace App\Services\GameIntelligence;

use App\Services\GameIntelligence\DTOs\DifficultyProfile;

class DrawConstraintService
{
    public function __construct(
        private readonly LetterPoolService $letterPoolService,
    ) {
    }

    /**
     * @param  array<int, string>  $letters
     * @return array<int, string>
     */
    public function allowedChoiceTypes(array $letters, DifficultyProfile $difficultyProfile): array
    {
        $remainingSlots = $this->remainingSlots($letters, $difficultyProfile);

        if ($remainingSlots <= 0) {
            return [];
        }

        $feasibleTypes = $this->baseFeasibleChoiceTypes($letters, $difficultyProfile);

        foreach ([0, 1, 2, 3] as $relaxationLevel) {
            $allowed = array_values(array_filter(
                $feasibleTypes,
                fn (string $type) => $this->candidateLettersForType($type, $letters, $difficultyProfile, $relaxationLevel) !== [],
            ));

            if ($allowed !== []) {
                return $allowed;
            }
        }

        return $feasibleTypes;
    }

    /**
     * @param  array<int, string>  $letters
     */
    public function canAddLetter(string $letter, array $letters, DifficultyProfile $difficultyProfile): bool
    {
        return $this->isLetterAllowed($letter, $letters, $difficultyProfile, 0);
    }

    /**
     * Return valid letters for one type under a bounded relaxation strategy.
     *
     * Relaxation levels:
     * 0: strict
     * 1: relax consonant run / hard-series limits
     * 2: also relax rare-letter count
     * 3: keep only min/max vowels, duplicates and basic Q playability
     *
     * @param  array<int, string>  $letters
     * @return array<int, string>
     */
    public function candidateLettersForType(string $type, array $letters, DifficultyProfile $difficultyProfile, int $relaxationLevel = 0): array
    {
        if (! $this->choiceKeepsDrawFeasible($type, $letters, $difficultyProfile)) {
            return [];
        }

        for ($level = $relaxationLevel; $level <= 3; $level++) {
            $candidates = [];

            foreach ($this->letterPoolService->weightsForType($type) as $letter => $_weight) {
                if (
                    $this->isLetterAllowed($letter, $letters, $difficultyProfile, $level)
                    && $this->canCompleteWithLetter($letter, $letters, $difficultyProfile)
                ) {
                    $candidates[] = $letter;
                }
            }

            if ($candidates !== []) {
                return $candidates;
            }
        }

        return [];
    }

    /**
     * Check whether adding this letter still leaves a path to a valid final draw.
     *
     * @param  array<int, string>  $letters
     */
    public function canCompleteWithLetter(string $letter, array $letters, DifficultyProfile $difficultyProfile): bool
    {
        $simulated = [...$letters, $letter];
        if (! $this->canPartialDrawReachValidCompletion($simulated, $difficultyProfile)) {
            return false;
        }

        if (count($simulated) === $difficultyProfile->lettersCount) {
            return $this->repairCompletedDraw($simulated, $difficultyProfile) !== null;
        }

        return true;
    }

    /**
     * Check whether the current partial draw can still reach a valid final state.
     *
     * @param  array<int, string>  $letters
     */
    public function canPartialDrawReachValidCompletion(array $letters, DifficultyProfile $difficultyProfile): bool
    {
        $currentCount = count($letters);

        if ($currentCount > $difficultyProfile->lettersCount) {
            return false;
        }

        $counts = array_count_values($letters);

        foreach ($counts as $letter => $count) {
            if ($count > $this->maxDuplicateCount((string) $letter)) {
                return false;
            }
        }

        $vowelCount = $this->countVowels($letters);
        $rareLettersCount = count(array_filter($letters, fn (string $existingLetter) => $this->letterPoolService->isRareLetter($existingLetter)));
        $remainingSlots = $difficultyProfile->lettersCount - $currentCount;

        if ($vowelCount > $this->maxVowels($difficultyProfile)) {
            return false;
        }

        if ($vowelCount + $remainingSlots < $this->minVowels($difficultyProfile)) {
            return false;
        }

        if ($rareLettersCount > (int) ($difficultyProfile->metadata['max_rare_letters'] ?? 1)) {
            return false;
        }

        if (! $this->canReachValidConsonantDistribution($vowelCount, $remainingSlots, $difficultyProfile)) {
            return false;
        }

        return true;
    }

    /**
     * @param  array<int, string>  $letters
     */
    public function isCompletedDrawValid(array $letters, DifficultyProfile $difficultyProfile): bool
    {
        $vowelCount = $this->countVowels($letters);
        $rareLettersCount = count(array_filter($letters, fn (string $letter) => $this->letterPoolService->isRareLetter($letter)));

        return count($letters) === $difficultyProfile->lettersCount
            && $vowelCount >= $this->minVowels($difficultyProfile)
            && $vowelCount <= $this->maxVowels($difficultyProfile)
            && $rareLettersCount <= (int) ($difficultyProfile->metadata['max_rare_letters'] ?? 1)
            && $this->longestConsonantRun($letters) <= $this->maxConsecutiveConsonants($difficultyProfile);
    }

    /**
     * Repair a completed draw to satisfy the final structural constraints when possible.
     *
     * @param  array<int, string>  $letters
     * @return array<int, string>|null
     */
    public function repairCompletedDraw(array $letters, DifficultyProfile $difficultyProfile): ?array
    {
        if (count($letters) !== $difficultyProfile->lettersCount) {
            return null;
        }

        $repaired = array_values($letters);

        $repaired = $this->repairVowelBounds($repaired, $difficultyProfile);

        if ($repaired === null) {
            return null;
        }

        $repaired = $this->repairRareLetters($repaired, $difficultyProfile);

        if ($repaired === null) {
            return null;
        }

        if (! $this->canArrangeConsonantsWithVowels($this->countVowels($repaired), count($repaired) - $this->countVowels($repaired), $difficultyProfile)) {
            return null;
        }

        return $this->reorderForPlayableRuns($repaired, $difficultyProfile);
    }

    /**
     * @param  array<int, string>  $letters
     * @return array{vowel_count: int, rare_letters_count: int, remaining_slots: int, allowed_choices: array<int, string>}
     */
    public function summarize(array $letters, DifficultyProfile $difficultyProfile): array
    {
        return [
            'vowel_count' => $this->countVowels($letters),
            'rare_letters_count' => count(array_filter($letters, fn (string $letter) => $this->letterPoolService->isRareLetter($letter))),
            'remaining_slots' => $this->remainingSlots($letters, $difficultyProfile),
            'allowed_choices' => $this->allowedChoiceTypes($letters, $difficultyProfile),
        ];
    }

    /**
     * Reorder a partial or complete draw to keep consonant runs playable.
     *
     * @param  array<int, string>  $letters
     * @return array<int, string>|null
     */
    public function arrangeLetters(array $letters, DifficultyProfile $difficultyProfile): ?array
    {
        $vowels = array_values(array_filter($letters, fn (string $letter) => $this->letterPoolService->isVowel($letter)));
        $consonants = array_values(array_filter($letters, fn (string $letter) => ! $this->letterPoolService->isVowel($letter)));
        $typePattern = $this->buildPlayableTypePattern(count($vowels), count($consonants), $difficultyProfile);

        if ($typePattern === null) {
            return null;
        }

        $ordered = [];

        foreach ($typePattern as $type) {
            $ordered[] = $type === 'vowel'
                ? array_shift($vowels)
                : array_shift($consonants);
        }

        return $this->longestConsonantRun($ordered) <= $this->maxConsecutiveConsonants($difficultyProfile)
            ? $ordered
            : null;
    }

    public function minVowels(DifficultyProfile $difficultyProfile): int
    {
        return (int) ($difficultyProfile->metadata['min_vowels'] ?? max(3, $difficultyProfile->vowelsCount));
    }

    public function maxVowels(DifficultyProfile $difficultyProfile): int
    {
        return (int) ($difficultyProfile->metadata['max_vowels'] ?? min(6, max($this->minVowels($difficultyProfile), $difficultyProfile->vowelsCount)));
    }

    /**
     * @param  array<int, string>  $letters
     */
    public function remainingSlots(array $letters, DifficultyProfile $difficultyProfile): int
    {
        return max(0, $difficultyProfile->lettersCount - count($letters));
    }

    /**
     * @param  array<int, string>  $letters
     */
    public function countVowels(array $letters): int
    {
        return count(array_filter($letters, fn (string $letter) => $this->letterPoolService->isVowel($letter)));
    }

    /**
     * @param  array<int, string>  $letters
     */
    public function consecutiveConsonants(array $letters): int
    {
        $run = 0;

        for ($index = count($letters) - 1; $index >= 0; $index--) {
            if ($this->letterPoolService->isVowel($letters[$index])) {
                break;
            }

            $run++;
        }

        return $run;
    }

    /**
     * @param  array<int, string>  $letters
     */
    private function longestConsonantRun(array $letters): int
    {
        $longest = 0;
        $current = 0;

        foreach ($letters as $letter) {
            if ($this->letterPoolService->isVowel($letter)) {
                $current = 0;
                continue;
            }

            $current++;
            $longest = max($longest, $current);
        }

        return $longest;
    }

    /**
     * @param  array<int, string>  $letters
     */
    private function consecutiveHardLetters(array $letters): int
    {
        $run = 0;

        for ($index = count($letters) - 1; $index >= 0; $index--) {
            if (! $this->letterPoolService->isRareLetter($letters[$index])) {
                break;
            }

            $run++;
        }

        return $run;
    }

    private function maxConsecutiveConsonants(DifficultyProfile $difficultyProfile): int
    {
        return (int) ($difficultyProfile->metadata['max_consecutive_consonants'] ?? 3);
    }

    private function maxConsecutiveHardLetters(DifficultyProfile $difficultyProfile): int
    {
        return (int) ($difficultyProfile->metadata['max_consecutive_hard_letters'] ?? 1);
    }

    private function maxDuplicateCount(string $letter): int
    {
        return match ($letter) {
            'E' => 4,
            'A', 'I', 'N', 'R', 'S', 'T', 'O', 'L', 'U' => 3,
            'D', 'C', 'M', 'P', 'G' => 2,
            default => 1,
        };
    }

    /**
     * @param  array<int, string>  $letters
     */
    private function choiceKeepsDrawFeasible(string $type, array $letters, DifficultyProfile $difficultyProfile): bool
    {
        $remainingAfterChoice = $this->remainingSlots($letters, $difficultyProfile) - 1;
        $vowelCount = $this->countVowels($letters);
        $newVowelCount = $type === 'vowel' ? $vowelCount + 1 : $vowelCount;

        if ($newVowelCount > $this->maxVowels($difficultyProfile)) {
            return false;
        }

        if ($newVowelCount + max(0, $remainingAfterChoice) < $this->minVowels($difficultyProfile)) {
            return false;
        }

        return true;
    }

    /**
     * @param  array<int, string>  $letters
     */
    private function baseFeasibleChoiceTypes(array $letters, DifficultyProfile $difficultyProfile): array
    {
        $remainingSlots = $this->remainingSlots($letters, $difficultyProfile);
        $vowelCount = $this->countVowels($letters);
        $minVowels = $this->minVowels($difficultyProfile);
        $maxVowels = $this->maxVowels($difficultyProfile);
        $vowelsStillNeeded = max(0, $minVowels - $vowelCount);

        if ($vowelsStillNeeded >= $remainingSlots) {
            return ['vowel'];
        }

        if ($vowelCount >= $maxVowels) {
            return ['consonant'];
        }

        $allowed = [];

        if ($vowelCount < $maxVowels) {
            $allowed[] = 'vowel';
        }

        if (($remainingSlots - 1) >= $vowelsStillNeeded) {
            $allowed[] = 'consonant';
        }

        if ($allowed !== []) {
            return $allowed;
        }

        return $vowelsStillNeeded > 0 ? ['vowel'] : ['consonant'];
    }

    /**
     * @param  array<int, string>  $letters
     */
    private function isLetterAllowed(string $letter, array $letters, DifficultyProfile $difficultyProfile, int $relaxationLevel): bool
    {
        $counts = array_count_values($letters);
        $isVowel = $this->letterPoolService->isVowel($letter);
        $rareLettersCount = count(array_filter($letters, fn (string $existingLetter) => $this->letterPoolService->isRareLetter($existingLetter)));
        $maxRareLetters = (int) ($difficultyProfile->metadata['max_rare_letters'] ?? 1);

        if (($counts[$letter] ?? 0) >= $this->maxDuplicateCount($letter)) {
            return false;
        }

        if (! $this->choiceKeepsDrawFeasible($isVowel ? 'vowel' : 'consonant', $letters, $difficultyProfile)) {
            return false;
        }

        if ($isVowel && $this->countVowels($letters) >= $this->maxVowels($difficultyProfile)) {
            return false;
        }

        if (! $isVowel && $relaxationLevel < 1 && $this->consecutiveConsonants($letters) >= $this->maxConsecutiveConsonants($difficultyProfile)) {
            return false;
        }

        if ($this->letterPoolService->isRareLetter($letter) && $relaxationLevel < 2 && $rareLettersCount >= $maxRareLetters) {
            return false;
        }

        if ($this->letterPoolService->isRareLetter($letter) && $relaxationLevel < 1 && $this->consecutiveHardLetters($letters) >= $this->maxConsecutiveHardLetters($difficultyProfile)) {
            return false;
        }

        if ($letter === 'Q' && ! in_array('U', $letters, true) && ! $this->choiceKeepsDrawFeasible('vowel', $letters, $difficultyProfile)) {
            return false;
        }

        if (! $isVowel && $relaxationLevel >= 3 && $this->letterPoolService->isRareLetter($letter) && $rareLettersCount >= $maxRareLetters) {
            return false;
        }

        return true;
    }

    /**
     * @param  array<int, string>  $letters
     * @return array<int, string>|null
     */
    private function repairVowelBounds(array $letters, DifficultyProfile $difficultyProfile): ?array
    {
        while ($this->countVowels($letters) > $this->maxVowels($difficultyProfile)) {
            $index = $this->lastIndexMatching($letters, fn (string $letter) => $this->letterPoolService->isVowel($letter));

            if ($index === null) {
                return null;
            }

            $baseLetters = $letters;
            unset($baseLetters[$index]);
            $baseLetters = array_values($baseLetters);
            $replacement = $this->pickRepairReplacement('consonant', $baseLetters, $difficultyProfile, true);

            if ($replacement === null) {
                return null;
            }

            $letters[$index] = $replacement;
        }

        while ($this->countVowels($letters) < $this->minVowels($difficultyProfile)) {
            $index = $this->lastIndexMatching($letters, fn (string $letter) => ! $this->letterPoolService->isVowel($letter));

            if ($index === null) {
                return null;
            }

            $baseLetters = $letters;
            unset($baseLetters[$index]);
            $baseLetters = array_values($baseLetters);
            $replacement = $this->pickRepairReplacement('vowel', $baseLetters, $difficultyProfile, false);

            if ($replacement === null) {
                return null;
            }

            $letters[$index] = $replacement;
        }

        return array_values($letters);
    }

    /**
     * @param  array<int, string>  $letters
     * @return array<int, string>|null
     */
    private function repairRareLetters(array $letters, DifficultyProfile $difficultyProfile): ?array
    {
        $maxRareLetters = (int) ($difficultyProfile->metadata['max_rare_letters'] ?? 1);

        while (count(array_filter($letters, fn (string $letter) => $this->letterPoolService->isRareLetter($letter))) > $maxRareLetters) {
            $index = $this->lastIndexMatching($letters, fn (string $letter) => $this->letterPoolService->isRareLetter($letter));

            if ($index === null) {
                return null;
            }

            $type = $this->letterPoolService->isVowel($letters[$index]) ? 'vowel' : 'consonant';
            $baseLetters = $letters;
            unset($baseLetters[$index]);
            $baseLetters = array_values($baseLetters);
            $replacement = $this->pickRepairReplacement($type, $baseLetters, $difficultyProfile, true);

            if ($replacement === null) {
                return null;
            }

            $letters[$index] = $replacement;
        }

        return array_values($letters);
    }

    /**
     * @param  array<int, string>  $letters
     * @return array<int, string>|null
     */
    private function reorderForPlayableRuns(array $letters, DifficultyProfile $difficultyProfile): ?array
    {
        $ordered = $this->arrangeLetters($letters, $difficultyProfile);

        return $ordered !== null && $this->isCompletedDrawValid($ordered, $difficultyProfile) ? $ordered : null;
    }

    private function canReachValidConsonantDistribution(int $currentVowels, int $remainingSlots, DifficultyProfile $difficultyProfile): bool
    {
        $minFinalVowels = max($this->minVowels($difficultyProfile), $currentVowels);
        $maxFinalVowels = min($this->maxVowels($difficultyProfile), $currentVowels + $remainingSlots);

        for ($finalVowels = $minFinalVowels; $finalVowels <= $maxFinalVowels; $finalVowels++) {
            $finalConsonants = $difficultyProfile->lettersCount - $finalVowels;

            if ($this->canArrangeConsonantsWithVowels($finalVowels, $finalConsonants, $difficultyProfile)) {
                return true;
            }
        }

        return false;
    }

    private function canArrangeConsonantsWithVowels(int $vowelCount, int $consonantCount, DifficultyProfile $difficultyProfile): bool
    {
        return $consonantCount <= (($vowelCount + 1) * $this->maxConsecutiveConsonants($difficultyProfile));
    }

    /**
     * @return array<int, string>|null
     */
    private function buildPlayableTypePattern(int $vowelCount, int $consonantCount, DifficultyProfile $difficultyProfile): ?array
    {
        $maxRun = $this->maxConsecutiveConsonants($difficultyProfile);
        $memo = [];

        $build = function (int $remainingVowels, int $remainingConsonants, int $currentConsonantRun) use (&$build, &$memo, $maxRun): ?array {
            $key = $remainingVowels.'|'.$remainingConsonants.'|'.$currentConsonantRun;

            if (array_key_exists($key, $memo)) {
                return $memo[$key];
            }

            if ($remainingVowels === 0 && $remainingConsonants === 0) {
                return $memo[$key] = [];
            }

            if ($remainingVowels > 0) {
                $suffix = $build($remainingVowels - 1, $remainingConsonants, 0);

                if ($suffix !== null) {
                    array_unshift($suffix, 'vowel');

                    return $memo[$key] = $suffix;
                }
            }

            if ($remainingConsonants > 0 && $currentConsonantRun < $maxRun) {
                $suffix = $build($remainingVowels, $remainingConsonants - 1, $currentConsonantRun + 1);

                if ($suffix !== null) {
                    array_unshift($suffix, 'consonant');

                    return $memo[$key] = $suffix;
                }
            }

            return $memo[$key] = null;
        };

        return $build($vowelCount, $consonantCount, 0);
    }

    /**
     * @param  array<int, string>  $letters
     */
    private function pickRepairReplacement(string $type, array $letters, DifficultyProfile $difficultyProfile, bool $avoidRare): ?string
    {
        foreach ($this->letterPoolService->weightsForType($type) as $candidate => $_weight) {
            if ($avoidRare && $this->letterPoolService->isRareLetter($candidate)) {
                continue;
            }

            $simulated = [...$letters, $candidate];
            $counts = array_count_values($simulated);

            if (($counts[$candidate] ?? 0) > $this->maxDuplicateCount($candidate)) {
                continue;
            }

            $vowelCount = $this->countVowels($simulated);
            $rareCount = count(array_filter($simulated, fn (string $letter) => $this->letterPoolService->isRareLetter($letter)));

            if ($vowelCount < $this->minVowels($difficultyProfile) || $vowelCount > $this->maxVowels($difficultyProfile)) {
                continue;
            }

            if ($rareCount > (int) ($difficultyProfile->metadata['max_rare_letters'] ?? 1)) {
                continue;
            }

            return $candidate;
        }

        return null;
    }

    /**
     * @param  array<int, string>  $letters
     */
    private function lastIndexMatching(array $letters, callable $predicate): ?int
    {
        for ($index = count($letters) - 1; $index >= 0; $index--) {
            if ($predicate($letters[$index])) {
                return $index;
            }
        }

        return null;
    }
}
