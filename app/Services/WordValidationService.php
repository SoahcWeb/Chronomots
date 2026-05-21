<?php

namespace App\Services;

use App\Models\AgeGroup;
use App\Models\Word;

class WordValidationService
{
    public function __construct(
        private readonly WordNormalizerService $wordNormalizerService,
    ) {
    }

    /**
     * Normalize a player word for gameplay comparisons.
     * The returned form stays uppercase to preserve the current UI/game flow.
     */
    public function normalize(string $word): string
    {
        return strtoupper($this->wordNormalizerService->normalize($word));
    }

    /**
     * Find a word by its normalized value.
     */
    public function findWord(string $word): ?Word
    {
        $normalizedWord = $this->wordNormalizerService->normalize($word);

        if ($normalizedWord === '') {
            return null;
        }

        return Word::query()
            ->where('normalized_word', $normalizedWord)
            ->first();
    }

    /**
     * Check whether a word exists in the current dictionary.
     */
    public function exists(string $word): bool
    {
        return $this->findWord($word) !== null;
    }

    /**
     * Check whether a dictionary word is allowed for a given age group.
     */
    public function isAllowedForAgeGroup(Word $word, AgeGroup $ageGroup): bool
    {
        if ($word->age_level === null) {
            return true;
        }

        return $this->ageLevelRank($word->age_level) <= $this->ageGroupRank($ageGroup);
    }

    /**
     * Validate a word against dictionary existence and age restrictions.
     *
     * @return array{valid: bool, normalized_word: string, word: ?Word, message: ?string}
     */
    public function validateForAgeGroup(string $word, AgeGroup $ageGroup): array
    {
        $normalizedWord = $this->normalize($word);

        if ($normalizedWord === '') {
            return [
                'valid' => false,
                'normalized_word' => '',
                'word' => null,
                'message' => 'Le mot doit contenir uniquement des lettres.',
            ];
        }

        $dictionaryWord = $this->findWord($normalizedWord);

        if (! $dictionaryWord) {
            return [
                'valid' => false,
                'normalized_word' => $normalizedWord,
                'word' => null,
                'message' => 'Le mot proposé n’existe pas encore dans le dictionnaire de Chronomots.',
            ];
        }

        if (! $this->isAllowedForAgeGroup($dictionaryWord, $ageGroup)) {
            return [
                'valid' => false,
                'normalized_word' => $normalizedWord,
                'word' => $dictionaryWord,
                'message' => 'Ce mot n’est pas encore autorisé pour cette catégorie d’âge.',
            ];
        }

        return [
            'valid' => true,
            'normalized_word' => $normalizedWord,
            'word' => $dictionaryWord,
            'message' => null,
        ];
    }

    /**
     * Convert a dictionary age level into an ordered rank.
     */
    private function ageLevelRank(string $ageLevel): int
    {
        return match ($ageLevel) {
            '7-9' => 1,
            '10-13' => 2,
            '14+' => 3,
            default => 3,
        };
    }

    /**
     * Convert an age group into an ordered rank.
     */
    private function ageGroupRank(AgeGroup $ageGroup): int
    {
        return match (true) {
            $ageGroup->min_age >= 14 => 3,
            $ageGroup->min_age >= 10 => 2,
            default => 1,
        };
    }
}
