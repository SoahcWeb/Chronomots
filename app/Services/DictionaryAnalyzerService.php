<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use RuntimeException;

class DictionaryAnalyzerService
{
    private const CACHE_TTL_SECONDS = 3600;

    public function __construct(
        private readonly WordNormalizerService $wordNormalizerService,
    ) {
    }

    /**
     * Return every dictionary word that can be formed from the provided letters.
     *
     * @param  array<int, string>  $letters
     * @return array<int, string>
     */
    public function findPossibleWords(array $letters): array
    {
        $normalizedLetters = $this->normalizeLetters($letters);
        $lettersCount = count($normalizedLetters);

        if ($lettersCount === 0) {
            return [];
        }

        $availableCounts = array_count_values($normalizedLetters);
        $matches = [];

        foreach ($this->dictionaryIndex()['words_by_length'] as $length => $words) {
            if ($length > $lettersCount) {
                continue;
            }

            foreach ($words as $entry) {
                if ($this->wordFitsCounts($entry['counts'], $availableCounts)) {
                    $matches[] = $entry['word'];
                }
            }
        }

        usort($matches, static function (string $left, string $right): int {
            $lengthComparison = strlen($right) <=> strlen($left);

            return $lengthComparison !== 0 ? $lengthComparison : strcmp($left, $right);
        });

        return array_values(array_unique($matches));
    }

    /**
     * Return the longest playable dictionary word for the provided letters.
     *
     * @param  array<int, string>  $letters
     */
    public function getLongestWord(array $letters): ?string
    {
        $words = $this->findPossibleWords($letters);

        return $words[0] ?? null;
    }

    /**
     * Compute a gameplay-oriented playability score and difficulty signal.
     *
     * @param  array<int, string>  $letters
     * @return array{
     *     score: int,
     *     possible_words_count: int,
     *     longest_word: string|null,
     *     longest_word_length: int,
     *     too_difficult: bool
     * }
     */
    public function calculatePlayabilityScore(array $letters): array
    {
        $possibleWords = $this->findPossibleWords($letters);
        $possibleWordsCount = count($possibleWords);
        $longestWord = $possibleWords[0] ?? null;
        $longestWordLength = $longestWord !== null ? strlen($longestWord) : 0;

        $score = min(45, $possibleWordsCount * 5);
        $score += min(40, $longestWordLength * 6);
        $score += $possibleWordsCount > 0 ? min(15, max(0, $possibleWordsCount - 1)) : 0;
        $score = max(0, min(100, $score));

        return [
            'score' => $score,
            'possible_words_count' => $possibleWordsCount,
            'longest_word' => $longestWord,
            'longest_word_length' => $longestWordLength,
            'too_difficult' => $possibleWordsCount === 0 || $longestWordLength < 4,
        ];
    }

    /**
     * @return array{words_by_length: array<int, array<int, array{word: string, counts: array<string, int>}>>}
     */
    private function dictionaryIndex(): array
    {
        $path = $this->dictionaryPath();

        if (! File::exists($path)) {
            throw new RuntimeException(sprintf('Dictionary file not found at path: %s', $path));
        }

        $cacheKey = sprintf(
            'dictionary-analyzer:%s:%d',
            md5($path),
            File::lastModified($path),
        );

        /** @var array{words_by_length: array<int, array<int, array{word: string, counts: array<string, int>}>>} $index */
        $index = Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($path): array {
            $wordsByLength = [];
            $seenWords = [];

            foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                $normalizedWord = strtoupper($this->wordNormalizerService->normalize($line));

                if ($normalizedWord === '' || isset($seenWords[$normalizedWord])) {
                    continue;
                }

                $seenWords[$normalizedWord] = true;
                $length = strlen($normalizedWord);
                $wordsByLength[$length][] = [
                    'word' => $normalizedWord,
                    'counts' => array_count_values(str_split($normalizedWord)),
                ];
            }

            krsort($wordsByLength);

            return [
                'words_by_length' => $wordsByLength,
            ];
        });

        return $index;
    }

    /**
     * @param  array<int, string>  $letters
     * @return array<int, string>
     */
    private function normalizeLetters(array $letters): array
    {
        $normalized = [];

        foreach ($letters as $letter) {
            $normalizedLetter = strtoupper($this->wordNormalizerService->normalize($letter));

            if ($normalizedLetter === '') {
                continue;
            }

            $normalized[] = $normalizedLetter[0];
        }

        return $normalized;
    }

    /**
     * @param  array<string, int>  $wordCounts
     * @param  array<string, int>  $availableCounts
     */
    private function wordFitsCounts(array $wordCounts, array $availableCounts): bool
    {
        foreach ($wordCounts as $letter => $count) {
            if (($availableCounts[$letter] ?? 0) < $count) {
                return false;
            }
        }

        return true;
    }

    private function dictionaryPath(): string
    {
        return storage_path('app/dictionary/french_words.txt');
    }
}
