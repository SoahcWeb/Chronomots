<?php

namespace App\Http\Controllers;

use App\Models\DailyChallenge;
use App\Services\AvatarCatalogService;
use App\Services\DailyChallengeService;
use App\Services\GameplaySecurityService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DailyChallengeController extends Controller
{
    private const TIMER_SESSION_KEY = 'chronomots.daily_challenges.timers';

    public function __construct(
        private readonly AvatarCatalogService $avatarCatalogService,
        private readonly DailyChallengeService $dailyChallengeService,
        private readonly GameplaySecurityService $gameplaySecurityService,
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $todayChallenges = $this->dailyChallengeService->todayChallenges()
            ->map(function (DailyChallenge $challenge) use ($user): array {
                return [
                    'challenge' => $challenge,
                    'user_attempt' => $this->dailyChallengeService->userAttempt($challenge, $user),
                    'best_score' => $this->dailyChallengeService->bestScoreOfDay($challenge),
                    'attempts_count' => $this->dailyChallengeService->attemptsCount($challenge),
                ];
            });

        return view('daily-challenges.index', [
            'todayChallenges' => $todayChallenges,
            'history' => $this->dailyChallengeService->historyForUser($user),
        ]);
    }

    public function show(Request $request, DailyChallenge $dailyChallenge): View|RedirectResponse
    {
        if (! $dailyChallenge->challenge_date->isToday()) {
            return redirect()->route('daily-challenges.index');
        }

        $dailyChallenge->load('ageGroup');
        $attempt = $this->dailyChallengeService->userAttempt($dailyChallenge, $request->user());

        if ($attempt) {
            $this->forgetTimerState($request, $dailyChallenge);

            return $this->renderResult($request, $dailyChallenge, $attempt, collect());
        }

        return view(
            'daily-challenges.'.$dailyChallenge->game_type,
            $this->challengeViewData($request, $dailyChallenge),
        );
    }

    public function submit(Request $request, DailyChallenge $dailyChallenge): View|RedirectResponse
    {
        if (! $dailyChallenge->challenge_date->isToday()) {
            return redirect()->route('daily-challenges.index');
        }

        $dailyChallenge->load('ageGroup');

        $timerState = $this->existingTimerState($request, $dailyChallenge);

        try {
            if (! is_array($timerState) || ! $this->gameplaySecurityService->stateMatches($timerState, $dailyChallenge->game_type, $dailyChallenge->age_group_id, $dailyChallenge->id)) {
                $this->gameplaySecurityService->logInvalidAttempt('daily-challenge', $request, 'missing_or_invalid_timer_state', [
                    'challenge_id' => $dailyChallenge->id,
                    'game_type' => $dailyChallenge->game_type,
                ]);

                throw ValidationException::withMessages([
                    'challenge' => $this->gameplaySecurityService->timerSyncMessage('ce défi quotidien'),
                ]);
            }

            if ($this->gameplaySecurityService->isExpired($timerState, (int) ($dailyChallenge->payload['timer_seconds'] ?? 0))) {
                $this->gameplaySecurityService->logExpiredSubmission('daily-challenge', $request, [
                    'challenge_id' => $dailyChallenge->id,
                    'started_at' => $timerState['started_at'] ?? null,
                    'expires_at' => $timerState['expires_at'] ?? null,
                ]);

                throw ValidationException::withMessages([
                    'challenge' => $this->gameplaySecurityService->expirationMessage('ce défi quotidien'),
                ]);
            }

            $result = $this->dailyChallengeService->submit(
                $request->user(),
                $dailyChallenge,
                $request->all(),
                $timerState,
            );
        } catch (ValidationException $exception) {
            $attempt = $this->dailyChallengeService->userAttempt($dailyChallenge, $request->user());

            if ($attempt) {
                $this->forgetTimerState($request, $dailyChallenge);

                return redirect()->route('daily-challenges.show', $dailyChallenge)
                    ->with('status', 'daily-challenge-already-attempted');
            }

            $firstError = collect($exception->errors())->flatten()->first();
            $this->gameplaySecurityService->logInvalidAttempt('daily-challenge', $request, 'validation_failed', [
                'challenge_id' => $dailyChallenge->id,
                'game_type' => $dailyChallenge->game_type,
                'message' => $firstError,
            ]);

            return response()->view(
                'daily-challenges.'.$dailyChallenge->game_type,
                $this->challengeViewData(
                    $request,
                    $dailyChallenge,
                    (string) $request->input('submitted_word', ''),
                    (string) $request->input('submitted_solution', ''),
                    $firstError,
                    $timerState,
                ),
                422,
            );
        }

        $this->forgetTimerState($request, $dailyChallenge);

        return $this->renderResult(
            $request,
            $dailyChallenge->fresh(['ageGroup']),
            $result['attempt'],
            $result['unlocked_achievements'],
            $result['round'],
        );
    }

    private function renderResult(Request $request, DailyChallenge $challenge, $attempt, $unlockedAchievements, $round = null): View
    {
        $leaderboard = $this->dailyChallengeService->leaderboard($challenge);
        $playerAvatar = $this->avatarCatalogService->avatarForUser($request->user());

        return view('daily-challenges.result', [
            'challenge' => $challenge,
            'ageGroup' => $challenge->ageGroup,
            'attempt' => $attempt,
            'round' => $round,
            'leaderboard' => $leaderboard,
            'bestScoreOfDay' => $this->dailyChallengeService->bestScoreOfDay($challenge),
            'attemptsCount' => $this->dailyChallengeService->attemptsCount($challenge),
            'unlockedAchievements' => $unlockedAchievements,
            'playerAvatar' => $playerAvatar,
            'isCurrentUserBest' => $leaderboard->first()?->user_id === $request->user()->id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function challengeViewData(
        Request $request,
        DailyChallenge $challenge,
        string $submittedWord = '',
        string $submittedSolution = '',
        ?string $errorMessage = null,
        ?array $timerState = null,
    ): array {
        $timerState = $timerState ?? $this->timerState($request, $challenge);
        $timerSeconds = (int) ($challenge->payload['timer_seconds'] ?? 0);

        return [
            'challenge' => $challenge,
            'ageGroup' => $challenge->ageGroup,
            'payload' => $challenge->payload,
            'bestScoreOfDay' => $this->dailyChallengeService->bestScoreOfDay($challenge),
            'attemptsCount' => $this->dailyChallengeService->attemptsCount($challenge),
            'submittedWord' => $submittedWord,
            'submittedSolution' => $submittedSolution,
            'errorMessage' => $errorMessage,
            'initialRemainingSeconds' => $this->gameplaySecurityService->remainingSeconds($timerState, $timerSeconds),
            'startedAtIso' => $this->gameplaySecurityService->startedAt($timerState)?->toIso8601String(),
            'expiresAtIso' => $this->gameplaySecurityService->expiresAt($timerState, $timerSeconds)?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function timerState(Request $request, DailyChallenge $challenge): array
    {
        $existingState = $this->existingTimerState($request, $challenge);

        if (is_array($existingState) && $this->gameplaySecurityService->stateMatches($existingState, $challenge->game_type, $challenge->age_group_id, $challenge->id)) {
            return $existingState;
        }

        $timerState = $this->gameplaySecurityService->createTimedWindow((int) ($challenge->payload['timer_seconds'] ?? 0));
        $timerState['game_type'] = $challenge->game_type;
        $timerState['age_group_id'] = $challenge->age_group_id;
        $timerState['challenge_id'] = $challenge->id;

        $request->session()->put($this->timerSessionKey($challenge), $timerState);

        return $timerState;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function existingTimerState(Request $request, DailyChallenge $challenge): ?array
    {
        $timerState = $request->session()->get($this->timerSessionKey($challenge));

        return is_array($timerState) ? $timerState : null;
    }

    private function forgetTimerState(Request $request, DailyChallenge $challenge): void
    {
        $request->session()->forget($this->timerSessionKey($challenge));
    }

    private function timerSessionKey(DailyChallenge $challenge): string
    {
        return self::TIMER_SESSION_KEY.'.'.$challenge->public_id;
    }
}
