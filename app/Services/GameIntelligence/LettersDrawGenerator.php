<?php

namespace App\Services\GameIntelligence;

use App\Models\AgeGroup;
use App\Models\Word;
use App\Services\GameIntelligence\DTOs\DifficultyProfile;
use Illuminate\Support\Collection;

class LettersDrawGenerator
{
    /**
     * @var array<string, array<int, string>>
     */
    private const CURATED_SAFE_SEED_WORDS = [
        '7-9' => ['SOLEIL', 'CHIEN', 'ROBOT', 'MOTOS', 'LUNES', 'CHATS'],
        '10-13' => ['ORANGE', 'TOMATE', 'MAISON', 'LOGIQUE', 'CALCUL', 'JARDIN', 'PIRATE', 'NOMBRE', 'VITESSE', 'QUESTION', 'TRIANGLE'],
        '14+' => ['STRATEGIE', 'REFLEXION', 'EQUATION', 'ALGEBRE', 'VARIABLE', 'QUOTIENT', 'PRECISION', 'CONSONNE', 'VOYELLE'],
    ];

    private const DEFAULT_SEED_POOL_PER_LENGTH = 80;

    private const DEFAULT_SEED_EVALUATION_LIMIT = 10;

    private const DEFAULT_SEED_SOLUTION_CHECK_LIMIT = 250;

    private const DEFAULT_FALLBACK_SEED_CANDIDATE_LIMIT = 12;

    private const MAX_FULL_DRAW_ATTEMPTS = 6;

    /**
     * @var array<string, Collection<int, Word>>
     */
    private array $seedWordCache = [];

    public function __construct(
        private readonly DrawConstraintService $drawConstraintService,
        private readonly LettersWordPoolService $lettersWordPoolService,
        private readonly LettersSolvabilityService $lettersSolvabilityService,
        private readonly WeightedLetterGenerator $weightedLetterGenerator,
        private readonly LetterPoolService $letterPoolService,
    ) {
    }

    /**
     * Generate a complete letters draw for automatic flows like AI analysis and daily challenges.
     *
     * @return array{letters: array<int, string>, seed_word: string|null, vowel_count: int, rare_letters_count: int, choice_history: array<int, string>}
     */
    public function generate(AgeGroup $ageGroup, DifficultyProfile $difficultyProfile): array
    {
        for ($attempt = 0; $attempt < self::MAX_FULL_DRAW_ATTEMPTS; $attempt++) {
            $state = $this->startInteractiveDraw($ageGroup, $difficultyProfile);

            while (! $this->isComplete($state, $difficultyProfile)) {
                $choice = $this->pickAutomaticChoiceType($state, $difficultyProfile);
                $state = $this->revealNextLetter($state, $choice, $difficultyProfile);
            }

            $state = $this->repairCompletedState($state, $difficultyProfile);

            if ($this->drawConstraintService->isCompletedDrawValid($state['letters'], $difficultyProfile)) {
                return $this->finalizeState($state, $difficultyProfile);
            }
        }

        return $this->generateSafeFallbackDraw($difficultyProfile);
    }

    /**
     * Build a solvability-first fallback by preserving a frequent age-appropriate seed word.
     *
     * @return array{letters: array<int, string>, seed_word: string|null, vowel_count: int, rare_letters_count: int, choice_history: array<int, string>}|null
     */
    public function generateSeededFallback(AgeGroup $ageGroup, DifficultyProfile $difficultyProfile): ?array
    {
        $seedWords = $this->seedWordCandidates($ageGroup, $difficultyProfile)
            ->pluck('normalized_word')
            ->map(fn (string $word) => strtoupper($word))
            ->values()
            ->all();

        return $this->generateFallbackFromSeedWords($seedWords, $ageGroup, $difficultyProfile);
    }

    /**
     * Build a fallback from an internal curated safe-word list by age.
     *
     * @return array{letters: array<int, string>, seed_word: string|null, vowel_count: int, rare_letters_count: int, choice_history: array<int, string>}|null
     */
    public function generateCuratedFallback(AgeGroup $ageGroup, DifficultyProfile $difficultyProfile): ?array
    {
        $seedWords = collect($this->curatedSafeSeedWords($ageGroup))
            ->filter(fn (string $word) => strlen($word) >= $difficultyProfile->minBestLength)
            ->filter(fn (string $word) => strlen($word) <= $difficultyProfile->lettersCount)
            ->values()
            ->all();

        return $this->generateFallbackFromSeedWords($seedWords, $ageGroup, $difficultyProfile);
    }

