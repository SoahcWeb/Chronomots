<?php

namespace App\Http\Controllers;

use App\Models\AgeGroup;
use App\Models\GameSession;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;

class NumberGameController extends Controller
{
    private const SESSION_KEY = 'chronomots.numbers.draws';

    /**
     * Display a new numbers game for the selected age group.
     */
    public function show(Request $request, AgeGroup $ageGroup): View
    {
        $draw = $this->createDrawPayload($ageGroup);

        $request->session()->put(self::SESSION_KEY.'.'.$draw['draw_id'], $draw);

        return $this->renderGameView($ageGroup, $draw);
    }

    /**
     * Submit a calculation for the current numbers game.
     */
    public function submit(Request $request, AgeGroup $ageGroup): View|Response|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'draw_id' => ['required', 'string'],
            'submitted_solution' => ['required', 'string', 'max:255'],
        ], [
            'submitted_solution.required' => 'Entre un calcul avant de valider.',
        ]);

        if ($validator->fails()) {
            $draw = $this->findExistingDraw($request, $request->string('draw_id')->toString()) ?? $this->createDrawPayload($ageGroup);

            return response()->view('play.numbers', [
                'ageGroup' => $ageGroup,
                'drawId' => $draw['draw_id'],
                'numbers' => $draw['numbers'],
                'targetNumber' => $draw['target_number'],
                'timerSeconds' => $ageGroup->numbers_timer_seconds,
                'submittedSolution' => $request->string('submitted_solution')->toString(),
                'errorMessage' => $validator->errors()->first('submitted_solution'),
            ], 422);
        }

        $draw = $this->findExistingDraw($request, $request->string('draw_id')->toString());

        if (! $draw) {
            return redirect()
                ->route('play.numbers.show', $ageGroup)
                ->withErrors([
                    'submitted_solution' => 'Cette partie n’est plus disponible. Relance un nouveau tirage.',
                ]);
        }

        $submittedSolution = trim($request->string('submitted_solution')->toString());
        $evaluation = $this->evaluateSubmittedSolution($submittedSolution, $draw['numbers']);

        if (! $evaluation['valid']) {
            return response()->view('play.numbers', [
                'ageGroup' => $ageGroup,
                'drawId' => $draw['draw_id'],
                'numbers' => $draw['numbers'],
                'targetNumber' => $draw['target_number'],
                'timerSeconds' => $ageGroup->numbers_timer_seconds,
                'submittedSolution' => $submittedSolution,
                'errorMessage' => $evaluation['message'],
            ], 422);
        }

        $resultValue = $evaluation['result'];
        $targetNumber = $draw['target_number'];
        $difference = abs($targetNumber - $resultValue);
        $score = $this->scoreForDifference($difference);
        $completedAt = now();

        [$gameSession, $numberRound] = DB::transaction(function () use ($request, $ageGroup, $draw, $submittedSolution, $resultValue, $score, $completedAt) {
            $gameSession = GameSession::query()->create([
                'user_id' => $request->user()->id,
                'age_group_id' => $ageGroup->id,
                'game_type' => 'numbers',
                'score' => 0,
                'status' => 'started',
                'started_at' => $draw['started_at'],
            ]);

            $numberRound = $gameSession->numberRounds()->create([
                'numbers' => $draw['numbers'],
                'target_number' => $draw['target_number'],
                'submitted_solution' => $submittedSolution,
                'result_value' => $resultValue,
                'score' => $score,
            ]);

            $gameSession->update([
                'score' => $score,
                'status' => 'completed',
                'completed_at' => $completedAt,
            ]);

            return [$gameSession->fresh(), $numberRound];
        });

        $request->session()->forget(self::SESSION_KEY.'.'.$draw['draw_id']);

        return view('play.numbers-result', [
            'ageGroup' => $ageGroup,
            'gameSession' => $gameSession,
            'numberRound' => $numberRound,
            'numbers' => $draw['numbers'],
            'targetNumber' => $targetNumber,
            'submittedSolution' => $submittedSolution,
            'resultValue' => $resultValue,
            'difference' => $difference,
            'score' => $score,
        ]);
    }

    /**
     * Render the numbers game page.
     */
    private function renderGameView(
        AgeGroup $ageGroup,
        array $draw,
        ?MessageBag $errors = null,
        string $submittedSolution = '',
    ): View {
        return view('play.numbers', [
            'ageGroup' => $ageGroup,
            'drawId' => $draw['draw_id'],
            'numbers' => $draw['numbers'],
            'targetNumber' => $draw['target_number'],
            'timerSeconds' => $ageGroup->numbers_timer_seconds,
            'submittedSolution' => $submittedSolution,
            'errorMessage' => $errors?->first('submitted_solution'),
        ]);
    }

    /**
     * Create the draw payload stored in session for a single play.
     */
    private function createDrawPayload(AgeGroup $ageGroup): array
    {
        [$numbers, $targetNumber] = match (true) {
            $ageGroup->min_age >= 14 => [$this->generateExpertNumbers(), random_int(100, 999)],
            $ageGroup->min_age >= 10 => [$this->generateRangeNumbers(5, 1, 25), random_int(50, 300)],
            default => [$this->generateRangeNumbers(4, 1, 10), random_int(10, 50)],
        };

        return [
            'draw_id' => Str::uuid()->toString(),
            'numbers' => $numbers,
            'target_number' => $targetNumber,
            'started_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Generate a set of small numbers.
     *
     * @return array<int, int>
     */
    private function generateRangeNumbers(int $count, int $min, int $max): array
    {
        $numbers = [];

        for ($index = 0; $index < $count; $index++) {
            $numbers[] = random_int($min, $max);
        }

        return $numbers;
    }

    /**
     * Generate an expert set with small and large numbers.
     *
     * @return array<int, int>
     */
    private function generateExpertNumbers(): array
    {
        $smallNumbers = $this->generateRangeNumbers(4, 1, 10);
        $largePool = [25, 50, 75, 100];
        shuffle($largePool);

        return array_merge($smallNumbers, array_slice($largePool, 0, 2));
    }

    /**
     * Retrieve an existing draw from session.
     */
    private function findExistingDraw(Request $request, string $drawId): ?array
    {
        $draw = $request->session()->get(self::SESSION_KEY.'.'.$drawId);

        return is_array($draw) ? $draw : null;
    }

    /**
     * Validate and evaluate a submitted arithmetic expression.
     *
     * @param  array<int, int>  $availableNumbers
     * @return array{valid: bool, result?: int, message?: string}
     */
    private function evaluateSubmittedSolution(string $expression, array $availableNumbers): array
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

        if (empty($usedNumbers)) {
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

    /**
     * Ensure the submitted numbers do not exceed the draw counts.
     *
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
     * Tokenize a simple arithmetic expression.
     *
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
     * Convert tokens to reverse polish notation.
     *
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
                while (! empty($operators)) {
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

                while (! empty($operators)) {
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

        while (! empty($operators)) {
            $operator = array_pop($operators);

            if ($operator === '(' || $operator === ')') {
                return null;
            }

            $output[] = $operator;
        }

        return $output;
    }

    /**
     * Evaluate reverse polish notation with integer-only arithmetic.
     *
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

    /**
     * Divide two integers only when division is exact.
     */
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

    /**
     * Compute the score from the target difference.
     */
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
