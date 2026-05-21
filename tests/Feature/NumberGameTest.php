<?php

namespace Tests\Feature;

use App\Models\AgeGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NumberGameTest extends TestCase
{
    use RefreshDatabase;

    public function test_numbers_game_page_displays_numbers_and_target_for_selected_age_group(): void
    {
        $user = User::factory()->create();
        $ageGroup = AgeGroup::query()->create([
            'name' => '7-9 ans',
            'min_age' => 7,
            'max_age' => 9,
            'description' => 'Mode découverte',
            'letters_timer_seconds' => 90,
            'numbers_timer_seconds' => 120,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('play.numbers.show', $ageGroup));

        $response
            ->assertOk()
            ->assertSee('Mode Chiffres')
            ->assertSee('120');

        $draws = session('chronomots.numbers.draws', []);

        $this->assertCount(1, $draws);
        $this->assertCount(4, array_values($draws)[0]['numbers']);
    }

    public function test_numbers_game_submission_creates_a_completed_session_and_round(): void
    {
        $user = User::factory()->create();
        $ageGroup = AgeGroup::query()->create([
            'name' => '14+',
            'min_age' => 14,
            'max_age' => null,
            'description' => 'Mode expert',
            'letters_timer_seconds' => 45,
            'numbers_timer_seconds' => 60,
        ]);

        $drawId = 'draw-test-numbers';

        $response = $this
            ->withSession([
                'chronomots' => [
                    'numbers' => [
                        'draws' => [
                            $drawId => [
                                'draw_id' => $drawId,
                                'numbers' => [100, 25, 5, 4, 3, 2],
                                'target_number' => 130,
                                'started_at' => now()->subSeconds(15)->toDateTimeString(),
                            ],
                        ],
                    ],
                ],
            ])
            ->actingAs($user)
            ->post(route('play.numbers.submit', $ageGroup), [
                'draw_id' => $drawId,
                'submitted_solution' => '100 + 25 + 5',
                'score' => 9999,
            ]);

        $response
            ->assertOk()
            ->assertSee('130')
            ->assertSee('100 pts');

        $this->assertDatabaseHas('game_sessions', [
            'user_id' => $user->id,
            'age_group_id' => $ageGroup->id,
            'game_type' => 'numbers',
            'status' => 'completed',
            'score' => 100,
        ]);

        $this->assertDatabaseHas('number_rounds', [
            'target_number' => 130,
            'submitted_solution' => '100 + 25 + 5',
            'result_value' => 130,
            'score' => 100,
        ]);
    }

    public function test_numbers_game_rejects_expired_submissions_server_side(): void
    {
        $user = User::factory()->create();
        $ageGroup = AgeGroup::query()->create([
            'name' => '14+',
            'min_age' => 14,
            'max_age' => null,
            'description' => 'Mode expert',
            'letters_timer_seconds' => 45,
            'numbers_timer_seconds' => 60,
        ]);

        $drawId = 'draw-test-expired-numbers';

        $response = $this
            ->withSession([
                'chronomots' => [
                    'numbers' => [
                        'draws' => [
                            $drawId => [
                                'draw_id' => $drawId,
                                'numbers' => [100, 25, 5, 4, 3, 2],
                                'target_number' => 130,
                                'started_at' => now()->subSeconds(90)->toIso8601String(),
                                'expires_at' => now()->subSecond()->toIso8601String(),
                                'game_type' => 'numbers',
                                'age_group_id' => $ageGroup->id,
                            ],
                        ],
                    ],
                ],
            ])
            ->actingAs($user)
            ->post(route('play.numbers.submit', $ageGroup), [
                'draw_id' => $drawId,
                'submitted_solution' => '100 + 25 + 5',
            ]);

        $response
            ->assertStatus(422)
            ->assertSee('Temps dépassé pour ce tirage chiffres');

        $this->assertDatabaseCount('game_sessions', 0);
        $this->assertDatabaseCount('number_rounds', 0);
    }

    public function test_numbers_game_rejects_unavailable_numbers_in_solution(): void
    {
        $user = User::factory()->create();
        $ageGroup = AgeGroup::query()->create([
            'name' => '10-13 ans',
            'min_age' => 10,
            'max_age' => 13,
            'description' => 'Mode entraînement',
            'letters_timer_seconds' => 60,
            'numbers_timer_seconds' => 90,
        ]);

        $drawId = 'draw-test-invalid-numbers';

        $response = $this
            ->withSession([
                'chronomots' => [
                    'numbers' => [
                        'draws' => [
                            $drawId => [
                                'draw_id' => $drawId,
                                'numbers' => [10, 8, 5, 4, 2],
                                'target_number' => 100,
                                'started_at' => now()->toDateTimeString(),
                            ],
                        ],
                    ],
                ],
            ])
            ->actingAs($user)
            ->post(route('play.numbers.submit', $ageGroup), [
                'draw_id' => $drawId,
                'submitted_solution' => '50 + 10',
            ]);

        $response
            ->assertStatus(422)
            ->assertSee('Le calcul utilise des nombres qui ne sont pas disponibles dans le tirage.');

        $this->assertDatabaseCount('game_sessions', 0);
        $this->assertDatabaseCount('number_rounds', 0);
    }

    public function test_numbers_game_can_display_vs_ai_results(): void
    {
        $user = User::factory()->create();
        $ageGroup = AgeGroup::query()->create([
            'name' => '14+',
            'min_age' => 14,
            'max_age' => null,
            'description' => 'Mode expert',
            'letters_timer_seconds' => 45,
            'numbers_timer_seconds' => 60,
        ]);

        $drawId = 'draw-test-ai-numbers';

        $response = $this
            ->withSession([
                'chronomots' => [
                    'numbers' => [
                        'draws' => [
                            $drawId => [
                                'draw_id' => $drawId,
                                'numbers' => [100, 25, 5, 4, 3, 2],
                                'target_number' => 130,
                                'started_at' => now()->subSeconds(15)->toDateTimeString(),
                                'opponent_level' => 'expert',
                            ],
                        ],
                    ],
                ],
            ])
            ->actingAs($user)
            ->post(route('play.numbers.submit', $ageGroup), [
                'draw_id' => $drawId,
                'submitted_solution' => '100 + 25 + 5',
                'opponent_level' => 'expert',
            ]);

        $response
            ->assertOk()
            ->assertSee('VS IA')
            ->assertSee('IA Expert')
            ->assertSee('Comparaison joueur vs IA');
    }

    public function test_numbers_game_submit_route_is_rate_limited(): void
    {
        $user = User::factory()->create();
        $ageGroup = AgeGroup::query()->create([
            'name' => '14+',
            'min_age' => 14,
            'max_age' => null,
            'description' => 'Mode expert',
            'letters_timer_seconds' => 45,
            'numbers_timer_seconds' => 60,
        ]);

        $drawId = 'draw-test-rate-limit-numbers';
        $sessionPayload = [
            'chronomots' => [
                'numbers' => [
                    'draws' => [
                        $drawId => [
                            'draw_id' => $drawId,
                            'numbers' => [100, 25, 5, 4, 3, 2],
                            'target_number' => 130,
                            'started_at' => now()->toIso8601String(),
                            'expires_at' => now()->addMinute()->toIso8601String(),
                            'game_type' => 'numbers',
                            'age_group_id' => $ageGroup->id,
                        ],
                    ],
                ],
            ],
        ];

        for ($attempt = 0; $attempt < 8; $attempt++) {
            $this->withSession($sessionPayload)
                ->actingAs($user)
                ->post(route('play.numbers.submit', $ageGroup), [
                    'draw_id' => $drawId,
                    'submitted_solution' => '999 + 1',
                ])
                ->assertStatus(422);
        }

        $this->withSession($sessionPayload)
            ->actingAs($user)
            ->post(route('play.numbers.submit', $ageGroup), [
                'draw_id' => $drawId,
                'submitted_solution' => '999 + 1',
            ])
            ->assertStatus(429)
            ->assertSee('Trop de tentatives sur le mode chiffres');
    }
}
