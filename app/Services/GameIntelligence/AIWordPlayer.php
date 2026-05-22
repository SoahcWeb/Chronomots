<?php

namespace App\Services\GameIntelligence;

use App\Models\Word;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

class AIWordPlayer
{
    private const CACHE_TTL_SECONDS = 300;

    /**
     * @var array<string, array{rank_ratio: float, confidence_floor: int, confidence_ceiling: int, thinking_time_ms: int}>
     */
    private const DIFFICULTY_PROFILES = [
        'beginner' => [
            'rank_ratio' => 0.60,
            'confidence_floor' => 35,
            'confidence_ceiling' => 60,
            'thinking_time_ms' => 2200,
        ],
        'intermediate' => [
            'rank_ratio' => 0.25,
            'confidence_floor' => 60,
            'confidence_ceiling' => 82,
            'thinking_time_ms' => 1500,
        ],
        'expert' => [
            'rank_ratio' => 0.00,
            'confidence_floor' => 88,
            'confidence_ceiling' => 99,
            'thinking_time_ms' => 900,
        ],
    ];

    /**
     * Analyze a draw and choose an AI word according to the requested difficulty.
     *
     * @param  array<int, string>  $letters
     * @return array{chosen_word: string, score: int, thinking_time: int, confidence: int}
     */
    public function play(array $letters, string $difficulty): array
    {
        $profile = self::DIFFICULTY_PROFILES[$difficulty] ?? null;

        if ($profile === null) {
            throw new InvalidArgumentException(sprintf('Unsupported AI difficulty: %s', $difficulty));
        }

        $candidates = $this->candidateWords($letters);

        if ($candidates->isEmpty()) {
            return [
                'chosen_word' => '',
                'score' => 0,
                'thinking_time' => $profile['thinking_time_ms'],
                'confidence' => $profile['confidence_floor'],
            ];
        }

        $selected = $this->pickWord($candidates, $profile['rank_ratio']);
        $score = strlen((string) $selected->normalized_word) * 10;
        $confidence = $this->confidenceForSelection($candidates, $selected->normalized_word, $profile);

        return [
            'chosen_word' => strtoupper((string) $selected->normalized_word),
            'score' => $score,
            'thinking_time' => $this->thinkingTimeForSelection($selected->length, $profile['thinking_time_ms']),
            'confidence' => $confidence,
        ];
    }

    /**
     * @param  array<int, string>  $letters
     * @return Collection<int, Word>
     */
    private function candidateWords(array $letters): Collection
    {
        $normalizedLetters = array_values(array_map(
            static fn (string $letter): string => strtoupper(substr(trim($letter), 0, 1)),
            array_filter($letters, static fn (mixed $letter): bool => is_string($letter) && trim($letter) !== ''),
        ));
        $cacheKey = 'ai-word-player:'.implode('', $normalizedLetters);

        /** @var array<int, array{normalized_word: string, length: int, frequency: float|null, is_active: bool}> $cachedCandidates */
        $cachedCandidates = Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($normalizedLetters): array {
            $availableCounts = array_count_values($normalizedLetters);
            $lettersCount = count($normalizedLetters);

            return Word::query()
                ->select(['normalized_word', 'length', 'frequency', 'is_active'])
                ->where('is_active', true)
                ->where('length', '<=', $lettersCount)
                ->orderByDesc('length')
                ->orderByDesc('frequency')
                ->orderBy('normalized_word')
                ->get()
                ->filter(function (Word $word) use ($availableCounts): bool {
                    $wordCounts = array_count_values(str_split(strtoupper((string) $word->normalized_word)));

                    foreach ($wordCounts as $letter => $count) {
                        if (($availableCounts[$letter] ?? 0) < $count) {
                            return false;
                        }
                    }

                    return true;
                })
                ->map(fn (Word $word): array => [
                    'normalized_word' => (string) $word->normalized_word,
                    'length' => (int) $word->length,
                    'frequency' => $word->frequency !== null ? (float) $word->frequency : null,
                    'is_active' => (bool) $word->is_active,
                ])
                ->all();
        });

        /** @var Collection<int, Word> $candidates */
        $candidates = collect($cachedCandidates)
            ->map(function (array $attributes): Word {
                $word = new Word();
                $word->forceFill($attributes);

                return $word;
            })
                ->values();

        return $candidates;
    }

    /**
     * @param  Collection<int, Word>  $candidates
     */
    private function pickWord(Collection $candidates, float $rankRatio): Word
    {
        $count = $candidates->count();
        $index = min(
            $count - 1,
            max(0, (int) floor(($count - 1) * $rankRatio)),
        );

        /** @var Word $selected */
        $selected = $candidates->values()->get($index);

        return $selected;
    }

    /**
     * @param  Collection<int, Word>  $candidates
     * @param  array{confidence_floor: int, confidence_ceiling: int}  $profile
     */
    private function confidenceForSelection(Collection $candidates, string $selectedWord, array $profile): int
    {
        $count = max(1, $candidates->count());
        $index = (int) $candidates->search(
            fn (Word $word): bool => (string) $word->normalized_word === $selectedWord,
        );
        $rankPenalty = $count > 1 ? (int) round(($index / ($count - 1)) * 20) : 0;

        return max(
            $profile['confidence_floor'],
            min($profile['confidence_ceiling'], $profile['confidence_ceiling'] - $rankPenalty),
        );
    }

    private function thinkingTimeForSelection(int $wordLength, int $baseThinkingTimeMs): int
    {
        return $baseThinkingTimeMs + max(0, $wordLength - 3) * 70;
    }
}
