<?php

namespace App\Http\Controllers;

use App\Models\AgeGroup;
use Illuminate\View\View;

class PlayController extends Controller
{
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
        ]);
    }
}
