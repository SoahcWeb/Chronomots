<?php

namespace Tests\Feature;

use App\Models\Achievement;
use App\Models\AgeGroup;
use App\Models\GameSession;
use App\Models\PlayerProfile;
use App\Models\User;
use App\Models\UserAchievement;
use App\Services\AchievementService;
use Database\Seeders\AchievementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_displays_empty_state_when_player_has_no_games(): void
    {
        $user = User::factory()->create();

        AgeGroup::query()->create([
            'name' => '7-9 ans',
            'min_age' => 7,
            'max_age' => 9,
            'description' => 'Mode découverte',
            'letters_timer_seconds' => 90,
            'numbers_timer_seconds' => 120,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertSee('Aucune partie enregistrée pour le moment')
            ->assertSee('Parties jouées')
            ->assertSee('0');
    }

    public function test_dashboard_displays_player_statistics_and_recent_games(): void
    {
        $this->seed(AchievementSeeder::class);

        $user = User::factory()->create();

        PlayerProfile::query()->create([
            'user_id' => $user->id,
            'avatar_type' => 'preset',
            'avatar_slug' => 'prisme',
        ]);

        $junior = AgeGroup::query()->create([
            'name' => '7-9 ans',
            'min_age' => 7,
            'max_age' => 9,
            'description' => 'Mode découverte',
            'letters_timer_seconds' => 90,
            'numbers_timer_seconds' => 120,
        ]);

        $expert = AgeGroup::query()->create([
            'name' => '14+',
            'min_age' => 14,
            'max_age' => null,
            'description' => 'Mode expert',
            'letters_timer_seconds' => 45,
            'numbers_timer_seconds' => 60,
        ]);

        GameSession::query()->create([
            'user_id' => $user->id,
            'age_group_id' => $junior->id,
            'game_type' => 'letters',
            'score' => 40,
            'status' => 'completed',
            'started_at' => now()->subMinutes(10),
            'completed_at' => now()->subMinutes(9),
        ]);

        GameSession::query()->create([
            'user_id' => $user->id,
            'age_group_id' => $expert->id,
            'game_type' => 'numbers',
            'score' => 100,
            'status' => 'completed',
            'started_at' => now()->subMinutes(5),
            'completed_at' => now()->subMinutes(4),
        ]);

        $achievement = Achievement::query()
            ->where('code', AchievementService::FIRST_VALID_WORD)
            ->first();

        UserAchievement::query()->create([
            'user_id' => $user->id,
            'achievement_id' => $achievement->id,
            'progress_value' => 1,
            'unlocked_at' => now()->subMinute(),
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertSee('Historique récent')
            ->assertSee('7-9 ans')
            ->assertSee('14+')
            ->assertSee('Avatar actif : Prisme')
            ->assertSee('Lettres')
            ->assertSee('Chiffres')
            ->assertSee('Badges et progression')
            ->assertSee('Premier Mot')
            ->assertSee('Débloqué')
            ->assertSee('Score cumulé')
            ->assertSee('100')
            ->assertSee('70');
    }
}
