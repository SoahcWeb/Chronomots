<?php

namespace App\Services\GameIntelligence;

use App\Models\AgeGroup;
use App\Services\GameplaySecurityService;
use Illuminate\Support\Str;

class GameIntelligenceManager
{
    public function __construct(
        private readonly AgeDifficultyProfileService $ageDifficultyProfileService,
        private readonly BalancedDrawService $balancedDrawService,
        private readonly LettersDrawGenerator $lettersDrawGenerator,
        private readonly GameplaySecurityService $gameplaySecurityService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function startLettersDraw(AgeGroup $ageGroup): array
    {
        $difficultyProfile = $this->ageDifficultyProfileService->forLetters($ageGroup);

        return $this->lettersDrawGenerator->startInteractiveDraw($ageGroup, $difficultyProfile);
    }

    /**
     * @param  array<string, mixed>  $draw
     * @return array<string, mixed>
     */
    public function revealLettersChoice(AgeGroup $ageGroup, array $draw, string $choiceType): array
    {
        $difficultyProfile = $this->ageDifficultyProfileService->forLetters($ageGroup);

        return $this->lettersDrawGenerator->revealNextLetter($draw, $choiceType, $difficultyProfile);
    }

    /**
     * Build the letters payload expected by the current controllers/session flow.
     *
     * @return array{draw_id: string, letters: array<int, string>, started_at: string, expires_at: string, timer_seconds: int, age_group_id: int, game_type: string, choice_history: array<int, string>, letters_target: int}
     */
    public function createLettersDraw(AgeGroup $ageGroup): array
    {
        $candidate = $this->balancedDrawService->generateLetters($ageGroup);
        $window = $this->gameplaySecurityService->createTimedWindow($ageGroup->letters_timer_seconds);

        return [
            'draw_id' => Str::uuid()->toString(),
            'letters' => $candidate->payload['letters'],
            'started_at' => $window['started_at'],
            'expires_at' => $window['expires_at'],
            'timer_seconds' => $window['timer_seconds'],
            'age_group_id' => $ageGroup->id,
            'game_type' => 'letters',
            'choice_history' => $candidate->payload['choice_history'] ?? [],
            'letters_target' => $candidate->difficultyProfile->lettersCount,
        ];
    }

    /**
     * Build the numbers payload expected by the current controllers/session flow.
     *
     * @return array{draw_id: string, numbers: array<int, int>, target_number: int, started_at: string, expires_at: string, timer_seconds: int, age_group_id: int, game_type: string}
     */
    public function createNumbersDraw(AgeGroup $ageGroup): array
    {
        $candidate = $this->balancedDrawService->generateNumbers($ageGroup);
        $window = $this->gameplaySecurityService->createTimedWindow($ageGroup->numbers_timer_seconds);

        return [
            'draw_id' => Str::uuid()->toString(),
            'numbers' => $candidate->payload['numbers'],
            'target_number' => $candidate->payload['target_number'],
            'started_at' => $window['started_at'],
            'expires_at' => $window['expires_at'],
            'timer_seconds' => $window['timer_seconds'],
            'age_group_id' => $ageGroup->id,
            'game_type' => 'numbers',
        ];
    }
}
