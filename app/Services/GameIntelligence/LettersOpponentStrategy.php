<?php

namespace App\Services\GameIntelligence;

use App\Models\AgeGroup;
use App\Models\Word;

class LettersOpponentStrategy
{
    public function __construct(
        private readonly LettersSolvabilityService $lettersSolvabilityService,
    ) {
    }

    /**
     * @param  array<int, string>  $letters
     * @return array{submitted_word: string, score: int, quality_label: string}
     */
    public function play(array $letters, AgeGroup $ageGroup, string $level): array
    {
        $candidates = $this->lettersSolvabilityService->findCandidateWords($letters, $ageGroup);

        if ($candidates->isEmpty()) {
            return [
                'submitted_word' => '',
                'score' => 0,
                'quality_label' => 'Aucun mot',
            ];
        }

        $selected = $this->pickWord($candidates->values()->all(), $level);
        $score = strlen($selected->normalized_word) * 10;

        return [
            'submitted_word' => $selected->normalized_word,
            'score' => $score,
            'quality_label' => $this->qualityLabel($level),
        ];
    }

    /**
     * @param  array<int, Word>  $candidates
     */
    private function pickWord(array $candidates, string $level): Word
    {
        $count = count($candidates);

        $index = match ($level) {
            'expert' => 0,
            'difficile' => min($count - 1, max(0, (int) floor(($count - 1) * 0.15))),
            'moyen' => min($count - 1, max(0, (int) floor(($count - 1) * 0.35))),
            default => min($count - 1, max(0, (int) floor(($count - 1) * 0.6))),
        };

        return $candidates[$index];
    }

    private function qualityLabel(string $level): string
    {
        return match ($level) {
            'expert' => 'Quasi optimal',
            'difficile' => 'Très solide',
            'moyen' => 'Bonne réponse',
            default => 'Imparfait mais valable',
        };
    }
}
