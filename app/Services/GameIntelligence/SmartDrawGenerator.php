<?php

namespace App\Services\GameIntelligence;

use App\Services\LetterBagService;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SmartDrawGenerator
{
    private const MAX_ATTEMPTS = 50;

    public function __construct(
        private readonly LetterBagService $letterBagService,
        private readonly DrawBalanceAnalyzer $drawBalanceAnalyzer,
    ) {
    }

    /**
     * Generate, validate and return only a balanced draw.
     *
     * @return array{
     *     letters: array<int, string>,
     *     quality_score: int,
     *     analysis: array{
     *         vowel_count: int,
     *         consonant_count: int,
     *         rare_letters_count: int,
     *         balance_score: int,
     *         problems: array<int, string>
     *     },
     *     attempt: int
     * }
     */
    public function generate(int $count = 10): array
    {
        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            $this->letterBagService->resetBag();

            $letters = $this->letterBagService->generateDraw($count);
            $analysis = $this->drawBalanceAnalyzer->analyze($letters);

            if ($this->drawBalanceAnalyzer->isBalanced($letters)) {
                Log::info('Smart draw accepted.', [
                    'attempt' => $attempt,
                    'count' => $count,
                    'letters' => $letters,
                    'quality_score' => $analysis['balance_score'],
                    'analysis' => $analysis,
                ]);

                return [
                    'letters' => $letters,
                    'quality_score' => $analysis['balance_score'],
                    'analysis' => $analysis,
                    'attempt' => $attempt,
                ];
            }

            Log::debug('Smart draw rejected as unbalanced.', [
                'attempt' => $attempt,
                'count' => $count,
                'letters' => $letters,
                'quality_score' => $analysis['balance_score'],
                'problems' => $analysis['problems'],
                'analysis' => $analysis,
            ]);
        }

        Log::warning('Smart draw generation exhausted all attempts without finding a balanced draw.', [
            'max_attempts' => self::MAX_ATTEMPTS,
            'count' => $count,
        ]);

        throw new RuntimeException(
            sprintf('Unable to generate a balanced letters draw after %d attempts.', self::MAX_ATTEMPTS),
        );
    }
}
