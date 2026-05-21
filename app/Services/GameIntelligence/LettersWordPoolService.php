<?php

namespace App\Services\GameIntelligence;

use App\Models\AgeGroup;
use App\Models\Word;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class LettersWordPoolService
{
    /**
     * @var array<string, Collection<int, Word>>
     */
    private array $frequentWordCache = [];

    /**
     * Return a lightweight, age-filtered pool of frequent words by length.
     * This keeps the letters game responsive without loading the full dictionary.
     *
     * @return Collection<int, Word>
     */
    public function frequentWords(AgeGroup $ageGroup, int $maxLength, int $perLengthLimit = 120): Collection
    {
        $cacheKey = $ageGroup->id.'-'.$maxLength.'-'.$perLengthLimit;

        if (isset($this->frequentWordCache[$cacheKey])) {
            return $this->frequentWordCache[$cacheKey];
        }

        $words = collect();

        for ($length = $maxLength; $length >= 2; $length--) {
            $chunk = Word::query()
                ->select(['normalized_word', 'length', 'frequency', 'is_active'])
                ->where('is_active', true)
                ->where('length', $length)
                ->tap(fn (Builder $query) => $this->applyAgeGroupFilter($query, $ageGroup))
                ->orderByDesc('frequency')
                ->orderBy('normalized_word')
                ->limit($perLengthLimit)
                ->get();

            if ($chunk->isNotEmpty()) {
                $words = $words->concat($chunk);
            }
        }

        /** @var Collection<int, Word> $sortedWords */
        $sortedWords = $words
            ->sortByDesc(fn (Word $word) => ($word->length * 100000) + (int) ($word->frequency ?? 0))
            ->values();

        return $this->frequentWordCache[$cacheKey] = $sortedWords;
    }

    private function applyAgeGroupFilter(Builder $query, AgeGroup $ageGroup): void
    {
        $allowedAgeLevels = match (true) {
            $ageGroup->min_age >= 14 => ['7-9', '10-13', '14+'],
            $ageGroup->min_age >= 10 => ['7-9', '10-13'],
            default => ['7-9'],
        };

        $query->where(function (Builder $ageQuery) use ($allowedAgeLevels): void {
            $ageQuery->whereNull('age_level');

            foreach ($allowedAgeLevels as $ageLevel) {
                $ageQuery->orWhere('age_level', $ageLevel);
            }
        });
    }
}
