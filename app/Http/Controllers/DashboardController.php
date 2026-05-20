<?php

namespace App\Http\Controllers;

use App\Models\AgeGroup;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the player's dashboard.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $completedSessions = $user->gameSessions()
            ->with('ageGroup')
            ->where('status', 'completed')
            ->orderByDesc('completed_at')
            ->orderByDesc('updated_at')
            ->get();

        $totalGames = $completedSessions->count();
        $bestLettersScore = $completedSessions->where('game_type', 'letters')->max('score') ?? 0;
        $bestNumbersScore = $completedSessions->where('game_type', 'numbers')->max('score') ?? 0;
        $averageScore = $totalGames > 0 ? (int) round((float) $completedSessions->avg('score')) : 0;

        $ageGroups = AgeGroup::query()
            ->orderBy('min_age')
            ->get();

        $recentSessions = $completedSessions->take(5)->values();
        $lettersGamesCount = $completedSessions->where('game_type', 'letters')->count();
        $numbersGamesCount = $completedSessions->where('game_type', 'numbers')->count();
        $favoriteMode = match (true) {
            $lettersGamesCount > $numbersGamesCount => 'Lettres',
            $numbersGamesCount > $lettersGamesCount => 'Chiffres',
            $totalGames > 0 => 'Équilibré',
            default => 'À découvrir',
        };

        $activeCategories = $ageGroups
            ->filter(fn (AgeGroup $ageGroup) => $completedSessions->where('age_group_id', $ageGroup->id)->isNotEmpty())
            ->count();

        $preferredAgeGroup = $recentSessions->first()?->ageGroup ?? $ageGroups->first();

        $progression = $ageGroups->map(function (AgeGroup $ageGroup) use ($completedSessions) {
            $sessions = $completedSessions->where('age_group_id', $ageGroup->id)->values();
            $gamesCount = $sessions->count();

            return [
                'age_group' => $ageGroup,
                'games_count' => $gamesCount,
                'best_score' => $sessions->max('score') ?? 0,
                'average_score' => $gamesCount > 0 ? (int) round((float) $sessions->avg('score')) : 0,
                'letters_games' => $sessions->where('game_type', 'letters')->count(),
                'numbers_games' => $sessions->where('game_type', 'numbers')->count(),
                'completion_percent' => min(100, $gamesCount * 20),
                'has_progress' => $gamesCount > 0,
            ];
        });

        return view('dashboard', [
            'totalGames' => $totalGames,
            'bestLettersScore' => $bestLettersScore,
            'bestNumbersScore' => $bestNumbersScore,
            'averageScore' => $averageScore,
            'recentSessions' => $recentSessions,
            'progression' => $progression,
            'lettersGamesCount' => $lettersGamesCount,
            'numbersGamesCount' => $numbersGamesCount,
            'favoriteMode' => $favoriteMode,
            'activeCategories' => $activeCategories,
            'preferredAgeGroup' => $preferredAgeGroup,
            'hasGames' => $totalGames > 0,
        ]);
    }
}
