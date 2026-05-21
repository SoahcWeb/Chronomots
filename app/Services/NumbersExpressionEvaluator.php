<?php

namespace App\Services;

class NumbersExpressionEvaluator
{
    /**
     * @param  array<int, int>  $availableNumbers
     * @return array{valid: bool, result?: int, message?: string}
     */
    public function evaluateSubmittedSolution(string $expression, array $availableNumbers): array
    {
        $normalized = preg_replace('/\s+/', '', $expression) ?? '';

        if ($normalized === '') {
            return ['valid' => false, 'message' => 'Entre un calcul avant de valider.'];
        }

        if (! preg_match('~^[0-9+\-*/()]+$~', $normalized)) {
            return ['valid' => false, 'message' => 'Le calcul contient des caractères non autorisés.'];
        }

        preg_match_all('/\d+/', $normalized, $matches);
        $usedNumbers = array_map('intval', $matches[0]);

        if ($usedNumbers === []) {
            return ['valid' => false, 'message' => 'Le calcul doit utiliser au moins un nombre proposé.'];
        }

        if (! $this->numbersAreAvailable($usedNumbers, $availableNumbers)) {
            return ['valid' => false, 'message' => 'Le calcul utilise des nombres qui ne sont pas disponibles dans le tirage.'];
        }

        $tokens = $this->tokenizeExpression($normalized);

        if ($tokens === null) {
            return ['valid' => false, 'message' => 'Le calcul saisi n’a pas un format valide.'];
        }

        $rpn = $this->toReversePolishNotation($tokens);

        if ($rpn === null) {
            return ['valid' => false, 'message' => 'Le calcul saisi n’a pas un format valide.'];
        }

        $result = $this->evaluateReversePolishNotation($rpn);

        if ($result === null) {
            return ['valid' => false, 'message' => 'Le calcul ne peut pas être évalué avec les règles de cette V1.'];
        }

        if ($result < 0) {
            return ['valid' => false, 'message' => 'Le résultat final doit rester positif ou nul.'];
        }

        return ['valid' => true, 'result' => $result];
    }

    public function scoreForDifference(int $difference): int
    {
        return match (true) {
            $difference === 0 => 100,
            $difference <= 5 => 50,
            $difference <= 10 => 25,
            default => 0,
        };
    }

    /**
     * @param  array<int, int>  $usedNumbers
     * @param  array<int, int>  $availableNumbers
     */
    private function numbersAreAvailable(array $usedNumbers, array $availableNumbers): bool
    {
        $availableCounts = array_count_values($availableNumbers);
        $usedCounts = array_count_values($usedNumbers);

        foreach ($usedCounts as $number => $count) {
            if (($availableCounts[$number] ?? 0) < $count) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<int, int|string>|null
     */
    private function tokenizeExpression(string $expression): ?array
    {
        $tokens = [];
        $length = strlen($expression);
        $buffer = '';

        for ($index = 0; $index < $length; $index++) {
            $character = $expression[$index];

            if (ctype_digit($character)) {
                $buffer .= $character;
                continue;
            }

            if ($buffer !== '') {
                $tokens[] = (int) $buffer;
                $buffer = '';
            }

            if (in_array($character, ['+', '-', '*', '/', '(', ')'], true)) {
                $tokens[] = $character;
                continue;
            }

            return null;
        }

        if ($buffer !== '') {
            $tokens[] = (int) $buffer;
        }

        return $tokens;
    }

    /**
     * @param  array<int, int|string>  $tokens
     * @return array<int, int|string>|null
     */
    private function toReversePolishNotation(array $tokens): ?array
    {
        $output = [];
        $operators = [];
        $precedence = ['+' => 1, '-' => 1, '*' => 2, '/' => 2];

        foreach ($tokens as $token) {
            if (is_int($token)) {
                $output[] = $token;
                continue;
            }

            if (isset($precedence[$token])) {
                while ($operators !== []) {
                    $last = end($operators);

                    if ($last === '(') {
                        break;
                    }

                    if (($precedence[$last] ?? 0) >= $precedence[$token]) {
                        $output[] = array_pop($operators);
                        continue;
                    }

                    break;
                }

                $operators[] = $token;
                continue;
            }

            if ($token === '(') {
                $operators[] = $token;
                continue;
            }

            if ($token === ')') {
                $foundOpening = false;

                while ($operators !== []) {
                    $operator = array_pop($operators);

                    if ($operator === '(') {
                        $foundOpening = true;
                        break;
                    }

                    $output[] = $operator;
                }

                if (! $foundOpening) {
                    return null;
                }
            }
        }

        while ($operators !== []) {
            $operator = array_pop($operators);

            if ($operator === '(' || $operator === ')') {
                return null;
            }

            $output[] = $operator;
        }

        return $output;
    }

    /**
     * @param  array<int, int|string>  $tokens
     */
    private function evaluateReversePolishNotation(array $tokens): ?int
    {
        $stack = [];

        foreach ($tokens as $token) {
            if (is_int($token)) {
                $stack[] = $token;
                continue;
            }

            if (count($stack) < 2) {
                return null;
            }

            $right = array_pop($stack);
            $left = array_pop($stack);

            $value = match ($token) {
                '+' => $left + $right,
                '-' => $left - $right,
                '*' => $left * $right,
                '/' => $this->divideIfExact($left, $right),
                default => null,
            };

            if ($value === null) {
                return null;
            }

            $stack[] = $value;
        }

        return count($stack) === 1 ? $stack[0] : null;
    }

    private function divideIfExact(int $left, int $right): ?int
    {
        if ($right === 0) {
            return null;
        }

        if ($left % $right !== 0) {
            return null;
        }

        return intdiv($left, $right);
    }
}
