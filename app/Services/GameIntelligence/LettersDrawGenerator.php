<?php

namespace App\Services\GameIntelligence;

use App\Models\AgeGroup;
use App\Models\Word;
use App\Services\GameIntelligence\DTOs\DifficultyProfile;
use Illuminate\Support\Collection;

class LettersDrawGenerator
{
    private const DEFAULT_SEED_POOL_PER_LENGTH = 80;

    private const DEFAULT_SEED_EVALUATION_LIMIT = 10;

    private const DEFAULT_SEED_SOLUTION_CHECK_LIMIT = 250;

    /**
     * @var array<string, Collection<int, Word>>
     */
    private array $seedWordCache = [];

    public function __construct(
        private readonly LettersWordPoolService $lettersWordPoolService,
    ) {
    }

    /**
     * Generate a candidate letters draw that tries a bounded "smart" seed first.
     * If nothing convincing is found quickly, fall back to a balanced raw draw.
     *
     * @return array{letters: array<int, string>, seed_word: string|null, vowel_count: int, rare_letters_count: int}
     */
    public function generate(AgeGroup $ageGroup, DifficultyProfile $difficultyProfile): array
    {
        $seedWord = $this->pickSeedWord($ageGroup, $difficultyProfile);
        $letters = $seedWord !== null ? str_split((string) $seedWord->normalized_word) : [];
        $letters = $this->fillRemainingLetters($letters, $difficultyProfile);

        shuffle($letters);

        return [
            'letters' => $letters,
            'seed_word' => $seedWord?->normalized_word,
            'vowel_count' => $this->countVowels($letters),
            'rare_letters_count' => count(array_filter($letters, fn (string $letter) => $this->isRareLetter($letter))),
        ];
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

            if ($candidate instanceof Word && $this->seedWordProvidesEnoughSolutions($candidate, $seedPool, $difficultyProfile)) {
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
        $availableCounts = array_count_values(str_split((string) $seedWord->normalized_word));
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
     * @param  array<int, string>  $letters
     * @return array<int, string>
     */
    private function fillRemainingLetters(array $letters, DifficultyProfile $difficultyProfile): array
    {
        $remainingSlots = max(0, $difficultyProfile->lettersCount - count($letters));
        $minVowels = (int) ($difficultyProfile->metadata['min_vowels'] ?? $difficultyProfile->vowelsCount);
        $maxVowels = (int) ($difficultyProfile->metadata['max_vowels'] ?? $difficultyProfile->vowelsCount);
        $targetVowels = min($maxVowels, max($minVowels, $difficultyProfile->vowelsCount));
        $currentVowels = $this->countVowels($letters);

        for ($index = 0; $index < $remainingSlots; $index++) {
            $remainingLetters = $remainingSlots - $index;
            $vowelsStillNeeded = max(0, $targetVowels - $currentVowels);
            $canStillUseVowel = $currentVowels < $maxVowels;

            if ($vowelsStillNeeded >= $remainingLetters && $canStillUseVowel) {
                $letters[] = $this->pickWeightedLetter($this->vowelPool(), $letters, $difficultyProfile);
                $currentVowels++;

                continue;
            }

            $chooseVowel = $canStillUseVowel && (
                ($vowelsStillNeeded > 0 && random_int(0, 100) < 55)
                || ($currentVowels < $minVowels && random_int(0, 100) < 70)
            );
            $letters[] = $this->pickWeightedLetter(
                $chooseVowel ? $this->vowelPool() : $this->consonantPool(),
                $letters,
                $difficultyProfile,
            );

            if ($this->isVowel($letters[array_key_last($letters)])) {
                $currentVowels++;
            }
        }

        return $letters;
    }

    /**
     * @param  array<int, string>  $letters
     */
    private function countVowels(array $letters): int
    {
        return count(array_filter($letters, fn (string $letter) => $this->isVowel($letter)));
    }

    private function isVowel(string $letter): bool
    {
        return in_array($letter, ['A', 'E', 'I', 'O', 'U', 'Y'], true);
    }

    /**
     * @return array<int, string>
     */
    private function vowelPool(): array
    {
        return [
            'E', 'E', 'E', 'E', 'E', 'E',
            'A', 'A', 'A', 'A',
            'I', 'I', 'I',
            'O', 'O', 'O',
            'U', 'U',
            'Y',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function consonantPool(): array
    {
        return [
            'S', 'S', 'S', 'S',
            'T', 'T', 'T',
            'N', 'N', 'N',
            'R', 'R', 'R',
            'L', 'L', 'L',
            'M', 'M',
            'D', 'D',
            'C', 'C',
            'P', 'P',
            'B', 'B',
            'F', 'F',
            'G', 'G',
            'H',
            'V',
            'J',
            'Q',
            'K',
            'W',
            'X',
            'Z',
        ];
    }

    /**
     * @param  array<int, string>  $pool
     * @param  array<int, string>  $existingLetters
     */
    private function pickWeightedLetter(array $pool, array $existingLetters, DifficultyProfile $difficultyProfile): string
    {
        $filteredPool = array_values(array_filter(
            $pool,
            fn (string $letter) => $this->canUseLetter($letter, $existingLetters, $difficultyProfile),
        ));

        if ($filteredPool === []) {
            $filteredPool = $pool;
        }

        return $filteredPool[random_int(0, count($filteredPool) - 1)];
    }

    /**
     * Avoid over-repeating letters and stacking too many awkward consonants.
     *
     * @param  array<int, string>  $existingLetters
     */
    private function canUseLetter(string $letter, array $existingLetters, DifficultyProfile $difficultyProfile): bool
    {
        $counts = array_count_values($existingLetters);
        $rareLettersCount = count(array_filter($existingLetters, fn (string $existingLetter) => $this->isRareLetter($existingLetter)));
        $maxRareLetters = (int) ($difficultyProfile->metadata['max_rare_letters'] ?? 1);
        $maxDuplicateCount = $this->maxDuplicateCount($letter);

        if (($counts[$letter] ?? 0) >= $maxDuplicateCount) {
            return false;
        }

        if ($this->isRareLetter($letter) && $rareLettersCount >= $maxRareLetters) {
            return false;
        }

        if ($letter === 'Q' && ! in_array('U', $existingLetters, true) && ($counts['Q'] ?? 0) >= 0) {
            return $rareLettersCount < $maxRareLetters;
        }

        return true;
    }

    private function maxDuplicateCount(string $letter): int
    {
        return match ($letter) {
            'E' => 4,
            'A', 'S', 'T', 'N', 'R', 'I', 'O', 'L' => 3,
            'U', 'M', 'D', 'C', 'P' => 2,
            default => 1,
        };
    }

    private function isRareLetter(string $letter): bool
    {
        return in_array($letter, ['J', 'K', 'Q', 'W', 'X', 'Y', 'Z'], true);
    }

    /**
     * @param  array<string, int>  $availableCounts
     */
    private function wordFitsCounts(string $word, array $availableCounts): bool
    {
        $wordCounts = array_count_values(str_split($word));

        foreach ($wordCounts as $letter => $count) {
            if (($availableCounts[$letter] ?? 0) < $count) {
                return false;
            }
        }

        return true;
    }
}
