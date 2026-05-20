<?php

namespace Tests\Feature;

use App\Models\AgeGroup;
use App\Models\GameSession;
use App\Models\LetterRound;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LetterGameTest extends TestCase
{
    use RefreshDatabase;

    public function test_letters_game_page_displays_a_draw_for_the_selected_age_group(): void
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
            ->get(route('play.letters.show', $ageGroup));

        $response
            ->assertOk()
            ->assertSee('Mode Lettres')
            ->assertSee('90');

        $draws = session('chronomots.letters.draws', []);

        $this->assertCount(1, $draws);
        $this->assertCount(7, array_values($draws)[0]['letters']);
    }

    public function test_letters_game_submission_creates_a_completed_session_and_round(): void
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

        $this->actingAs($user)->get(route('play.letters.show', $ageGroup));

        $drawId = array_key_first(session('chronomots.letters.draws', []));

        $response = $this
            ->withSession([
                'chronomots' => [
                    'letters' => [
                        'draws' => [
                            $drawId => [
                                'draw_id' => $drawId,
                                'letters' => ['M', 'A', 'R', 'O', 'T', 'E', 'S', 'L'],
                                'started_at' => now()->subSeconds(20)->toDateTimeString(),
                            ],
                        ],
                    ],
                ],
            ])
            ->actingAs($user)
            ->post(route('play.letters.submit', $ageGroup), [
                'draw_id' => $drawId,
                'submitted_word' => 'mots',
            ]);

        $response
            ->assertOk()
            ->assertSee('40 pts')
            ->assertSee('MOTS');

        $this->assertDatabaseHas('game_sessions', [
            'user_id' => $user->id,
            'age_group_id' => $ageGroup->id,
            'game_type' => 'letters',
            'status' => 'completed',
            'score' => 40,
        ]);

        $this->assertDatabaseHas('letter_rounds', [
            'submitted_word' => 'MOTS',
            'score' => 40,
        ]);
    }

    public function test_letters_game_rejects_words_using_unavailable_letters(): void
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

        $drawId = 'draw-test-letters';

        $response = $this
            ->withSession([
                'chronomots' => [
                    'letters' => [
                        'draws' => [
                            $drawId => [
                                'draw_id' => $drawId,
                                'letters' => ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'],
                                'started_at' => now()->toDateTimeString(),
                            ],
                        ],
                    ],
                ],
            ])
            ->actingAs($user)
            ->post(route('play.letters.submit', $ageGroup), [
                'draw_id' => $drawId,
                'submitted_word' => 'KILO',
            ]);

        $response
            ->assertStatus(422)
            ->assertSee('Le mot proposé utilise des lettres qui ne sont pas disponibles dans le tirage.');

        $this->assertDatabaseCount('game_sessions', 0);
        $this->assertDatabaseCount('letter_rounds', 0);
    }
}
