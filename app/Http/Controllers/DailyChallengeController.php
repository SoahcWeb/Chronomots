<?php

namespace App\Http\Controllers;

use App\Models\DailyChallenge;
use App\Services\AvatarCatalogService;
use App\Services\DailyChallengeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DailyChallengeController extends Controller
{
    public function __construct(
        private readonly AvatarCatalogService $avatarCatalogService,
        private readonly DailyChallengeService $dailyChallengeService,
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
            return $this->renderResult($request, $dailyChallenge, $attempt, collect());
        }

        return view('daily-challenges.'.$dailyChallenge->game_type, [
            'challenge' => $dailyChallenge,
            'ageGroup' => $dailyChallenge->ageGroup,
            'payload' => $dailyChallenge->payload,
            'bestScoreOfDay' => $this->dailyChallengeService->bestScoreOfDay($dailyChallenge),
            'attemptsCount' => $this->dailyChallengeService->attemptsCount($dailyChallenge),
        ]);
    }

    public function submit(Request $request, DailyChallenge $dailyChallenge): View|RedirectResponse
    {
        if (! $dailyChallenge->challenge_date->isToday()) {
            return redirect()->route('daily-challenges.index');
        }

        try {
            $result = $this->dailyChallengeService->submit($request->user(), $dailyChallenge->load('ageGroup'), $request->all());
        } catch (ValidationException $exception) {
            $attempt = $this->dailyChallengeService->userAttempt($dailyChallenge, $request->user());

            if ($attempt) {
                return redirect()->route('daily-challenges.show', $dailyChallenge)
                    ->with('status', 'daily-challenge-already-attempted');
            }

            return response()->view('daily-challenges.'.$dailyChallenge->game_type, [
                'challenge' => $dailyChallenge,
                'ageGroup' => $dailyChallenge->ageGroup,
                'payload' => $dailyChallenge->payload,
                'bestScoreOfDay' => $this->dailyChallengeService->bestScoreOfDay($dailyChallenge),
                'attemptsCount' => $this->dailyChallengeService->attemptsCount($dailyChallenge),
                'submittedWord' => (string) $request->input('submitted_word', ''),
                'submittedSolution' => (string) $request->input('submitted_solution', ''),
                'errorMessage' => collect($exception->errors())->flatten()->first(),
            ], 422);
        }

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
}
