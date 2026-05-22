<?php

namespace App\Http\Controllers;

use App\Models\AgeGroup;
use App\Models\GameSession;
use App\Services\AchievementService;
use App\Services\AvatarCatalogService;
use App\Services\GameIntelligence\GameIntelligenceManager;
use App\Services\GameplaySecurityService;
use App\Services\GameIntelligence\OpponentAiService;
use App\Services\WordValidationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LetterGameController extends Controller
{
    private const SESSION_KEY = 'chronomots.letters.draws';

    public function __construct(
        private readonly AchievementService $achievementService,
        private readonly AvatarCatalogService $avatarCatalogService,
        private readonly GameIntelligenceManager $gameIntelligenceManager,
        private readonly GameplaySecurityService $gameplaySecurityService,
        private readonly OpponentAiService $opponentAiService,
        private readonly WordValidationService $wordValidationService,
    ) {
    }

    /**
     * Display a new letters game for the selected age group.
     */
    public function show(Request $request, AgeGroup $ageGroup): View
    {
        $opponentLevel = $this->opponentAiService->normalizeLevel($request->query('opponent_level'));
        $draw = $this->freshInteractiveDraw($ageGroup, $opponentLevel);

        $request->session()->put(self::SESSION_KEY.'.'.$draw['draw_id'], $draw);

        return view('play.letters', $this->gameViewData($ageGroup, $draw, '', null, $opponentLevel));
    }

    /**
     * Reveal the next letter after a vowel/consonant choice.
     */
    public function draw(Request $request, AgeGroup $ageGroup): View|Response|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'draw_id' => ['required', 'string'],
            'letter_type' => ['required', Rule::in(['vowel', 'consonant'])],
            'opponent_level' => ['nullable', Rule::in(array_keys($this->opponentAiService->levels()))],
        ], [
            'letter_type.required' => 'Choisis voyelle ou consonne pour révéler la prochaine lettre.',
        ]);

        $opponentLevel = $this->opponentAiService->normalizeLevel($request->string('opponent_level')->toString());
        $drawId = $request->string('draw_id')->toString();

        if ($validator->fails()) {
            $draw = $this->findExistingDraw($request, $drawId) ?? $this->freshInteractiveDraw($ageGroup, $opponentLevel);

            return response()->view(
                'play.letters',
                $this->gameViewData(
                    $ageGroup,
                    $draw,
                    '',
                    $validator->errors()->first('letter_type'),
                    $opponentLevel,
                ),
                422,
            );
        }

        $draw = $this->findExistingDraw($request, $drawId);

        if (! $draw) {
            $this->gameplaySecurityService->logInvalidAttempt('letters', $request, 'missing_draw_for_reveal', [
                'draw_id' => $drawId,
            ]);

            return redirect()
                ->route('play.letters.show', ['ageGroup' => $ageGroup, 'opponent_level' => $opponentLevel])
                ->withErrors([
                    'submitted_word' => 'Ce tirage interactif n’est plus disponible. Relance une nouvelle partie.',
                ]);
        }

        $draw['opponent_level'] = $opponentLevel ?? ($draw['opponent_level'] ?? null);

        if (! $this->gameplaySecurityService->stateMatches($draw, 'letters', $ageGroup->id)) {
            $this->gameplaySecurityService->logInvalidAttempt('letters', $request, 'interactive_draw_context_mismatch', [
                'draw_id' => $draw['draw_id'] ?? null,
            ]);
            $request->session()->forget(self::SESSION_KEY.'.'.$drawId);

            return redirect()
                ->route('play.letters.show', ['ageGroup' => $ageGroup, 'opponent_level' => $opponentLevel])
                ->withErrors([
                    'submitted_word' => 'Ce tirage n’est plus valide. Relance une nouvelle partie.',
                ]);
        }

        if ($this->gameplaySecurityService->isExpired($draw, $ageGroup->letters_timer_seconds)) {
            $request->session()->forget(self::SESSION_KEY.'.'.$drawId);

            return response()->view(
                'play.letters',
                $this->gameViewData(
                    $ageGroup,
                    $draw,
                    '',
                    $this->gameplaySecurityService->expirationMessage('ce tirage lettres'),
                    $draw['opponent_level'],
                ),
                422,
            );
        }

        if ($this->drawIsComplete($draw, $ageGroup)) {
            return response()->view(
                'play.letters',
                $this->gameViewData(
                    $ageGroup,
                    $draw,
                    '',
                    'Le tirage est complet. Tu peux maintenant proposer ton mot.',
                    $draw['opponent_level'],
                ),
                422,
            );
        }

        $letterType = $request->string('letter_type')->toString();
        $allowedChoices = $draw['allowed_choices'] ?? ['vowel', 'consonant'];

        if (! in_array($letterType, $allowedChoices, true)) {
            $this->gameplaySecurityService->logInvalidAttempt('letters', $request, 'interactive_choice_not_allowed', [
                'draw_id' => $drawId,
                'letter_type' => $letterType,
                'allowed_choices' => $allowedChoices,
            ]);

            return response()->view(
                'play.letters',
                $this->gameViewData(
                    $ageGroup,
                    $draw,
                    '',
                    'Ce choix n’est plus disponible pour garder un tirage jouable. Choisis l’autre type de lettre.',
                    $draw['opponent_level'],
                ),
                422,
            );
        }

        $updatedDraw = $this->gameIntelligenceManager->revealLettersChoice($ageGroup, $draw, $letterType);
        $updatedDraw = [
            ...$draw,
            ...$updatedDraw,
            'opponent_level' => $draw['opponent_level'],
        ];

        $request->session()->put(self::SESSION_KEY.'.'.$drawId, $updatedDraw);

        return view('play.letters', $this->gameViewData($ageGroup, $updatedDraw, '', null, $updatedDraw['opponent_level']));
    }

    /**
     * Submit a word for the current letters game.
     */
    public function submit(Request $request, AgeGroup $ageGroup): View|Response|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'draw_id' => ['required', 'string'],
            'submitted_word' => ['required', 'string', 'regex:/^[\pL\'-]+$/u', 'max:32'],
            'opponent_level' => ['nullable', Rule::in(array_keys($this->opponentAiService->levels()))],
        ], [
            'submitted_word.required' => 'Propose un mot avant de valider.',
            'submitted_word.regex' => 'Le mot doit contenir uniquement des lettres.',
        ]);

        $opponentLevel = $this->opponentAiService->normalizeLevel($request->string('opponent_level')->toString());

        if ($validator->fails()) {
            $draw = $this->findExistingDraw($request, $request->string('draw_id')->toString())
                ?? $this->freshInteractiveDraw($ageGroup, $opponentLevel);

            $this->gameplaySecurityService->logInvalidAttempt('letters', $request, 'validation_failed', [
                'draw_id' => $draw['draw_id'],
                'errors' => $validator->errors()->keys(),
            ]);

            return response()->view(
                'play.letters',
                $this->gameViewData(
                    $ageGroup,
                    $draw,
                    $request->string('submitted_word')->toString(),
                    $validator->errors()->first('submitted_word'),
                    $opponentLevel,
                ),
                422,
            );
        }

        $draw = $this->findExistingDraw($request, $request->string('draw_id')->toString());

        if (! $draw) {
            $this->gameplaySecurityService->logInvalidAttempt('letters', $request, 'missing_draw', [
                'draw_id' => $request->string('draw_id')->toString(),
            ]);

            return redirect()
                ->route('play.letters.show', $ageGroup)
                ->withErrors([
                    'submitted_word' => 'Cette partie n’est plus disponible. Relance un nouveau tirage.',
                ]);
        }

        $draw['opponent_level'] = $opponentLevel ?? ($draw['opponent_level'] ?? null);

        if (! $this->gameplaySecurityService->stateMatches($draw, 'letters', $ageGroup->id)) {
            $this->gameplaySecurityService->logInvalidAttempt('letters', $request, 'draw_context_mismatch', [
                'draw_id' => $draw['draw_id'] ?? null,
                'draw_age_group_id' => $draw['age_group_id'] ?? null,
                'draw_game_type' => $draw['game_type'] ?? null,
            ]);
            $request->session()->forget(self::SESSION_KEY.'.'.$request->string('draw_id')->toString());

            return redirect()
                ->route('play.letters.show', $ageGroup)
                ->withErrors([
                    'submitted_word' => 'Cette partie n’est plus valide. Relance un nouveau tirage.',
                ]);
        }

        if ($this->gameplaySecurityService->isExpired($draw, $ageGroup->letters_timer_seconds)) {
            $this->gameplaySecurityService->logExpiredSubmission('letters', $request, [
                'draw_id' => $draw['draw_id'],
                'started_at' => $draw['started_at'] ?? null,
                'expires_at' => $draw['expires_at'] ?? null,
            ]);
            $request->session()->forget(self::SESSION_KEY.'.'.$draw['draw_id']);

            return response()->view(
                'play.letters',
                $this->gameViewData(
                    $ageGroup,
                    $draw,
                    $request->string('submitted_word')->toString(),
                    $this->gameplaySecurityService->expirationMessage('ce tirage lettres'),
                    $draw['opponent_level'],
                ),
                422,
            );
        }

        if (! $this->drawIsComplete($draw, $ageGroup)) {
            $this->gameplaySecurityService->logInvalidAttempt('letters', $request, 'submit_before_draw_complete', [
                'draw_id' => $draw['draw_id'],
                'revealed_letters' => count($draw['letters'] ?? []),
                'letters_target' => $draw['letters_target'] ?? $ageGroup->letters_timer_seconds,
            ]);

            return response()->view(
                'play.letters',
                $this->gameViewData(
                    $ageGroup,
                    $draw,
                    '',
                    'Le tirage n’est pas encore complet. Révèle toutes les lettres avant de proposer un mot.',
                    $draw['opponent_level'],
                ),
                422,
            );
        }

        $submittedWord = $this->wordValidationService->normalize(
            $request->string('submitted_word')->toString(),
        );

        if ($submittedWord === '') {
            $this->gameplaySecurityService->logInvalidAttempt('letters', $request, 'empty_normalized_word', [
                'draw_id' => $draw['draw_id'],
            ]);

            return response()->view(
                'play.letters',
                $this->gameViewData(
                    $ageGroup,
                    $draw,
                    '',
                    'Le mot doit contenir uniquement des lettres.',
                    $draw['opponent_level'],
                ),
                422,
            );
        }

        if (! $this->wordUsesAvailableLetters($submittedWord, $draw['letters'])) {
            $this->gameplaySecurityService->logInvalidAttempt('letters', $request, 'unavailable_letters', [
                'draw_id' => $draw['draw_id'],
                'submitted_word' => $submittedWord,
            ]);

            return response()->view(
                'play.letters',
                $this->gameViewData(
                    $ageGroup,
                    $draw,
                    $submittedWord,
                    'Le mot proposé utilise des lettres qui ne sont pas disponibles dans le tirage.',
                    $draw['opponent_level'],
                ),
                422,
            );
        }

        $validation = $this->wordValidationService->validateForAgeGroup(
            $submittedWord,
            $ageGroup,
        );

        if (! $validation['valid']) {
            $this->gameplaySecurityService->logInvalidAttempt('letters', $request, 'dictionary_validation_failed', [
                'draw_id' => $draw['draw_id'],
                'submitted_word' => $submittedWord,
                'validation_message' => $validation['message'],
            ]);

            return response()->view(
                'play.letters',
                $this->gameViewData(
                    $ageGroup,
                    $draw,
                    $submittedWord,
                    $validation['message'],
                    $draw['opponent_level'],
                ),
                422,
            );
        }

        $score = strlen($submittedWord) * 10;
        $completedAt = now();
        $opponentLevel = $draw['opponent_level'];
        $opponentResult = $opponentLevel !== null
            ? $this->opponentAiService->playLetters($draw['letters'], $ageGroup, $opponentLevel)
            : null;
        $duelOutcome = $this->duelOutcome($score, $opponentResult['score'] ?? null);

        [$gameSession, $letterRound] = DB::transaction(function () use ($request, $ageGroup, $draw, $submittedWord, $score, $completedAt) {
            $gameSession = GameSession::query()->create([
                'user_id' => $request->user()->id,
                'age_group_id' => $ageGroup->id,
                'game_type' => 'letters',
                'score' => 0,
                'status' => 'started',
                'started_at' => $draw['started_at'],
                'expires_at' => $this->gameplaySecurityService->expiresAt($draw, $ageGroup->letters_timer_seconds),
            ]);

            $letterRound = $gameSession->letterRounds()->create([
                'letters' => implode('', $draw['letters']),
                'submitted_word' => $submittedWord,
                'best_word' => null,
                'score' => $score,
            ]);

            $gameSession->update([
                'score' => $score,
                'status' => 'completed',
                'completed_at' => $completedAt,
            ]);

            return [$gameSession->fresh(), $letterRound];
        });

        $unlockedAchievements = $this->achievementService->unlockForCompletedGame(
            $request->user(),
            $gameSession,
            [
                'submitted_word' => $submittedWord,
                'opponent_level' => $opponentLevel,
                'duel_outcome' => $duelOutcome,
            ],
        );

        $request->session()->forget(self::SESSION_KEY.'.'.$draw['draw_id']);

        return view('play.letters-result', [
            'ageGroup' => $ageGroup,
            'gameSession' => $gameSession,
            'letterRound' => $letterRound,
            'letters' => str_split($letterRound->letters),
            'submittedWord' => $submittedWord,
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
     * Render the letters game page.
     */
    private function gameViewData(
        AgeGroup $ageGroup,
        array $draw,
        string $submittedWord = '',
        ?string $errorMessage = null,
        ?string $opponentLevel = null,
    ): array {
        $normalizedOpponentLevel = $opponentLevel ?? ($draw['opponent_level'] ?? null);

        return [
            'ageGroup' => $ageGroup,
            'drawId' => $draw['draw_id'],
            'letters' => $draw['letters'],
            'lettersCount' => (int) ($draw['letters_target'] ?? count($draw['letters'])),
            'revealedLettersCount' => count($draw['letters']),
            'remainingLettersCount' => max(0, ((int) ($draw['letters_target'] ?? count($draw['letters']))) - count($draw['letters'])),
            'timerSeconds' => $ageGroup->letters_timer_seconds,
            'initialRemainingSeconds' => $this->gameplaySecurityService->remainingSeconds($draw, $ageGroup->letters_timer_seconds),
            'startedAtIso' => $this->gameplaySecurityService->startedAt($draw)?->toIso8601String(),
            'expiresAtIso' => $this->gameplaySecurityService->expiresAt($draw, $ageGroup->letters_timer_seconds)?->toIso8601String(),
            'submittedWord' => $submittedWord,
            'errorMessage' => $errorMessage,
            'allowedChoices' => $draw['allowed_choices'] ?? ['vowel', 'consonant'],
            'drawChoiceHistory' => $draw['choice_history'] ?? [],
            'drawCompleted' => $this->drawIsComplete($draw, $ageGroup),
            'latestLetter' => $draw['latest_letter'] ?? null,
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

    /**
     * @return array<string, mixed>
     */
    private function freshInteractiveDraw(AgeGroup $ageGroup, ?string $opponentLevel): array
    {
        return [
            ...$this->gameIntelligenceManager->startLettersDraw($ageGroup),
            ...$this->gameplaySecurityService->createTimedWindow($ageGroup->letters_timer_seconds),
            'draw_id' => Str::uuid()->toString(),
            'age_group_id' => $ageGroup->id,
            'game_type' => 'letters',
            'opponent_level' => $opponentLevel,
        ];
    }

    /**
     * Ensure the proposed word only uses available letters.
     *
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

    private function drawIsComplete(array $draw, AgeGroup $ageGroup): bool
    {
        $target = (int) ($draw['letters_target'] ?? count($draw['letters'] ?? []));

        return count($draw['letters'] ?? []) >= $target;
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
