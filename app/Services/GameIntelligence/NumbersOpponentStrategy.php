<?php

namespace App\Services\GameIntelligence;

class NumbersOpponentStrategy
{
    public function __construct(
        private readonly NumbersSolvabilityService $numbersSolvabilityService,
    ) {
    }

    /**
     * @param  array<int, int>  $numbers
     * @return array{submitted_solution: string, result_value: int, difference: int, score: int, quality_label: string}
     */
    public function play(array $numbers, int $targetNumber, string $level): array
    {
        $options = $this->numbersSolvabilityService->findTargetOptions($numbers, $targetNumber);

        if ($options === []) {
            return [
                'submitted_solution' => '',
                'result_value' => 0,
                'difference' => $targetNumber,
                'score' => 0,
                'quality_label' => 'Aucune solution',
            ];
        }

        $selected = $this->pickOption($options, $level);

        return [
            'submitted_solution' => $selected['expression'],
            'result_value' => $selected['value'],
            'difference' => $selected['difference'],
            'score' => $selected['score'],
            'quality_label' => $this->qualityLabel($level),
        ];
    }

    /**
     * @param  array<int, array{value: int, expression: string, difference: int, score: int, ways: int, min_operations: int, max_numbers_used: int, is_input: bool}>  $options
     * @return array{value: int, expression: string, difference: int, score: int, ways: int, min_operations: int, max_numbers_used: int, is_input: bool}
     */
    private function pickOption(array $options, string $level): array
    {
        if ($level === 'expert') {
            return $options[0];
        }

        if ($level === 'difficile') {
            $strongOptions = array_values(array_filter(
                $options,
                fn (array $option) => $option['score'] >= 50,
            ));

            return $strongOptions[min(count($strongOptions) - 1, 1)] ?? $options[0];
        }

        if ($level === 'moyen') {
            $goodOptions = array_values(array_filter(
                $options,
                fn (array $option) => $option['score'] >= 25,
            ));

            if ($goodOptions !== []) {
                return $goodOptions[min(count($goodOptions) - 1, max(0, (int) floor((count($goodOptions) - 1) * 0.4)))];
            }

            return $options[min(count($options) - 1, 1)];
        }

        $imperfectOptions = array_values(array_filter(
            $options,
            fn (array $option) => $option['difference'] > 0 && $option['score'] > 0,
        ));

        if ($imperfectOptions !== []) {
            return $imperfectOptions[min(count($imperfectOptions) - 1, max(0, (int) floor((count($imperfectOptions) - 1) * 0.55)))];
        }

        return $options[min(count($options) - 1, max(0, (int) floor((count($options) - 1) * 0.6)))];
    }

    private function qualityLabel(string $level): string
    {
        return match ($level) {
            'expert' => 'Quasi optimal',
            'difficile' => 'Très précis',
            'moyen' => 'Bonne réponse',
            default => 'Réponse imparfaite',
        };
    }
}
