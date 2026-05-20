<?php

namespace App\Services\GameIntelligence;

use App\Services\GameIntelligence\DTOs\DifficultyProfile;
use App\Services\GameIntelligence\DTOs\SolvabilityReport;

class NumbersSolvabilityService
{
    /**
     * Check that a candidate numbers draw can produce at least one target in range.
     *
     * @param  array<int, int>  $numbers
     */
    public function analyze(array $numbers, DifficultyProfile $difficultyProfile): SolvabilityReport
    {
        $reachableValues = $this->reachableValues($numbers);
        $targetsInRange = $this->targetsInRange($reachableValues, $difficultyProfile);
        $selectedTarget = $this->pickTarget($targetsInRange, $difficultyProfile);

        return new SolvabilityReport(
            valid: $selectedTarget !== null,
            solutionsCount: count($targetsInRange),
            message: $selectedTarget !== null ? null : 'Le tirage n’a pas trouvé de cible idéale dans la plage attendue.',
            bestValue: $selectedTarget['value'] ?? $this->closestToRange(array_keys($reachableValues), $difficultyProfile->targetMin, $difficultyProfile->targetMax),
            metadata: [
                'targets_in_range' => array_slice(array_map(fn (array $target) => $target['value'], $targetsInRange), 0, 15),
                'reachable_values_count' => count($reachableValues),
                'selected_target' => $selectedTarget,
            ],
        );
    }

    /**
     * Return reachable options sorted from strongest to weakest against a target.
     *
     * @param  array<int, int>  $numbers
     * @return array<int, array{value: int, expression: string, difference: int, score: int, ways: int, min_operations: int, max_numbers_used: int, is_input: bool}>
     */
    public function findTargetOptions(array $numbers, int $targetNumber): array
    {
        $reachableValues = $this->reachableValues($numbers);
        $options = [];

        foreach ($reachableValues as $value => $stats) {
            $difference = abs($targetNumber - $value);

            $options[] = [
                'value' => $value,
                'expression' => $stats['expression'],
                'difference' => $difference,
                'score' => $this->scoreForDifference($difference),
                'ways' => $stats['ways'],
                'min_operations' => $stats['min_operations'],
                'max_numbers_used' => $stats['max_numbers_used'],
                'is_input' => $stats['is_input'],
            ];
        }

        usort($options, function (array $left, array $right) {
            return $right['score'] <=> $left['score']
                ?: $left['difference'] <=> $right['difference']
                ?: $right['max_numbers_used'] <=> $left['max_numbers_used']
                ?: $right['ways'] <=> $left['ways']
                ?: $left['min_operations'] <=> $right['min_operations']
                ?: $left['value'] <=> $right['value'];
        });

        return $options;
    }

    /**
     * @param  array<int, int>  $numbers
     * @return array<int, array{ways: int, min_operations: int, max_numbers_used: int, is_input: bool, expression: string}>
     */
    private function reachableValues(array $numbers): array
    {
        $values = [];
        $seenStates = [];
        $nodes = array_map(fn (int $number) => [
            'value' => $number,
            'operations' => 0,
            'numbers_used' => 1,
            'expression' => (string) $number,
        ], $numbers);

        $this->explore($nodes, $values, $seenStates);

        return $values;
    }

