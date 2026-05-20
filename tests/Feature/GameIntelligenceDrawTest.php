<?php

namespace Tests\Feature;

use App\Models\AgeGroup;
use App\Services\GameIntelligence\AgeDifficultyProfileService;
use App\Services\GameIntelligence\GameIntelligenceManager;
use App\Services\GameIntelligence\LettersSolvabilityService;
use App\Services\GameIntelligence\NumbersSolvabilityService;
use Database\Seeders\AgeGroupSeeder;
use Database\Seeders\WordSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameIntelligenceDrawTest extends TestCase
{
    use RefreshDatabase;

    public function test_letters_draws_are_playable_for_every_age_group(): void
    {
        $this->seed([AgeGroupSeeder::class, WordSeeder::class]);

        $manager = app(GameIntelligenceManager::class);
        $profiles = app(AgeDifficultyProfileService::class);
        $solvability = app(LettersSolvabilityService::class);

        AgeGroup::query()->orderBy('min_age')->each(function (AgeGroup $ageGroup) use ($manager, $profiles, $solvability): void {
            $draw = $manager->createLettersDraw($ageGroup);
            $profile = $profiles->forLetters($ageGroup);
            $report = $solvability->analyze($draw['letters'], $ageGroup, $profile);

            $this->assertTrue($report->valid, 'Le tirage lettres doit être solvable pour '.$ageGroup->name);
            $this->assertGreaterThanOrEqual($profile->minSolutions, $report->solutionsCount);
            $this->assertGreaterThanOrEqual($profile->minBestLength, (int) ($report->metadata['best_length'] ?? 0));
            $this->assertGreaterThanOrEqual(1, (int) ($report->metadata['target_word_count'] ?? 0));
        });
    }

    public function test_numbers_draws_always_choose_a_reachable_target(): void
    {
        $this->seed([AgeGroupSeeder::class]);

        $manager = app(GameIntelligenceManager::class);
        $profiles = app(AgeDifficultyProfileService::class);
        $solvability = app(NumbersSolvabilityService::class);

        AgeGroup::query()->orderBy('min_age')->each(function (AgeGroup $ageGroup) use ($manager, $profiles, $solvability): void {
            $draw = $manager->createNumbersDraw($ageGroup);
            $profile = $profiles->forNumbers($ageGroup);
            $report = $solvability->analyze($draw['numbers'], $profile);

            $this->assertTrue($report->valid, 'Le tirage chiffres doit proposer une cible atteignable pour '.$ageGroup->name);
            $this->assertSame($draw['target_number'], $report->bestValue);
            $this->assertGreaterThanOrEqual($profile->targetMin, $draw['target_number']);
            $this->assertLessThanOrEqual($profile->targetMax, $draw['target_number']);
        });
    }
}
