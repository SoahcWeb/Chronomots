<?php

namespace App\Http\Controllers;

use App\Models\AgeGroup;
use App\Models\GameSession;
use App\Services\AchievementService;
use App\Services\AvatarCatalogService;
use App\Services\GameIntelligence\GameIntelligenceManager;
use App\Services\GameplaySecurityService;
use App\Services\NumbersExpressionEvaluator;
use App\Services\GameIntelligence\OpponentAiService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class NumberGameController extends Controller
{
    private const SESSION_KEY = 'chronomots.numbers.draws';

    public function __construct(
        private readonly AchievementService $achievementService,
        private readonly AvatarCatalogService $avatarCatalogService,
        private readonly GameIntelligenceManager $gameIntelligenceManager,
        private readonly GameplaySecurityService $gameplaySecurityService,
        private readonly NumbersExpressionEvaluator $numbersExpressionEvaluator,
        private readonly OpponentAiService $opponentAiService,
    ) {
    }

    /**
     * Display a new numbers game for the selected age group.
     */
    public function show(Request $request, AgeGroup $ageGroup): View
    {
        $opponentLevel = $this->opponentAiService->normalizeLevel($request->query('opponent_level'));
        $draw = $this->gameIntelligenceManager->createNumbersDraw($ageGroup);
        $draw['opponent_level'] = $opponentLevel;

        $request->session()->put(self::SESSION_KEY.'.'.$draw['draw_id'], $draw);

        return view('play.numbers', $this->gameViewData($ageGroup, $draw, '', null, $opponentLevel));
    }

    /**
     * Submit a calculation for the current numbers game.
     */
    public function submit(Request $request, AgeGroup $ageGroup): View|Response|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'draw_id' => ['required', 'string'],
            'submitted_solution' => ['required', 'string', 'max:255'],
            'opponent_level' => ['nullable', Rule::in(array_keys($this->opponentAiService->levels()))],
        ], [
            'submitted_solution.required' => 'Entre un calcul avant de valider.',
        ]);

        $opponentLevel = $this->opponentAiService->normalizeLevel($request->string('opponent_level')->toString());

        if ($validator->fails()) {
            $draw = $this->findExistingDraw($request, $request->string('draw_id')->toString())
                ?? $this->gameIntelligenceManager->createNumbersDraw($ageGroup);
            $draw['opponent_level'] = $opponentLevel;

            $this->gameplaySecurityService->logInvalidAttempt('numbers', $request, 'validation_failed', [
                'draw_id' => $draw['draw_id'],
                'errors' => $validator->errors()->keys(),
            ]);

            return response()->view(
                'play.numbers',
                $this->gameViewData(
                    $ageGroup,
                    $draw,
                    $request->string('submitted_solution')->toString(),
                    $validator->errors()->first('submitted_solution'),
                    $opponentLevel,
                ),
                422,
            );
        }

        $draw = $this->findExistingDraw($request, $request->string('draw_id')->toString());

        if (! $draw) {
            $this->gameplaySecurityService->logInvalidAttempt('numbers', $request, 'missing_draw', [
                'draw_id' => $request->string('draw_id')->toString(),
            ]);

            return redirect()
                ->route('play.numbers.show', $ageGroup)
                ->withErrors([
                    'submitted_solution' => 'Cette partie n’est plus disponible. Relance un nouveau tirage.',
                ]);
        }

        $draw['opponent_level'] = $opponentLevel ?? ($draw['opponent_level'] ?? null);

        if (! $this->gameplaySecurityService->stateMatches($draw, 'numbers', $ageGroup->id)) {
            $this->gameplaySecurityService->logInvalidAttempt('numbers', $request, 'draw_context_mismatch', [
                'draw_id' => $draw['draw_id'] ?? null,
                'draw_age_group_id' => $draw['age_group_id'] ?? null,
                'draw_game_type' => $draw['game_type'] ?? null,
            ]);
            $request->session()->forget(self::SESSION_KEY.'.'.$request->string('draw_id')->toString());

            return redirect()
                ->route('play.numbers.show', $ageGroup)
                ->withErrors([
                    'submitted_solution' => 'Cette partie n’est plus valide. Relance un nouveau tirage.',
                ]);
        }

        if ($this->gameplaySecurityService->isExpired($draw, $ageGroup->numbers_timer_seconds)) {
            $this->gameplaySecurityService->logExpiredSubmission('numbers', $request, [
                'draw_id' => $draw['draw_id'],
                'started_at' => $draw['started_at'] ?? null,
                'expires_at' => $draw['expires_at'] ?? null,
            ]);
            $request->session()->forget(self::SESSION_KEY.'.'.$draw['draw_id']);

            return response()->view(
                'play.numbers',
                $this->gameViewData(
                    $ageGroup,
                    $draw,
                    $request->string('submitted_solution')->toString(),
                    $this->gameplaySecurityService->expirationMessage('ce tirage chiffres'),
                    $draw['opponent_level'],
                ),
                422,
            );
        }

        $submittedSolution = trim($request->string('submitted_solution')->toString());
        $evaluation = $this->numbersExpressionEvaluator->evaluateSubmittedSolution($submittedSolution, $draw['numbers']);

        if (! $evaluation['valid']) {
            $this->gameplaySecurityService->logInvalidAttempt('numbers', $request, 'expression_validation_failed', [
                'draw_id' => $draw['draw_id'],
                'submitted_solution' => $submittedSolution,
                'validation_message' => $evaluation['message'],
            ]);

            return response()->view(
                'play.numbers',
                $this->gameViewData(
                    $ageGroup,
                    $draw,
                    $submittedSolution,
                    $evaluation['message'],
                    $draw['opponent_level'],
                ),
                422,
            );
        }

        $resultValue = $evaluation['result'];
        $targetNumber = $draw['target_number'];
        $difference = abs($targetNumber - $resultValue);
        $score = $this->numbersExpressionEvaluator->scoreForDifference($difference);
        $completedAt = now();
        $opponentLevel = $draw['opponent_level'];
        $opponentResult = $opponentLevel !== null
            ? $this->opponentAiService->playNumbers($draw['numbers'], $targetNumber, $opponentLevel)
            : null;
        $duelOutcome = $this->duelOutcome($score, $opponentResult['score'] ?? null);

        [$gameSession, $numberRound] = DB::transaction(function () use ($request, $ageGroup, $draw, $submittedSolution, $resultValue, $score, $completedAt) {
            $gameSession = GameSession::query()->create([
                'user_id' => $request->user()->id,
                'age_group_id' => $ageGroup->id,
                'game_type' => 'numbers',
                'score' => 0,
                'status' => 'started',
                'started_at' => $draw['started_at'],
                'expires_at' => $this->gameplaySecurityService->expiresAt($draw, $ageGroup->numbers_timer_seconds),
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

        $unlockedAchievements = $this->achievementService->unlockForCompletedGame(
            $request->user(),
            $gameSession,
            [
                'opponent_level' => $opponentLevel,
                'duel_outcome' => $duelOutcome,
            ],
        );

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
            'opponentResult' => $opponentResult,
            'duelOutcome' => $duelOutcome,
            'opponentLevel' => $opponentLevel,
            'opponentLevelLabel' => $this->opponentAiService->labelForLevel($opponentLevel),
            'unlockedAchievements' => $unlockedAchievements,
            'playerAvatar' => $this->avatarCatalogService->avatarForUser($request->user()),
            'opponentAvatar' => $opponentLevel ? $this->avatarCatalogService->aiAvatar($opponentLevel) : null,
        ]);
    }

    /**
     * Render the numbers game page.
     */
    private function gameViewData(
        AgeGroup $ageGroup,
        array $draw,
        string $submittedSolution = '',
        ?string $errorMessage = null,
        ?string $opponentLevel = null,
    ): array {
        $normalizedOpponentLevel = $opponentLevel ?? ($draw['opponent_level'] ?? null);

        return [
            'ageGroup' => $ageGroup,
            'drawId' => $draw['draw_id'],
            'numbers' => $draw['numbers'],
            'targetNumber' => $draw['target_number'],
            'timerSeconds' => $ageGroup->numbers_timer_seconds,
            'initialRemainingSeconds' => $this->gameplaySecurityService->remainingSeconds($draw, $ageGroup->numbers_timer_seconds),
            'startedAtIso' => $this->gameplaySecurityService->startedAt($draw)?->toIso8601String(),
            'expiresAtIso' => $this->gameplaySecurityService->expiresAt($draw, $ageGroup->numbers_timer_seconds)?->toIso8601String(),
            'submittedSolution' => $submittedSolution,
            'errorMessage' => $errorMessage,
            'opponentLevel' => $normalizedOpponentLevel,
            'opponentLevelLabel' => $this->opponentAiService->labelForLevel($normalizedOpponentLevel),
        ];
    }

    /**
     * Retrieve an existing draw from session.
     */
    private function findExistingDraw(Request $request, string $drawId): ?array
    {
        $draw = $request->session()->get(self::SESSION_KEY.'.'.$drawId);

        return is_array($draw) ? $draw : null;
    }

    private function duelOutcome(int $playerScore, ?int $opponentScore): ?string
    {
        if ($opponentScore === null) {
            return null;
        }

        return match (true) {
            $playerScore > $opponentScore => 'Victoire',
            $playerScore < $opponentScore => 'Défaite',
            default => 'Égalité',
        };
    }
}