    /**
     * Explore exact integer results reachable with the V1 arithmetic rules.
     *
     * @param  array<int, array{value: int, operations: int, numbers_used: int, expression: string}>  $nodes
     * @param  array<int, array{ways: int, min_operations: int, max_numbers_used: int, is_input: bool, expression: string}>  $values
     * @param  array<string, true>  $seenStates
     */
    private function explore(array $nodes, array &$values, array &$seenStates): void
    {
        usort($nodes, fn (array $left, array $right) => ($left['value'] <=> $right['value'])
            ?: ($left['numbers_used'] <=> $right['numbers_used'])
            ?: ($left['operations'] <=> $right['operations']));
        $stateKey = implode('|', array_map(
            fn (array $node) => $node['value'].':'.$node['numbers_used'].':'.$node['operations'],
            $nodes,
        ));

        if (isset($seenStates[$stateKey])) {
            return;
        }

        $seenStates[$stateKey] = true;

        foreach ($nodes as $node) {
            if ($node['value'] >= 0) {
                $this->registerValue($values, $node['value'], $node['operations'], $node['numbers_used'], $node['expression']);
            }
        }

        $count = count($nodes);

        if ($count < 2) {
            return;
        }

        for ($leftIndex = 0; $leftIndex < $count; $leftIndex++) {
            for ($rightIndex = $leftIndex + 1; $rightIndex < $count; $rightIndex++) {
                $left = $nodes[$leftIndex];
                $right = $nodes[$rightIndex];
                $remaining = $nodes;
                unset($remaining[$rightIndex], $remaining[$leftIndex]);
                $remaining = array_values($remaining);

                foreach ($this->combine($left, $right) as $result) {
                    if ($result['value'] < 0 || $result['value'] > 5000) {
                        continue;
                    }

                    $next = $remaining;
                    $next[] = [
                        'value' => $result['value'],
                        'operations' => $left['operations'] + $right['operations'] + 1,
                        'numbers_used' => $left['numbers_used'] + $right['numbers_used'],
                        'expression' => $result['expression'],
                    ];
                    $this->explore($next, $values, $seenStates);
                }
            }
        }
    }

    /**
     * @param  array{value: int, expression: string}  $left
     * @param  array{value: int, expression: string}  $right
     * @return array<int, array{value: int, expression: string}>
     */
    private function combine(array $left, array $right): array
    {
        $leftValue = $left['value'];
        $rightValue = $right['value'];
        $results = [
            [
                'value' => $leftValue + $rightValue,
                'expression' => '('.$left['expression'].' + '.$right['expression'].')',
            ],
            [
                'value' => $leftValue * $rightValue,
                'expression' => '('.$left['expression'].' * '.$right['expression'].')',
            ],
        ];

        if ($leftValue >= $rightValue) {
            $results[] = [
                'value' => $leftValue - $rightValue,
                'expression' => '('.$left['expression'].' - '.$right['expression'].')',
            ];
        }

        if ($rightValue >= $leftValue) {
            $results[] = [
                'value' => $rightValue - $leftValue,
                'expression' => '('.$right['expression'].' - '.$left['expression'].')',
            ];
        }

        if ($rightValue !== 0 && $leftValue % $rightValue === 0) {
            $results[] = [
                'value' => intdiv($leftValue, $rightValue),
                'expression' => '('.$left['expression'].' / '.$right['expression'].')',
            ];
        }

        if ($leftValue !== 0 && $rightValue % $leftValue === 0) {
            $results[] = [
                'value' => intdiv($rightValue, $leftValue),
                'expression' => '('.$right['expression'].' / '.$left['expression'].')',
            ];
        }

        $unique = [];

        foreach ($results as $result) {
            if ($result['value'] < 0) {
                continue;
            }

            $unique[$result['value'].'|'.$result['expression']] = $result;
        }

        return array_values($unique);
    }

    /**
     * @param  array<int, array{ways: int, min_operations: int, max_numbers_used: int, is_input: bool, expression: string}>  $values
     */
    private function registerValue(array &$values, int $value, int $operations, int $numbersUsed, string $expression): void
    {
        $current = $values[$value] ?? [
            'ways' => 0,
            'min_operations' => PHP_INT_MAX,
            'max_numbers_used' => 0,
            'is_input' => false,
            'expression' => $expression,
        ];

        $current['ways'] = min(99, $current['ways'] + 1);
        if ($operations < $current['min_operations']
            || ($operations === $current['min_operations'] && $numbersUsed > $current['max_numbers_used'])) {
            $current['expression'] = $expression;
        }

        $current['min_operations'] = min($current['min_operations'], $operations);
        $current['max_numbers_used'] = max($current['max_numbers_used'], $numbersUsed);
        $current['is_input'] = $current['is_input'] || ($operations === 0 && $numbersUsed === 1);

        $values[$value] = $current;
    }

