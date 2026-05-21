<?php

namespace App\Http\Controllers;

use App\Models\AgeGroup;
use App\Services\DailyChallengeService;
use App\Services\GameIntelligence\OpponentAiService;
use Illuminate\View\View;

class PlayController extends Controller
{
    public function __construct(
        private readonly DailyChallengeService $dailyChallengeService,
        private readonly OpponentAiService $opponentAiService,
    ) {
    }

    /**
     * Display the available solo play modes.
     */
    public function index(): View
    {
        $ageGroups = AgeGroup::query()
            ->orderBy('min_age')
            ->get();

        return view('play', [
            'ageGroups' => $ageGroups,
            'aiLevels' => $this->opponentAiService->levels(),
            'dailyChallenges' => auth()->check()
                ? $this->dailyChallengeService->todayChallenges()
                : collect(),
        ]);
    }
}
