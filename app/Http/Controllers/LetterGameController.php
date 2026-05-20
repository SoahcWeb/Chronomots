<?php

namespace App\Http\Controllers;

use App\Models\AgeGroup;
use App\Models\GameSession;
use App\Services\GameIntelligence\GameIntelligenceManager;
use App\Services\GameIntelligence\OpponentAiService;
use App\Services\WordValidationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\Rule;

class LetterGameController extends Controller
{
    private const SESSION_KEY = 'chronomots.letters.draws';

    public function __construct(
        private readonly GameIntelligenceManager $gameIntelligenceManager,
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
        $draw = $this->gameIntelligenceManager->createLettersDraw($ageGroup);
        $draw['opponent_level'] = $opponentLevel;

        $request->session()->put(self::SESSION_KEY.'.'.$draw['draw_id'], $draw);

        return $this->renderGameView($ageGroup, $draw, null, '', $opponentLevel);
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
                ?? $this->gameIntelligenceManager->createLettersDraw($ageGroup);
            $draw['opponent_level'] = $opponentLevel;

            return response()->view('play.letters', [
                'ageGroup' => $ageGroup,
                'drawId' => $draw['draw_id'],
                'letters' => $draw['letters'],
                'lettersCount' => count($draw['letters']),
                'timerSeconds' => $ageGroup->letters_timer_seconds,
                'submittedWord' => $request->string('submitted_word')->toString(),
                'errorMessage' => $validator->errors()->first('submitted_word'),
                'opponentLevel' => $opponentLevel,
                'opponentLevelLabel' => $this->opponentAiService->labelForLevel($opponentLevel),
            ], 422);
        }

        $draw = $this->findExistingDraw($request, $request->string('draw_id')->toString());

        if (! $draw) {
            return redirect()
                ->route('play.letters.show', $ageGroup)
                ->withErrors([
                    'submitted_word' => 'Cette partie n’est plus disponible. Relance un nouveau tirage.',
                ]);
        }

        $submittedWord = $this->wordValidationService->normalize(
            $request->string('submitted_word')->toString(),
        );

        if ($submittedWord === '') {
            return response()->view('play.letters', [
                'ageGroup' => $ageGroup,
                'drawId' => $draw['draw_id'],
                'letters' => $draw['letters'],
                'lettersCount' => count($draw['letters']),
                'timerSeconds' => $ageGroup->letters_timer_seconds,
                'submittedWord' => '',
                'errorMessage' => 'Le mot doit contenir uniquement des lettres.',
                'opponentLevel' => $opponentLevel ?? ($draw['opponent_level'] ?? null),
                'opponentLevelLabel' => $this->opponentAiService->labelForLevel($opponentLevel ?? ($draw['opponent_level'] ?? null)),
            ], 422);
        }

        if (! $this->wordUsesAvailableLetters($submittedWord, $draw['letters'])) {
            return response()->view('play.letters', [
                'ageGroup' => $ageGroup,
                'drawId' => $draw['draw_id'],
                'letters' => $draw['letters'],
                'lettersCount' => count($draw['letters']),
                'timerSeconds' => $ageGroup->letters_timer_seconds,
                'submittedWord' => $submittedWord,
                'errorMessage' => 'Le mot proposé utilise des lettres qui ne sont pas disponibles dans le tirage.',
                'opponentLevel' => $opponentLevel ?? ($draw['opponent_level'] ?? null),
                'opponentLevelLabel' => $this->opponentAiService->labelForLevel($opponentLevel ?? ($draw['opponent_level'] ?? null)),
            ], 422);
        }

        $validation = $this->wordValidationService->validateForAgeGroup(
            $submittedWord,
            $ageGroup,
        );

        if (! $validation['valid']) {
            return response()->view('play.letters', [
                'ageGroup' => $ageGroup,
                'drawId' => $draw['draw_id'],
                'letters' => $draw['letters'],
                'lettersCount' => count($draw['letters']),
                'timerSeconds' => $ageGroup->letters_timer_seconds,
                'submittedWord' => $submittedWord,
                'errorMessage' => $validation['message'],
                'opponentLevel' => $opponentLevel ?? ($draw['opponent_level'] ?? null),
                'opponentLevelLabel' => $this->opponentAiService->labelForLevel($opponentLevel ?? ($draw['opponent_level'] ?? null)),
            ], 422);
        }

        $score = strlen($submittedWord) * 10;
        $completedAt = now();
        $opponentLevel = $opponentLevel ?? ($draw['opponent_level'] ?? null);
        $opponentResult = $opponentLevel !== null
            ? $this->opponentAiService->playLetters($draw['letters'], $ageGroup, $opponentLevel)
            : null;

        [$gameSession, $letterRound] = DB::transaction(function () use ($request, $ageGroup, $draw, $submittedWord, $score, $completedAt) {
            $gameSession = GameSession::query()->create([
                'user_id' => $request->user()->id,
                'age_group_id' => $ageGroup->id,
                'game_type' => 'letters',
                'score' => 0,
                'status' => 'started',
                'started_at' => $draw['started_at'],
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

        $request->session()->forget(self::SESSION_KEY.'.'.$draw['draw_id']);

        return view('play.letters-result', [
            'ageGroup' => $ageGroup,
            'gameSession' => $gameSession,
            'letterRound' => $letterRound,
            'letters' => str_split($letterRound->letters),
            'submittedWord' => $submittedWord,
            'score' => $score,
            'opponentResult' => $opponentResult,
            'duelOutcome' => $this->duelOutcome($score, $opponentResult['score'] ?? null),
            'opponentLevel' => $opponentLevel,
            'opponentLevelLabel' => $this->opponentAiService->labelForLevel($opponentLevel),
        ]);
    }

    /**
     * Render the letters game page.
     */
    private function renderGameView(
        AgeGroup $ageGroup,
        array $draw,
        ?MessageBag $errors = null,
        string $submittedWord = '',
        ?string $opponentLevel = null,
    ): View {
        $normalizedOpponentLevel = $opponentLevel ?? ($draw['opponent_level'] ?? null);

        return view('play.letters', [
            'ageGroup' => $ageGroup,
            'drawId' => $draw['draw_id'],
            'letters' => $draw['letters'],
            'lettersCount' => count($draw['letters']),
            'timerSeconds' => $ageGroup->letters_timer_seconds,
            'submittedWord' => $submittedWord,
            'errorMessage' => $errors?->first('submitted_word'),
            'opponentLevel' => $normalizedOpponentLevel,
            'opponentLevelLabel' => $this->opponentAiService->labelForLevel($normalizedOpponentLevel),
        ]);
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