    /**
     * @param  array<int, array{ways: int, min_operations: int, max_numbers_used: int, is_input: bool}>  $reachableValues
     * @return array<int, array{value: int, ways: int, min_operations: int, max_numbers_used: int, is_input: bool, quality: int}>
     */
    private function targetsInRange(array $reachableValues, DifficultyProfile $difficultyProfile): array
    {
        $targets = [];

        foreach ($reachableValues as $value => $stats) {
            if ($value < $difficultyProfile->targetMin || $value > $difficultyProfile->targetMax) {
                continue;
            }

            $targets[] = [
                'value' => $value,
                'ways' => $stats['ways'],
                'min_operations' => $stats['min_operations'],
                'max_numbers_used' => $stats['max_numbers_used'],
                'is_input' => $stats['is_input'],
                'quality' => $this->targetQuality($value, $stats, $difficultyProfile),
            ];
        }

        usort($targets, fn (array $left, array $right) => $right['quality'] <=> $left['quality'] ?: $left['value'] <=> $right['value']);

        return $targets;
    }

    /**
     * @param  array{ways: int, min_operations: int, max_numbers_used: int, is_input: bool}  $stats
     */
    private function targetQuality(int $value, array $stats, DifficultyProfile $difficultyProfile): int
    {
        $center = (int) floor(($difficultyProfile->targetMin + $difficultyProfile->targetMax) / 2);
        $rangeWidth = max(1, $difficultyProfile->targetMax - $difficultyProfile->targetMin);
        $centerDistance = abs($value - $center);
        $centerScore = max(0, 30 - (int) round(($centerDistance / $rangeWidth) * 30));

        $preferredMinOperations = (int) ($difficultyProfile->metadata['preferred_min_operations'] ?? 1);
        $preferredMaxOperations = (int) ($difficultyProfile->metadata['preferred_max_operations'] ?? 99);
        $preferredMinNumbersUsed = (int) ($difficultyProfile->metadata['preferred_min_numbers_used'] ?? 1);
        $avoidInputTargets = (bool) ($difficultyProfile->metadata['avoid_input_targets'] ?? false);

        $operationsScore = match (true) {
            $stats['min_operations'] < $preferredMinOperations => 10,
            $stats['min_operations'] > $preferredMaxOperations => 15,
            default => 30,
        };

        $numbersUsedScore = $stats['max_numbers_used'] >= $preferredMinNumbersUsed ? 25 : 8;
        $waysScore = min(15, $stats['ways'] * 3);
        $inputPenalty = $avoidInputTargets && $stats['is_input'] ? 25 : 0;

        return max(0, $centerScore + $operationsScore + $numbersUsedScore + $waysScore - $inputPenalty);
    }

    /**
     * @param  array<int, array{value: int, ways: int, min_operations: int, max_numbers_used: int, is_input: bool, quality: int}>  $targetsInRange
     * @return array{value: int, ways: int, min_operations: int, max_numbers_used: int, is_input: bool, quality: int}|null
     */
    private function pickTarget(array $targetsInRange, DifficultyProfile $difficultyProfile): ?array
    {
        if ($targetsInRange === []) {
            return null;
        }

        $preferredMinOperations = (int) ($difficultyProfile->metadata['preferred_min_operations'] ?? 1);
        $preferredMinNumbersUsed = (int) ($difficultyProfile->metadata['preferred_min_numbers_used'] ?? 1);

        foreach ($targetsInRange as $target) {
            if ($target['min_operations'] >= $preferredMinOperations && $target['max_numbers_used'] >= $preferredMinNumbersUsed) {
                return $target;
            }
        }

        return $targetsInRange[0];
    }

    /**
     * @param  array<int, int>  $values
     */
    private function closestToRange(array $values, int $min, int $max): ?int
    {
        if ($values === []) {
            return null;
        }

        usort($values, function (int $left, int $right) use ($min, $max) {
            $leftDistance = $this->rangeDistance($left, $min, $max);
            $rightDistance = $this->rangeDistance($right, $min, $max);

            return $leftDistance <=> $rightDistance ?: $left <=> $right;
        });

        return $values[0];
    }

    private function rangeDistance(int $value, int $min, int $max): int
    {
        if ($value < $min) {
            return $min - $value;
        }

        if ($value > $max) {
            return $value - $max;
        }

        return 0;
    }

    private function scoreForDifference(int $difference): int
    {
        return match (true) {
            $difference === 0 => 100,
            $difference <= 5 => 50,
            $difference <= 10 => 25,
            default => 0,
        };
    }
}
