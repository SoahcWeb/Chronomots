<?php

namespace App\Services;

use App\Models\AgeGroup;
use App\Models\DrawStatistic;
use App\Models\DrawStatisticHistogram;
use App\Services\GameIntelligence\DTOs\SolvabilityReport;
use Illuminate\Support\Facades\DB;

class DrawStatisticsService
{
    /**
     * Record one letters draw attempt into aggregated statistics.
     *
     * @param  array<int, string>  $letters
     */
    public function recordLettersDrawAttempt(
        AgeGroup $ageGroup,
        array $letters,
        SolvabilityReport $solvabilityReport,
        int $qualityScore,
        bool $accepted,
    ): void {
        $payload = $this->buildLettersMetricsPayload($letters, $solvabilityReport, $qualityScore, $accepted);

        DB::transaction(function () use ($ageGroup, $payload): void {
            $this->updateStatisticAggregate('letters:global', 'global', null, $payload);
            $this->updateStatisticAggregate('letters:age:'.$ageGroup->id, 'age_group', $ageGroup->id, $payload);
        });
    }

    /**
     * @return \Illuminate\Support\Collection<int, DrawStatistic>
     */
    public function allLettersStatistics()
    {
        return DrawStatistic::query()
            ->with(['ageGroup', 'histograms'])
            ->where('game_type', 'letters')
            ->orderBy('scope')
            ->orderBy('age_group_id')
            ->get();
    }

    /**
     * @param  array<int, string>  $letters
     * @return array{
     *     accepted_increment: int,
     *     rejected_increment: int,
     *     total_letters_increment: int,
     *     total_possible_words_increment: int,
     *     total_possible_word_length_increment: int,
     *     total_difficulty_score_increment: int,
     *     letter_frequency_increment: array<string, int>,
     *     histograms: array<string, string>
     * }
     */
    private function buildLettersMetricsPayload(
        array $letters,
        SolvabilityReport $solvabilityReport,
        int $qualityScore,
        bool $accepted,
    ): array {
        $normalizedLetters = array_values(array_map('strtoupper', $letters));
        $difficultyScore = $this->difficultyScore($solvabilityReport, $qualityScore);

        return [
            'accepted_increment' => $accepted ? 1 : 0,
            'rejected_increment' => $accepted ? 0 : 1,
            'total_letters_increment' => count($normalizedLetters),
            'total_possible_words_increment' => $solvabilityReport->solutionsCount,
            'total_possible_word_length_increment' => (int) ($solvabilityReport->metadata['total_possible_word_length'] ?? 0),
            'total_difficulty_score_increment' => $difficultyScore,
            'letter_frequency_increment' => array_count_values($normalizedLetters),
            'histograms' => [
                'possible_words_count' => $this->bucketize($solvabilityReport->solutionsCount, 5),
                'best_length' => $this->bucketize((int) ($solvabilityReport->metadata['best_length'] ?? 0), 1),
                'difficulty_score' => $this->bucketize($difficultyScore, 10),
            ],
        ];
    }

    /**
     * @param  array{
     *     accepted_increment: int,
     *     rejected_increment: int,
     *     total_letters_increment: int,
     *     total_possible_words_increment: int,
     *     total_possible_word_length_increment: int,
     *     total_difficulty_score_increment: int,
     *     letter_frequency_increment: array<string, int>,
     *     histograms: array<string, string>
     * }  $payload
     */
    private function updateStatisticAggregate(
        string $scopeKey,
        string $scope,
        ?int $ageGroupId,
        array $payload,
    ): void {
        $statistic = DrawStatistic::query()->firstOrCreate(
            ['scope_key' => $scopeKey],
            [
                'scope' => $scope,
                'game_type' => 'letters',
                'age_group_id' => $ageGroupId,
                'letter_frequency' => [],
            ],
        );

        $acceptedDrawsCount = $statistic->accepted_draws_count + $payload['accepted_increment'];
        $rejectedDrawsCount = $statistic->rejected_draws_count + $payload['rejected_increment'];
        $totalAttempts = $acceptedDrawsCount + $rejectedDrawsCount;
        $totalPossibleWords = $statistic->total_possible_words + $payload['total_possible_words_increment'];
        $totalPossibleWordLength = $statistic->total_possible_word_length + $payload['total_possible_word_length_increment'];
        $totalDifficultyScore = $statistic->total_difficulty_score + $payload['total_difficulty_score_increment'];
        $letterFrequency = $this->mergeLetterFrequency(
            $statistic->letter_frequency ?? [],
            $payload['letter_frequency_increment'],
        );

        $statistic->fill([
            'scope' => $scope,
            'game_type' => 'letters',
            'age_group_id' => $ageGroupId,
            'accepted_draws_count' => $acceptedDrawsCount,
            'rejected_draws_count' => $rejectedDrawsCount,
            'total_letters_drawn' => $statistic->total_letters_drawn + $payload['total_letters_increment'],
            'total_possible_words' => $totalPossibleWords,
            'total_possible_word_length' => $totalPossibleWordLength,
            'total_difficulty_score' => $totalDifficultyScore,
            'average_possible_word_length' => $totalPossibleWords > 0
                ? round($totalPossibleWordLength / $totalPossibleWords, 2)
                : 0,
            'average_difficulty_score' => $totalAttempts > 0
                ? round($totalDifficultyScore / $totalAttempts, 2)
                : 0,
            'rejection_rate' => $totalAttempts > 0
                ? round($rejectedDrawsCount / $totalAttempts, 4)
                : 0,
            'letter_frequency' => $letterFrequency,
        ])->save();

        foreach ($payload['histograms'] as $metric => $bucket) {
            $histogram = DrawStatisticHistogram::query()->firstOrCreate(
                [
                    'draw_statistic_id' => $statistic->id,
                    'metric' => $metric,
                    'bucket' => $bucket,
                ],
                ['entries_count' => 0],
            );

            $histogram->increment('entries_count');
        }
    }

    /**
     * @param  array<string, int>  $existing
     * @param  array<string, int>  $increment
     * @return array<string, int>
     */
    private function mergeLetterFrequency(array $existing, array $increment): array
    {
        foreach ($increment as $letter => $count) {
            $existing[$letter] = ($existing[$letter] ?? 0) + $count;
        }

        ksort($existing);

        return $existing;
    }

    private function difficultyScore(SolvabilityReport $solvabilityReport, int $qualityScore): int
    {
        $bestLength = (int) ($solvabilityReport->metadata['best_length'] ?? 0);
        $targetWordCount = (int) ($solvabilityReport->metadata['target_word_count'] ?? 0);
        $difficulty = 100;
        $difficulty -= min(40, $solvabilityReport->solutionsCount * 8);
        $difficulty -= min(25, $bestLength * 4);
        $difficulty -= min(20, $targetWordCount * 5);
        $difficulty += $solvabilityReport->valid ? 0 : 10;
        $difficulty += max(0, 80 - $qualityScore) / 4;

        return max(0, min(100, (int) round($difficulty)));
    }

    private function bucketize(int $value, int $bucketSize): string
    {
        $start = (int) floor($value / max(1, $bucketSize)) * max(1, $bucketSize);
        $end = $start + max(1, $bucketSize) - 1;

        return sprintf('%d-%d', $start, $end);
    }
}
