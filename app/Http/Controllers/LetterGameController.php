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

class LetterGameController extends Controller
{
    private const SESSION_KEY = 'chronomots.letters.draws';

    /**
     * Display a new letters game for the selected age group.
     */
    public function show(Request $request, AgeGroup $ageGroup): View
    {
        $draw = $this->createDrawPayload($ageGroup);

        $request->session()->put(self::SESSION_KEY.'.'.$draw['draw_id'], $draw);

        return $this->renderGameView($ageGroup, $draw);
    }

    /**
     * Submit a word for the current letters game.
     */
    public function submit(Request $request, AgeGroup $ageGroup): View|Response|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'draw_id' => ['required', 'string'],
            'submitted_word' => ['required', 'string', 'alpha:ascii', 'max:32'],
        ], [
            'submitted_word.required' => 'Propose un mot avant de valider.',
            'submitted_word.alpha' => 'Le mot doit contenir uniquement des lettres.',
        ]);

        if ($validator->fails()) {
            $draw = $this->findExistingDraw($request, $request->string('draw_id')->toString()) ?? $this->createDrawPayload($ageGroup);

            return response()->view('play.letters', [
                'ageGroup' => $ageGroup,
                'drawId' => $draw['draw_id'],
                'letters' => $draw['letters'],
                'lettersCount' => count($draw['letters']),
                'timerSeconds' => $ageGroup->letters_timer_seconds,
                'submittedWord' => $request->string('submitted_word')->toString(),
                'errorMessage' => $validator->errors()->first('submitted_word'),
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

        $submittedWord = $this->normalizeWord($request->string('submitted_word')->toString());

        if (! $this->wordUsesAvailableLetters($submittedWord, $draw['letters'])) {
            return response()->view('play.letters', [
                'ageGroup' => $ageGroup,
                'drawId' => $draw['draw_id'],
                'letters' => $draw['letters'],
                'lettersCount' => count($draw['letters']),
                'timerSeconds' => $ageGroup->letters_timer_seconds,
                'submittedWord' => $submittedWord,
                'errorMessage' => 'Le mot proposé utilise des lettres qui ne sont pas disponibles dans le tirage.',
            ], 422);
        }

        $score = strlen($submittedWord) * 10;
        $completedAt = now();

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
    ): View {
        return view('play.letters', [
            'ageGroup' => $ageGroup,
            'drawId' => $draw['draw_id'],
            'letters' => $draw['letters'],
            'lettersCount' => count($draw['letters']),
            'timerSeconds' => $ageGroup->letters_timer_seconds,
            'submittedWord' => $submittedWord,
            'errorMessage' => $errors?->first('submitted_word'),
        ]);
    }

    /**
     * Create the draw payload stored in session for a single play.
     */
    private function createDrawPayload(AgeGroup $ageGroup): array
    {
        $lettersCount = $this->lettersCountForAgeGroup($ageGroup);
        $vowelsCount = $this->vowelsCountForLettersGame($lettersCount);
        $consonantsCount = $lettersCount - $vowelsCount;

        $vowels = ['A', 'E', 'I', 'O', 'U', 'Y'];
        $consonants = ['B', 'C', 'D', 'F', 'G', 'H', 'J', 'K', 'L', 'M', 'N', 'P', 'Q', 'R', 'S', 'T', 'V', 'W', 'X', 'Z'];

        $letters = array_merge(
            $this->pickLetters($vowels, $vowelsCount),
            $this->pickLetters($consonants, $consonantsCount),
        );

        shuffle($letters);

        return [
            'draw_id' => Str::uuid()->toString(),
            'letters' => $letters,
            'started_at' => now()->toDateTimeString(),
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
     * Determine the number of letters for an age group.
     */
    private function lettersCountForAgeGroup(AgeGroup $ageGroup): int
    {
        if ($ageGroup->min_age >= 14) {
            return 10;
        }

        if ($ageGroup->min_age >= 10) {
            return 8;
        }

        return 7;
    }

    /**
     * Determine the number of vowels to keep the draw balanced.
     */
    private function vowelsCountForLettersGame(int $lettersCount): int
    {
        return match ($lettersCount) {
            10 => 4,
            8 => 3,
            default => 3,
        };
    }

    /**
     * Pick letters with repetition allowed.
     *
     * @param  array<int, string>  $source
     * @return array<int, string>
     */
    private function pickLetters(array $source, int $count): array
    {
        $letters = [];

        for ($index = 0; $index < $count; $index++) {
            $letters[] = $source[random_int(0, count($source) - 1)];
        }

        return $letters;
    }

    /**
     * Normalize a submitted word for validation and storage.
     */
    private function normalizeWord(string $submittedWord): string
    {
        return Str::upper(trim($submittedWord));
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
}
