<?php

namespace App\Services\GameIntelligence;

use App\Models\AgeGroup;
use App\Models\Word;
use App\Services\GameIntelligence\DTOs\DifficultyProfile;
use App\Services\GameIntelligence\DTOs\SolvabilityReport;
use App\Services\WordValidationService;
use Illuminate\Support\Collection;

class LettersSolvabilityService
{
    /**
     * @var array<string, \Illuminate\Support\Collection<int, Word>>
     */
    private array $wordPoolCache = [];

    public function __construct(
        private readonly WordValidationService $wordValidationService,
    ) {
    }

    /**
     * Validate that a draw has enough age-allowed solutions to be interesting.
     *
     * @param  array<int, string>  $letters
     */
    public function analyze(array $letters, AgeGroup $ageGroup, DifficultyProfile $difficultyProfile): SolvabilityReport
    {
        $matchingWords = $this->findCandidateWords($letters, $ageGroup);

        $bestWord = $matchingWords->first();
        $solutionsCount = $matchingWords->count();
        $bestLength = $bestWord?->length ?? 0;
        $targetWordCount = $matchingWords
            ->filter(fn (Word $word) => $word->length >= $difficultyProfile->minBestLength)
            ->count();

        $valid = $solutionsCount >= $difficultyProfile->minSolutions
            && $bestLength >= $difficultyProfile->minBestLength
            && $targetWordCount >= 1;

        return new SolvabilityReport(
            valid: $valid,
            solutionsCount: $solutionsCount,
            message: $valid ? null : 'Le tirage n’offre pas encore assez de solutions pertinentes.',
            bestWord: $bestWord?->normalized_word,
            metadata: [
                'best_length' => $bestLength,
                'target_word_count' => $targetWordCount,
                'sample_words' => $matchingWords->take(5)->pluck('normalized_word')->values()->all(),
            ],
        );
    }

    /**
     * Return candidate words ordered from strongest to weakest for a given draw.
     *
     * @param  array<int, string>  $letters
     * @return Collection<int, Word>
     */
    public function findCandidateWords(array $letters, AgeGroup $ageGroup): Collection
    {
        $availableCounts = array_count_values($letters);
        $wordPool = $this->wordPool($ageGroup, count($letters));

        /** @var Collection<int, Word> $matchingWords */
        $matchingWords = $wordPool
            ->filter(function (Word $word) use ($ageGroup, $availableCounts) {
                if (! $this->wordValidationService->isAllowedForAgeGroup($word, $ageGroup)) {
                    return false;
                }

                return $this->wordFitsCounts($word->normalized_word, $availableCounts);
            })
            ->sortByDesc(fn (Word $word) => ($word->length * 100000) + ($word->frequency ?? 0))
            ->values();

        return $matchingWords;
    }

    /**
     * @return \Illuminate\Support\Collection<int, Word>
     */
    private function wordPool(AgeGroup $ageGroup, int $lettersCount)
    {
        $cacheKey = $ageGroup->id.'-'.$lettersCount;

        if (! isset($this->wordPoolCache[$cacheKey])) {
            $this->wordPoolCache[$cacheKey] = Word::query()
                ->where('length', '<=', $lettersCount)
                ->get()
                ->filter(fn (Word $word) => $this->wordValidationService->isAllowedForAgeGroup($word, $ageGroup))
                ->sortByDesc(fn (Word $word) => (($word->length ?? 0) * 100000) + ($word->frequency ?? 0))
                ->values();
        }

        return $this->wordPoolCache[$cacheKey];
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