    /**
     * Expose the deterministic constraint-safe fallback for last-resort recovery.
     *
     * @return array{letters: array<int, string>, seed_word: string|null, vowel_count: int, rare_letters_count: int, choice_history: array<int, string>}
     */
    public function generateSafeFallback(DifficultyProfile $difficultyProfile): array
    {
        return $this->generateSafeFallbackDraw($difficultyProfile);
    }

    /**
     * @return array<string, mixed>
     */
    public function startInteractiveDraw(AgeGroup $ageGroup, DifficultyProfile $difficultyProfile): array
    {
        $seedWord = $this->pickSeedWord($ageGroup, $difficultyProfile);
        $state = [
            'letters' => [],
            'choice_history' => [],
            'seed_word' => $seedWord ? strtoupper((string) $seedWord->normalized_word) : null,
            'seed_letters_remaining' => $seedWord ? str_split(strtoupper((string) $seedWord->normalized_word)) : [],
            'letters_target' => $difficultyProfile->lettersCount,
            'latest_letter' => null,
            'requested_choice_type' => null,
            'applied_choice_type' => null,
            'was_choice_overridden' => false,
        ];

        return [
            ...$state,
            ...$this->drawConstraintService->summarize($state['letters'], $difficultyProfile),
            'is_complete' => false,
        ];
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    public function revealNextLetter(array $state, string $choiceType, DifficultyProfile $difficultyProfile): array
    {
        $letters = array_values($state['letters'] ?? []);
        $requestedChoiceType = $choiceType;
        $allowedChoiceTypes = $this->drawConstraintService->allowedChoiceTypes($letters, $difficultyProfile);

        if ($allowedChoiceTypes === []) {
            $allowedChoiceTypes = $this->drawConstraintService->remainingSlots($letters, $difficultyProfile) > 0
                ? [$this->fallbackChoiceType($letters, $difficultyProfile)]
                : [];
        }

        if ($allowedChoiceTypes === []) {
            return [
                ...$state,
                'allowed_choices' => [],
                'requested_choice_type' => $requestedChoiceType,
                'applied_choice_type' => null,
                'was_choice_overridden' => false,
                'is_complete' => $this->isComplete(['letters' => $letters], $difficultyProfile),
            ];
        }

        if (! in_array($choiceType, $allowedChoiceTypes, true)) {
            $choiceType = $allowedChoiceTypes[0] ?? 'vowel';
        }

        $preferredLetters = $this->preferredLettersForType($choiceType, $state['seed_letters_remaining'] ?? []);
        $letter = $this->weightedLetterGenerator->pick($choiceType, $letters, $difficultyProfile, $preferredLetters);
        $appliedChoiceType = $this->letterPoolService->isVowel($letter) ? 'vowel' : 'consonant';
        $letters[] = $letter;

        $updatedSeedLetters = $this->removeLetterFromSeed($letter, $state['seed_letters_remaining'] ?? []);
        $choiceHistory = array_values($state['choice_history'] ?? []);
        $choiceHistory[] = $appliedChoiceType;

        $updatedState = [
            ...$state,
            'letters' => $letters,
            'choice_history' => $choiceHistory,
            'seed_letters_remaining' => $updatedSeedLetters,
            'latest_letter' => $letter,
            'requested_choice_type' => $requestedChoiceType,
            'applied_choice_type' => $appliedChoiceType,
            'was_choice_overridden' => $requestedChoiceType !== $appliedChoiceType,
        ];

        if (count($letters) >= $difficultyProfile->lettersCount) {
            $updatedState = $this->repairCompletedState($updatedState, $difficultyProfile);
            $letters = array_values($updatedState['letters'] ?? []);
        }

        $summary = $this->drawConstraintService->summarize($letters, $difficultyProfile);

        return [
            ...$updatedState,
            ...$summary,
            'is_complete' => $this->isComplete(['letters' => $letters], $difficultyProfile),
        ];
    }

    /**
     * @param  array<string, mixed>  $state
     */
    public function isComplete(array $state, DifficultyProfile $difficultyProfile): bool
    {
        return count($state['letters'] ?? []) >= ($state['letters_target'] ?? $difficultyProfile->lettersCount);
    }

    private function pickSeedWord(AgeGroup $ageGroup, DifficultyProfile $difficultyProfile): ?Word
    {
        $seedMinLength = min(
            $difficultyProfile->lettersCount,
            max(2, (int) ($difficultyProfile->metadata['seed_min_length'] ?? $difficultyProfile->minBestLength)),
        );
        $cacheKey = $ageGroup->id.'-'.$difficultyProfile->lettersCount.'-'.$seedMinLength;

        if (! isset($this->seedWordCache[$cacheKey])) {
            $perLengthLimit = (int) ($difficultyProfile->metadata['seed_pool_per_length'] ?? self::DEFAULT_SEED_POOL_PER_LENGTH);
            $this->seedWordCache[$cacheKey] = $this->lettersWordPoolService
                ->frequentWords($ageGroup, $difficultyProfile->lettersCount, $perLengthLimit)
                ->filter(fn (Word $word) => $word->length >= $seedMinLength)
                ->values();
        }

        $seedPool = $this->seedWordCache[$cacheKey];

        if ($seedPool->isEmpty()) {
            return null;
        }

        $seedCandidates = $seedPool->shuffle()->values();
        $maxEvaluations = min(
            $seedCandidates->count(),
            max(1, (int) ($difficultyProfile->metadata['seed_evaluation_limit'] ?? self::DEFAULT_SEED_EVALUATION_LIMIT)),
        );

        for ($attempt = 0; $attempt < $maxEvaluations; $attempt++) {
            $candidate = $seedCandidates->get($attempt);

            if (
                $candidate instanceof Word
                && $this->seedWordProvidesEnoughSolutions($candidate, $seedPool, $difficultyProfile)
                && $this->seedWordSupportsConstraints($candidate, $difficultyProfile)
            ) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @param  Collection<int, Word>  $wordPool
     */
    private function seedWordProvidesEnoughSolutions(Word $seedWord, Collection $wordPool, DifficultyProfile $difficultyProfile): bool
    {
        $availableCounts = array_count_values(str_split(strtoupper((string) $seedWord->normalized_word)));
        $solutionsCount = 0;
        $hasTargetWord = false;
        $inspectedWords = 0;
        $maxChecks = max(
            $difficultyProfile->minSolutions,
            (int) ($difficultyProfile->metadata['seed_solution_check_limit'] ?? self::DEFAULT_SEED_SOLUTION_CHECK_LIMIT),
        );

        foreach ($wordPool as $word) {
            $inspectedWords++;

            if (! $this->wordFitsCounts((string) $word->normalized_word, $availableCounts)) {
                if ($inspectedWords >= $maxChecks) {
                    break;
                }

                continue;
            }

            $solutionsCount++;

            if ($word->length >= $difficultyProfile->minBestLength) {
                $hasTargetWord = true;
            }

            if ($solutionsCount >= $difficultyProfile->minSolutions && $hasTargetWord) {
                return true;
            }

            if ($inspectedWords >= $maxChecks) {
                break;
            }
        }

        return false;
    }

    /**
     * @return Collection<int, Word>
     */
    private function seedWordCandidates(AgeGroup $ageGroup, DifficultyProfile $difficultyProfile): Collection
    {
        $seedMinLength = min(
            $difficultyProfile->lettersCount,
            max(2, (int) ($difficultyProfile->metadata['seed_min_length'] ?? $difficultyProfile->minBestLength)),
        );
        $cacheKey = $ageGroup->id.'-'.$difficultyProfile->lettersCount.'-'.$seedMinLength;

        if (! isset($this->seedWordCache[$cacheKey])) {
            $perLengthLimit = (int) ($difficultyProfile->metadata['seed_pool_per_length'] ?? self::DEFAULT_SEED_POOL_PER_LENGTH);
            $this->seedWordCache[$cacheKey] = $this->lettersWordPoolService
                ->frequentWords($ageGroup, $difficultyProfile->lettersCount, $perLengthLimit)
                ->filter(fn (Word $word) => $word->length >= $seedMinLength)
                ->values();
        }

        $limit = max(1, (int) ($difficultyProfile->metadata['fallback_seed_candidate_limit'] ?? self::DEFAULT_FALLBACK_SEED_CANDIDATE_LIMIT));

        return $this->seedWordCache[$cacheKey]
            ->filter(fn (Word $word) => $this->seedWordProvidesEnoughSolutions($word, $this->seedWordCache[$cacheKey], $difficultyProfile))
            ->filter(fn (Word $word) => $this->seedWordSupportsConstraints($word, $difficultyProfile))
            ->take($limit)
            ->values();
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function pickAutomaticChoiceType(array $state, DifficultyProfile $difficultyProfile): string
    {
        $letters = array_values($state['letters'] ?? []);
        $allowedChoiceTypes = $this->drawConstraintService->allowedChoiceTypes($letters, $difficultyProfile);

        if ($allowedChoiceTypes === []) {
            return $this->fallbackChoiceType($letters, $difficultyProfile);
        }

        if (count($allowedChoiceTypes) === 1) {
            return $allowedChoiceTypes[0];
        }

        $targetVowels = max($this->drawConstraintService->minVowels($difficultyProfile), min($this->drawConstraintService->maxVowels($difficultyProfile), $difficultyProfile->vowelsCount));
        $currentVowels = $this->drawConstraintService->countVowels($letters);
        $remainingSlots = $this->drawConstraintService->remainingSlots($letters, $difficultyProfile);
        $seedLetters = $state['seed_letters_remaining'] ?? [];
        $seedVowels = count(array_filter($seedLetters, fn (string $letter) => $this->letterPoolService->isVowel($letter)));
        $seedConsonants = count($seedLetters) - $seedVowels;

        if ($currentVowels < $targetVowels && $remainingSlots <= max(1, $targetVowels - $currentVowels + 1)) {
            return 'vowel';
        }

        if ($seedVowels > $seedConsonants && in_array('vowel', $allowedChoiceTypes, true) && $currentVowels < $targetVowels) {
            return 'vowel';
        }

        if ($seedConsonants > $seedVowels && in_array('consonant', $allowedChoiceTypes, true)) {
            return 'consonant';
        }

        $vowelBias = max(35, min(65, (int) round(($targetVowels / max(1, $difficultyProfile->lettersCount)) * 100)));

        return in_array('vowel', $allowedChoiceTypes, true) && random_int(1, 100) <= $vowelBias
            ? 'vowel'
            : ($allowedChoiceTypes[0] ?? 'consonant');
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array{letters: array<int, string>, seed_word: string|null, vowel_count: int, rare_letters_count: int, choice_history: array<int, string>}
     */
    private function finalizeState(array $state, DifficultyProfile $difficultyProfile): array
    {
        $letters = array_values($state['letters'] ?? []);
        $summary = $this->drawConstraintService->summarize($letters, $difficultyProfile);

        return [
            'letters' => $letters,
            'seed_word' => $state['seed_word'] ?? null,
            'vowel_count' => $summary['vowel_count'],
            'rare_letters_count' => $summary['rare_letters_count'],
            'choice_history' => array_values($state['choice_history'] ?? []),
        ];
    }

    /**
     * @param  array<int, string>  $seedLetters
     * @return array<int, string>
     */
    private function preferredLettersForType(string $type, array $seedLetters): array
    {
        return array_values(array_filter(
            $seedLetters,
            fn (string $letter) => $type === 'vowel'
                ? $this->letterPoolService->isVowel($letter)
                : ! $this->letterPoolService->isVowel($letter),
        ));
    }

    /**
     * @param  array<int, string>  $seedLetters
     * @return array<int, string>
     */
    private function removeLetterFromSeed(string $letter, array $seedLetters): array
    {
        $index = array_search($letter, $seedLetters, true);

        if ($index === false) {
            return $seedLetters;
        }

        unset($seedLetters[$index]);

        return array_values($seedLetters);
    }

    /**
     * @param  array<string, int>  $availableCounts
     */
    private function wordFitsCounts(string $word, array $availableCounts): bool
    {
        $wordCounts = array_count_values(str_split(strtoupper($word)));

        foreach ($wordCounts as $letter => $count) {
            if (($availableCounts[$letter] ?? 0) < $count) {
                return false;
            }
        }

        return true;
    }

    /**
     * Build a deterministic safe draw if the smarter generation path exhausts its bounded retries.
     *
     * @return array{letters: array<int, string>, seed_word: string|null, vowel_count: int, rare_letters_count: int, choice_history: array<int, string>}
     */
    private function generateSafeFallbackDraw(DifficultyProfile $difficultyProfile): array
    {
        $state = [
            'letters' => [],
            'choice_history' => [],
            'seed_word' => null,
            'seed_letters_remaining' => [],
            'letters_target' => $difficultyProfile->lettersCount,
            'latest_letter' => null,
            'requested_choice_type' => null,
            'applied_choice_type' => null,
            'was_choice_overridden' => false,
        ];

        while (count($state['letters']) < $difficultyProfile->lettersCount) {
            $allowedChoiceTypes = $this->drawConstraintService->allowedChoiceTypes($state['letters'], $difficultyProfile);

            if ($allowedChoiceTypes === []) {
                $allowedChoiceTypes = [$this->fallbackChoiceType($state['letters'], $difficultyProfile)];
            }

            $choiceType = $allowedChoiceTypes[0];
            $state = $this->revealNextLetter($state, $choiceType, $difficultyProfile);
        }

        $state = $this->repairCompletedState($state, $difficultyProfile);

        if (! $this->drawConstraintService->isCompletedDrawValid($state['letters'], $difficultyProfile)) {
            return $this->buildDeterministicConstraintFallback($difficultyProfile);
        }

        return $this->finalizeState($state, $difficultyProfile);
    }

    /**
     * Choose a recoverable fallback type when secondary constraints conflict.
     *
     * @param  array<int, string>  $letters
     */
    private function fallbackChoiceType(array $letters, DifficultyProfile $difficultyProfile): string
    {
        $remainingSlots = $this->drawConstraintService->remainingSlots($letters, $difficultyProfile);
        $vowelCount = $this->drawConstraintService->countVowels($letters);
        $minVowels = $this->drawConstraintService->minVowels($difficultyProfile);
        $maxVowels = $this->drawConstraintService->maxVowels($difficultyProfile);
        $vowelsStillNeeded = max(0, $minVowels - $vowelCount);

        if ($vowelsStillNeeded >= $remainingSlots) {
            return 'vowel';
        }

        if ($vowelCount >= $maxVowels) {
            return 'consonant';
        }

        return $vowelsStillNeeded > 0 ? 'vowel' : 'consonant';
    }

    private function seedWordSupportsConstraints(Word $seedWord, DifficultyProfile $difficultyProfile): bool
    {
        return $this->drawConstraintService->canPartialDrawReachValidCompletion(
            str_split(strtoupper((string) $seedWord->normalized_word)),
            $difficultyProfile,
        );
    }

    /**
     * @param  array<int, string>  $seedWords
     * @return array{letters: array<int, string>, seed_word: string|null, vowel_count: int, rare_letters_count: int, choice_history: array<int, string>}|null
     */
    private function generateFallbackFromSeedWords(array $seedWords, AgeGroup $ageGroup, DifficultyProfile $difficultyProfile): ?array
    {
        foreach ($seedWords as $seedWord) {
            $normalizedSeedWord = strtoupper($seedWord);
            $seedLetters = str_split($normalizedSeedWord);

            if (strlen($normalizedSeedWord) < $difficultyProfile->minBestLength || strlen($normalizedSeedWord) > $difficultyProfile->lettersCount) {
                continue;
            }

            if (! $this->drawConstraintService->canPartialDrawReachValidCompletion($seedLetters, $difficultyProfile)) {
                continue;
            }

            $orderedSeedLetters = $this->drawConstraintService->arrangeLetters($seedLetters, $difficultyProfile) ?? $seedLetters;
            $state = [
                'letters' => $orderedSeedLetters,
                'choice_history' => array_map(
                    fn (string $letter) => $this->letterPoolService->isVowel($letter) ? 'vowel' : 'consonant',
                    $orderedSeedLetters,
                ),
                'seed_word' => $normalizedSeedWord,
                'seed_letters_remaining' => [],
                'letters_target' => $difficultyProfile->lettersCount,
                'latest_letter' => $orderedSeedLetters !== [] ? $orderedSeedLetters[array_key_last($orderedSeedLetters)] : null,
                'requested_choice_type' => null,
                'applied_choice_type' => null,
                'was_choice_overridden' => false,
            ];

            while (! $this->isComplete($state, $difficultyProfile)) {
                $choice = $this->pickAutomaticChoiceType($state, $difficultyProfile);
                $state = $this->revealNextLetter($state, $choice, $difficultyProfile);
            }

            $state = $this->repairCompletedState($state, $difficultyProfile);

            if (! $this->drawConstraintService->isCompletedDrawValid($state['letters'], $difficultyProfile)) {
                continue;
            }

            $payload = $this->finalizeState($state, $difficultyProfile);
            $report = $this->lettersSolvabilityService->analyze($payload['letters'], $ageGroup, $difficultyProfile);

            if ($report->valid) {
                return $payload;
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function curatedSafeSeedWords(AgeGroup $ageGroup): array
    {
        return match (true) {
            $ageGroup->min_age >= 14 => self::CURATED_SAFE_SEED_WORDS['14+'],
            $ageGroup->min_age >= 10 => self::CURATED_SAFE_SEED_WORDS['10-13'],
            default => self::CURATED_SAFE_SEED_WORDS['7-9'],
        };
    }

    /**
     * Return a constraint-valid fallback without throwing, even if the weighted path stalls.
     *
     * @return array{letters: array<int, string>, seed_word: string|null, vowel_count: int, rare_letters_count: int, choice_history: array<int, string>}
     */
    private function buildDeterministicConstraintFallback(DifficultyProfile $difficultyProfile): array
    {
        $letters = [];
        $choiceHistory = [];

        while (count($letters) < $difficultyProfile->lettersCount) {
            $allowedChoiceTypes = $this->drawConstraintService->allowedChoiceTypes($letters, $difficultyProfile);
            $choiceType = $allowedChoiceTypes[0] ?? $this->fallbackChoiceType($letters, $difficultyProfile);
            $candidates = $this->drawConstraintService->candidateLettersForType($choiceType, $letters, $difficultyProfile, 3);

            if ($candidates === []) {
                $alternateType = $choiceType === 'vowel' ? 'consonant' : 'vowel';
                $alternateCandidates = $this->drawConstraintService->candidateLettersForType($alternateType, $letters, $difficultyProfile, 3);

                if ($alternateCandidates !== []) {
                    $choiceType = $alternateType;
                    $candidates = $alternateCandidates;
                }
            }

            $letter = $candidates[0] ?? array_key_first($this->letterPoolService->weightsForType($choiceType)) ?? 'E';
            $letters[] = $letter;
            $choiceHistory[] = $this->letterPoolService->isVowel($letter) ? 'vowel' : 'consonant';
        }

        $state = $this->repairCompletedState([
            'letters' => $letters,
            'choice_history' => $choiceHistory,
            'seed_word' => null,
            'seed_letters_remaining' => [],
            'letters_target' => $difficultyProfile->lettersCount,
            'latest_letter' => $letters !== [] ? $letters[array_key_last($letters)] : null,
            'requested_choice_type' => null,
            'applied_choice_type' => null,
            'was_choice_overridden' => false,
        ], $difficultyProfile);

        return $this->finalizeState($state, $difficultyProfile);
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    private function repairCompletedState(array $state, DifficultyProfile $difficultyProfile): array
    {
        $letters = array_values($state['letters'] ?? []);

        if (count($letters) !== $difficultyProfile->lettersCount) {
            return $state;
        }

        $repairedLetters = $this->drawConstraintService->repairCompletedDraw($letters, $difficultyProfile);

        if ($repairedLetters === null) {
            return $state;
        }

        return [
            ...$state,
            'letters' => $repairedLetters,
        ];
    }
}
