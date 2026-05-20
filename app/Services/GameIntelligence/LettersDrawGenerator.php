<?php

namespace App\Services\GameIntelligence;

use App\Models\AgeGroup;
use App\Models\Word;
use App\Services\GameIntelligence\DTOs\DifficultyProfile;
use App\Services\WordValidationService;

class LettersDrawGenerator
{
    /**
     * @var array<string, \Illuminate\Support\Collection<int, Word>>
     */
    private array $seedWordCache = [];

    public function __construct(
        private readonly WordValidationService $wordValidationService,
    ) {
    }

    /**
     * Generate a candidate letters draw that already contains one age-allowed seed word.
     *
     * @return array{letters: array<int, string>, seed_word: string|null}
     */
    public function generate(AgeGroup $ageGroup, DifficultyProfile $difficultyProfile): array
    {
        $letters = [];
        $seedWord = $this->pickSeedWord($ageGroup, $difficultyProfile);
        $minVowels = (int) ($difficultyProfile->metadata['min_vowels'] ?? $difficultyProfile->vowelsCount);
        $maxVowels = (int) ($difficultyProfile->metadata['max_vowels'] ?? $difficultyProfile->vowelsCount);

        if ($seedWord !== null) {
            $letters = str_split($seedWord->normalized_word);
        }

        $remainingSlots = max(0, $difficultyProfile->lettersCount - count($letters));
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

        shuffle($letters);

        return [
            'letters' => $letters,
            'seed_word' => $seedWord?->word,
            'vowel_count' => $currentVowels,
            'rare_letters_count' => count(array_filter($letters, fn (string $letter) => $this->isRareLetter($letter))),
        ];
    }

    private function pickSeedWord(AgeGroup $ageGroup, DifficultyProfile $difficultyProfile): ?Word
    {
        $cacheKey = $ageGroup->id.'-'.$difficultyProfile->lettersCount;

        if (! isset($this->seedWordCache[$cacheKey])) {
            $this->seedWordCache[$cacheKey] = Word::query()
                ->where('length', '<=', $difficultyProfile->lettersCount)
                ->get()
                ->filter(fn (Word $word) => $this->wordValidationService->isAllowedForAgeGroup($word, $ageGroup))
                ->sortByDesc(fn (Word $word) => (($word->frequency ?? 0) * 1000) + ($word->length * 100))
                ->values();
        }

        /** @var \Illuminate\Support\Collection<int, Word> $words */
        $words = $this->seedWordCache[$cacheKey];

        $preferredWords = $words
            ->filter(fn (Word $word) => $word->length >= ($difficultyProfile->metadata['seed_min_length'] ?? 0))
            ->values();

        $pool = $preferredWords->isNotEmpty() ? $preferredWords : $words;

        if ($pool->isEmpty()) {
            return null;
        }

        return $pool->random();
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
}
