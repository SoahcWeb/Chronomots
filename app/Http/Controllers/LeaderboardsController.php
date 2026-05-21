<?php

namespace App\Http\Controllers;

use App\Models\AgeGroup;
use App\Models\User;
use App\Services\AvatarCatalogService;
use Illuminate\View\View;

class LeaderboardsController extends Controller
{
    public function __construct(
        private readonly AvatarCatalogService $avatarCatalogService,
    ) {
    }

    public function __invoke(): View
    {
        $leaders = User::query()
            ->with('playerProfile')
            ->whereHas('gameSessions', fn ($query) => $query->where('status', 'completed'))
            ->withMax([
                'gameSessions as best_score' => fn ($query) => $query->where('status', 'completed'),
            ], 'score')
            ->withAvg([
                'gameSessions as average_score' => fn ($query) => $query->where('status', 'completed'),
            ], 'score')
            ->withCount([
                'gameSessions as games_count' => fn ($query) => $query->where('status', 'completed'),
            ])
            ->orderByDesc('best_score')
            ->orderByDesc('average_score')
            ->limit(6)
            ->get()
            ->map(function (User $user): array {
                return [
                    'user' => $user,
                    'avatar' => $this->avatarCatalogService->avatarForUser($user),
                    'best_score' => (int) ($user->best_score ?? 0),
                    'average_score' => (int) round((float) ($user->average_score ?? 0)),
                    'games_count' => (int) ($user->games_count ?? 0),
                ];
            });

        $ageHighlights = AgeGroup::query()
            ->orderBy('min_age')
            ->get()
            ->map(function (AgeGroup $ageGroup): array {
                $bestScore = $ageGroup->gameSessions()
                    ->where('status', 'completed')
                    ->max('score') ?? 0;

                $gamesCount = $ageGroup->gameSessions()
                    ->where('status', 'completed')
                    ->count();

                return [
                    'age_group' => $ageGroup,
                    'best_score' => (int) $bestScore,
                    'games_count' => $gamesCount,
                ];
            });

        return view('leaderboards', [
            'leaders' => $leaders,
            'ageHighlights' => $ageHighlights,
        ]);
    }
}
