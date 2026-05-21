<?php

namespace Tests\Feature;

use App\Models\AgeGroup;
use App\Models\GameSession;
use App\Models\PlayerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaderboardsTest extends TestCase
{
    use RefreshDatabase;

    public function test_leaderboards_display_players_with_their_avatars(): void
    {
        $ageGroup = AgeGroup::query()->create([
            'name' => '10-13 ans',
            'min_age' => 10,
            'max_age' => 13,
            'description' => 'Mode entraînement',
            'letters_timer_seconds' => 60,
            'numbers_timer_seconds' => 90,
        ]);

        $playerOne = User::factory()->create(['name' => 'Alice']);
        $playerTwo = User::factory()->create(['name' => 'Bastien']);

        PlayerProfile::query()->create([
            'user_id' => $playerOne->id,
            'avatar_type' => 'preset',
            'avatar_slug' => 'nova',
        ]);

        PlayerProfile::query()->create([
            'user_id' => $playerTwo->id,
            'avatar_type' => 'preset',
            'avatar_slug' => 'tempo',
        ]);

        GameSession::query()->create([
            'user_id' => $playerOne->id,
            'age_group_id' => $ageGroup->id,
            'game_type' => 'letters',
            'score' => 90,
            'status' => 'completed',
            'started_at' => now()->subMinutes(12),
            'completed_at' => now()->subMinutes(11),
        ]);

        GameSession::query()->create([
            'user_id' => $playerTwo->id,
            'age_group_id' => $ageGroup->id,
            'game_type' => 'numbers',
            'score' => 75,
            'status' => 'completed',
            'started_at' => now()->subMinutes(8),
            'completed_at' => now()->subMinutes(7),
        ]);

        $response = $this->get(route('leaderboards'));

        $response
            ->assertOk()
            ->assertSee('Classement général')
            ->assertSee('Alice')
            ->assertSee('Bastien')
            ->assertSee('Nova')
            ->assertSee('Tempo');
    }
}
