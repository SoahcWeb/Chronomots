<?php

namespace App\Services;

use App\Models\DailyChallenge;
use App\Models\DailyChallengeAttempt;
use App\Models\GameSession;
use App\Models\LetterRound;
use App\Models\NumberRound;
use App\Models\User;
use App\Services\AchievementService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DailyChallengeService
{
    public function __construct(
        private readonly AchievementService $achievementService,
        private readonly DailyChallengeGenerator $dailyChallengeGenerator,
        private readonly WordValidationService $wordValidationService,
    ) {
    }

    /**
     * @return Collection<int, DailyChallenge>
     */
    public function todayChallenges(): Collection
    {
        $today = now()->startOfDay();

        return collect(['letters', 'numbers'])
            ->map(fn (string $gameType) => $this->challengeForDateAndType($today, $gameType));
    }

    public function challengeForToday(string $gameType): DailyChallenge
    {
        return $this->challengeForDateAndType(now()->startOfDay(), $gameType);
    }

    public function challengeForDateAndType($date, string $gameType): DailyChallenge
    {
        $challenge = DailyChallenge::query()
            ->with('ageGroup')
            ->whereDate('challenge_date', $date->toDateString())
            ->where('game_type', $gameType)
            ->first();

        if ($challenge) {
            return $challenge;
        }

        $generated = $gameType === 'letters'
            ? $this->dailyChallengeGenerator->generateLetters($date)
            : $this->dailyChallengeGenerator->generateNumbers($date);

        try {
            return DailyChallenge::query()->create([
                'public_id' => (string) Str::ulid(),
                ...$generated,
            ])->load('ageGroup');
        } catch (\Throwable) {
            return DailyChallenge::query()
                ->with('ageGroup')
                ->whereDate('challenge_date', $date->toDateString())
                ->where('game_type', $gameType)
                ->firstOrFail();
        }
    }

    public function userAttempt(DailyChallenge $challenge, User $user): ?DailyChallengeAttempt
    {
        return $challenge->attempts()
            ->with(['user.playerProfile', 'gameSession.ageGroup'])
            ->where('user_id', $user->id)
            ->first();
    }

    /**
     * @return Collection<int, DailyChallengeAttempt>
     */
    public function leaderboard(DailyChallenge $challenge, int $limit = 10): Collection
    {
        return $challenge->attempts()
            ->with('user.playerProfile')
            ->orderByDesc('score')
            ->orderBy('attempted_at')
            ->limit($limit)
            ->get();
    }

    public function bestScoreOfDay(DailyChallenge $challenge): int
    {
        return (int) ($challenge->attempts()->max('score') ?? 0);
    }

    public function attemptsCount(DailyChallenge $challenge): int
    {
        return (int) $challenge->attempts()->count();
    }

    /**
     * @return Collection<int, array{challenge: DailyChallenge, user_attempt: ?DailyChallengeAttempt, best_score: int, attempts_count: int}>
     */
    public function historyForUser(User $user, int $days = 7): Collection
    {
        return DailyChallenge::query()
            ->with(['ageGroup', 'attempts' => fn ($query) => $query->where('user_id', $user->id)->with('gameSession')])
            ->withMax('attempts as best_score', 'score')
            ->withCount('attempts')
            ->whereDate('challenge_date', '<', now()->toDateString())
            ->orderByDesc('challenge_date')
            ->orderBy('game_type')
            ->limit($days * 2)
            ->get()
            ->map(function (DailyChallenge $challenge): array {
                return [
                    'challenge' => $challenge,
                    'user_attempt' => $challenge->attempts->first(),
                    'best_score' => (int) ($challenge->best_score ?? 0),
                    'attempts_count' => (int) ($challenge->attempts_count ?? 0),
                ];
            });
    }

    /**
     * @return array{attempt: DailyChallengeAttempt, game_session: GameSession, round: LetterRound|NumberRound, unlocked_achievements: Collection<int, \App\Models\Achievement>}
     */
    public function submit(User $user, DailyChallenge $challenge, array $input): array
    {
        if (! $challenge->challenge_date->isToday()) {
            throw ValidationException::withMessages([
                'challenge' => 'Ce défi quotidien n’est plus ouvert.',
            ]);
        }

        return match ($challenge->game_type) {
            'letters' => $this->submitLetters($user, $challenge, $input),
            'numbers' => $this->submitNumbers($user, $challenge, $input),
            default => throw ValidationException::withMessages([
                'challenge' => 'Type de défi non reconnu.',
            ]),
        };
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{attempt: DailyChallengeAttempt, game_session: GameSession, round: LetterRound, unlocked_achievements: Collection<int, \App\Models\Achievement>}
     */
    private function submitLetters(User $user, DailyChallenge $challenge, array $input): array
    {
        $letters = $challenge->payload['letters'] ?? [];
        $submittedWord = $this->wordValidationService->normalize((string) ($input['submitted_word'] ?? ''));

        if ($submittedWord === '') {
            throw ValidationException::withMessages([
                'submitted_word' => 'Le mot doit contenir uniquement des lettres.',
            ]);
        }

        if (! $this->wordUsesAvailableLetters($submittedWord, $letters)) {
            throw ValidationException::withMessages([
                'submitted_word' => 'Le mot proposé utilise des lettres qui ne sont pas disponibles dans le tirage.',
            ]);
        }

        $validation = $this->wordValidationService->validateForAgeGroup($submittedWord, $challenge->ageGroup);

        if (! $validation['valid']) {
            throw ValidationException::withMessages([
                'submitted_word' => $validation['message'],
            ]);
        }

        $score = strlen($submittedWord) * 10;
        $perfectScore = (int) ($challenge->solution_payload['perfect_score'] ?? 0);
        $isPerfect = $perfectScore > 0 && $score >= $perfectScore;

        return DB::transaction(function () use ($user, $challenge, $submittedWord, $score, $isPerfect) {
            $lockedChallenge = DailyChallenge::query()->lockForUpdate()->findOrFail($challenge->id);
            $existingAttempt = DailyChallengeAttempt::query()
                ->where('daily_challenge_id', $lockedChallenge->id)
                ->where('user_id', $user->id)
                ->first();

            if ($existingAttempt) {
                throw ValidationException::withMessages([
                    'submitted_word' => 'Tu as déjà tenté ce défi lettres aujourd’hui.',
                ]);
            }

            $completedAt = now();
            $gameSession = GameSession::query()->create([
                'user_id' => $user->id,
                'age_group_id' => $lockedChallenge->age_group_id,
                'daily_challenge_id' => $lockedChallenge->id,
                'game_type' => 'letters',
                'score' => 0,
                'status' => 'started',
                'started_at' => $completedAt,
            ]);

            $letterRound = $gameSession->letterRounds()->create([
                'letters' => implode('', $lockedChallenge->payload['letters']),
                'submitted_word' => $submittedWord,
                'best_word' => null,
                'score' => $score,
            ]);

            $gameSession->update([
                'score' => $score,
                'status' => 'completed',
                'completed_at' => $completedAt,
            ]);

            $attempt = DailyChallengeAttempt::query()->create([
                'daily_challenge_id' => $lockedChallenge->id,
                'user_id' => $user->id,
                'game_session_id' => $gameSession->id,
                'score' => $score,
                'submitted_word' => $submittedWord,
                'result_payload' => [
                    'perfect_score' => $lockedChallenge->solution_payload['perfect_score'] ?? null,
                    'best_length' => $lockedChallenge->solution_payload['best_length'] ?? null,
                ],
                'is_perfect' => $isPerfect,
                'attempted_at' => $completedAt,
            ]);

            $unlockedAchievements = $this->achievementService->unlockForCompletedGame(
                $user,
                $gameSession,
                ['submitted_word' => $submittedWord],
            );

            return [
                'attempt' => $attempt->fresh(['user.playerProfile', 'gameSession']),
                'game_session' => $gameSession->fresh(),
                'round' => $letterRound,
                'unlocked_achievements' => $unlockedAchievements,
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{attempt: DailyChallengeAttempt, game_session: GameSession, round: NumberRound, unlocked_achievements: Collection<int, \App\Models\Achievement>}
     */
    private function submitNumbers(User $user, DailyChallenge $challenge, array $input): array
    {
        $submittedSolution = trim((string) ($input['submitted_solution'] ?? ''));
        $numbers = $challenge->payload['numbers'] ?? [];
        $targetNumber = (int) ($challenge->payload['target_number'] ?? 0);
        $evaluation = $this->evaluateSubmittedSolution($submittedSolution, $numbers);

        if (! $evaluation['valid']) {
            throw ValidationException::withMessages([
                'submitted_solution' => $evaluation['message'],
            ]);
        }

        $resultValue = (int) $evaluation['result'];
        $difference = abs($targetNumber - $resultValue);
        $score = $this->scoreForDifference($difference);
        $isPerfect = $difference === 0;

        return DB::transaction(function () use ($user, $challenge, $submittedSolution, $resultValue, $difference, $score, $isPerfect) {
            $lockedChallenge = DailyChallenge::query()->lockForUpdate()->findOrFail($challenge->id);
            $existingAttempt = DailyChallengeAttempt::query()
                ->where('daily_challenge_id', $lockedChallenge->id)
                ->where('user_id', $user->id)
                ->first();

            if ($existingAttempt) {
                throw ValidationException::withMessages([
                    'submitted_solution' => 'Tu as déjà tenté ce défi chiffres aujourd’hui.',
                ]);
            }

            $completedAt = now();
            $gameSession = GameSession::query()->create([
                'user_id' => $user->id,
                'age_group_id' => $lockedChallenge->age_group_id,
                'daily_challenge_id' => $lockedChallenge->id,
                'game_type' => 'numbers',
                'score' => 0,
                'status' => 'started',
                'started_at' => $completedAt,
            ]);

            $numberRound = $gameSession->numberRounds()->create([
                'numbers' => $lockedChallenge->payload['numbers'],
                'target_number' => $lockedChallenge->payload['target_number'],
                'submitted_solution' => $submittedSolution,
                'result_value' => $resultValue,
                'score' => $score,
            ]);

            $gameSession->update([
                'score' => $score,
                'status' => 'completed',
                'completed_at' => $completedAt,
            ]);

            $attempt = DailyChallengeAttempt::query()->create([
                'daily_challenge_id' => $lockedChallenge->id,
                'user_id' => $user->id,
                'game_session_id' => $gameSession->id,
                'score' => $score,
                'submitted_solution' => $submittedSolution,
                'result_payload' => [
                    'result_value' => $resultValue,
                    'difference' => $difference,
                    'perfect_score' => 100,
                ],
                'is_perfect' => $isPerfect,
                'attempted_at' => $completedAt,
            ]);

            $unlockedAchievements = $this->achievementService->unlockForCompletedGame($user, $gameSession);

            return [
                'attempt' => $attempt->fresh(['user.playerProfile', 'gameSession']),
                'game_session' => $gameSession->fresh(),
                'round' => $numberRound,
                'unlocked_achievements' => $unlockedAchievements,
            ];
        });
    }

    /**
     * @param  array<int, string>  $availableLetters
     */
    private function wordUsesAvailableLetters(string $submittedWord, array $availableLetters): bool
    {
        $availableCounts = array_count_values($availableLetters);
        $submittedCounts = array_count_values(str_split($submittedWord));

        foreach ($submittedCounts as $letter => $count) {
            if (($availableCounts[$letter] ?? 0) < $count) {
                return false;
            }
        }

        return true;
    }

    /**
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
