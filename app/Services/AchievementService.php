<?php

namespace App\Services;

use App\Models\Achievement;
use App\Models\GameSession;
use App\Models\User;
use App\Models\UserAchievement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AchievementService
{
    public const FIRST_VALID_WORD = 'first_valid_word';
    public const FIRST_PERFECT_SCORE = 'first_perfect_score';
    public const TEN_GAMES_PLAYED = 'ten_games_played';
    public const VICTORY_VS_AI_EASY = 'victory_vs_ai_easy';
    public const VICTORY_VS_AI_MEDIUM = 'victory_vs_ai_medium';
    public const VICTORY_VS_AI_HARD = 'victory_vs_ai_hard';
    public const VICTORY_VS_AI_EXPERT = 'victory_vs_ai_expert';
    public const EIGHT_LETTER_WORD = 'eight_letter_word';
    public const TOTAL_SCORE_MILESTONE = 'total_score_milestone';

    /**
     * Evaluate and unlock achievements after one completed game.
     *
     * @param  array<string, mixed>  $context
     * @return Collection<int, Achievement>
     */
    public function unlockForCompletedGame(User $user, GameSession $gameSession, array $context = []): Collection
    {
        $achievements = Achievement::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->keyBy('code');

        if ($achievements->isEmpty()) {
            return collect();
        }

        $userAchievements = $user->userAchievements()
            ->get()
            ->keyBy('achievement_id');

        $stats = $this->userStats($user);
        $now = now();
        $newlyUnlocked = collect();

        foreach ($achievements as $code => $achievement) {
            /** @var Achievement $achievement */
            $progressValue = $this->progressForAchievement($code, $achievement->unlock_value, $gameSession, $stats, $context);
            $existing = $userAchievements->get($achievement->id);
            $shouldUnlock = $progressValue >= $achievement->unlock_value;

            if ($existing) {
                $existing->progress_value = max($existing->progress_value, $progressValue);

                if ($existing->unlocked_at === null && $shouldUnlock) {
                    $existing->unlocked_at = $now;
                    $newlyUnlocked->push($achievement);
                }

                $existing->save();
                continue;
            }

            $userAchievement = UserAchievement::query()->create([
                'user_id' => $user->id,
                'achievement_id' => $achievement->id,
                'progress_value' => $progressValue,
                'unlocked_at' => $shouldUnlock ? $now : null,
            ]);

            if ($userAchievement->unlocked_at !== null) {
                $newlyUnlocked->push($achievement);
            }
        }

        return $newlyUnlocked->values();
    }

    /**
     * @return array{completed_games: int, total_score: int, valid_words: int}
     */
    private function userStats(User $user): array
    {
        $completedGames = (int) $user->gameSessions()
            ->where('status', 'completed')
            ->count();

        $totalScore = (int) $user->gameSessions()
            ->where('status', 'completed')
            ->sum('score');

        $validWords = (int) DB::table('letter_rounds')
            ->join('game_sessions', 'game_sessions.id', '=', 'letter_rounds.game_session_id')
            ->where('game_sessions.user_id', $user->id)
            ->where('game_sessions.status', 'completed')
            ->whereNotNull('letter_rounds.submitted_word')
            ->count();

        return [
            'completed_games' => $completedGames,
            'total_score' => $totalScore,
            'valid_words' => $validWords,
        ];
    }

    /**
     * @param  array{completed_games: int, total_score: int, valid_words: int}  $stats
     * @param  array<string, mixed>  $context
     */
    private function progressForAchievement(
        string $code,
        int $unlockValue,
        GameSession $gameSession,
        array $stats,
        array $context,
    ): int {
        return match ($code) {
            self::FIRST_VALID_WORD => $stats['valid_words'],
            self::FIRST_PERFECT_SCORE => $gameSession->score >= 100 ? 1 : 0,
            self::TEN_GAMES_PLAYED => $stats['completed_games'],
            self::VICTORY_VS_AI_EASY => $this->aiVictoryProgress($context, 'facile'),
            self::VICTORY_VS_AI_MEDIUM => $this->aiVictoryProgress($context, 'moyen'),
            self::VICTORY_VS_AI_HARD => $this->aiVictoryProgress($context, 'difficile'),
            self::VICTORY_VS_AI_EXPERT => $this->aiVictoryProgress($context, 'expert'),
            self::EIGHT_LETTER_WORD => $this->longWordProgress($context, 8),
            self::TOTAL_SCORE_MILESTONE => $stats['total_score'],
            default => 0,
        };
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function aiVictoryProgress(array $context, string $expectedLevel): int
    {
        return (($context['opponent_level'] ?? null) === $expectedLevel
            && ($context['duel_outcome'] ?? null) === 'Victoire') ? 1 : 0;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function longWordProgress(array $context, int $minimumLength): int
    {
        $submittedWord = (string) ($context['submitted_word'] ?? '');

        return strlen($submittedWord) >= $minimumLength ? 1 : 0;
    }
}
