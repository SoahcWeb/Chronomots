<?php

namespace Tests\Feature;

use App\Models\Achievement;
use App\Models\AgeGroup;
use App\Models\GameSession;
use App\Models\LetterRound;
use App\Models\User;
use App\Models\Word;
use App\Services\AchievementService;
use App\Services\GameIntelligence\LetterPoolService;
use Database\Seeders\AchievementSeeder;
use Database\Seeders\AgeGroupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LetterGameTest extends TestCase
{
    use RefreshDatabase;

    public function test_letters_game_page_starts_an_interactive_draw_for_the_selected_age_group(): void
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
            ->assertSee('90')
            ->assertSee('Choisir une voyelle')
            ->assertSee('Choisir une consonne');

        $draws = session('chronomots.letters.draws', []);

        $this->assertCount(1, $draws);
        $this->assertCount(0, array_values($draws)[0]['letters']);
        $this->assertSame(7, array_values($draws)[0]['letters_target']);
    }

    public function test_letters_pages_load_for_age_group_ids_even_without_a_seed_dictionary_word(): void
    {
        $this->seed(AgeGroupSeeder::class);

        $user = User::factory()->create();
        $expectedLettersByAgeGroupId = [
            1 => 7,
            2 => 8,
            3 => 10,
        ];

        foreach ($expectedLettersByAgeGroupId as $ageGroupId => $expectedLettersCount) {
            $response = $this
                ->actingAs($user)
                ->get('/play/letters/'.$ageGroupId);

            $response
                ->assertOk()
                ->assertSee('Mode Lettres');

            $draws = session('chronomots.letters.draws', []);
            $latestDraw = end($draws);

            $this->assertIsArray($latestDraw);
            $this->assertSame($expectedLettersCount, $latestDraw['letters_target']);
            $this->assertCount(0, $latestDraw['letters']);
        }
    }

    public function test_letters_draw_choice_reveals_a_matching_letter_type(): void
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
            ->actingAs($user)
            ->post(route('play.letters.draw', $ageGroup), [
                'draw_id' => $drawId,
                'letter_type' => 'vowel',
            ]);

        $response
            ->assertOk()
            ->assertSee('1/8');

        $draw = session('chronomots.letters.draws.'.$drawId);

        $this->assertIsArray($draw);
        $this->assertCount(1, $draw['letters']);
        $this->assertSame('vowel', $draw['choice_history'][0]);
        $this->assertTrue(app(LetterPoolService::class)->isVowel($draw['letters'][0]));
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
        Word::query()->create([
            'word' => 'mots',
            'normalized_word' => 'MOTS',
            'length' => 4,
            'frequency' => 95,
            'age_level' => '7-9',
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
                'score' => 9999,
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

    public function test_letters_game_rejects_expired_submissions_server_side(): void
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
        Word::query()->create([
            'word' => 'mots',
            'normalized_word' => 'MOTS',
            'length' => 4,
            'frequency' => 95,
            'age_level' => '7-9',
        ]);

        $drawId = 'draw-test-expired-letters';

        $response = $this
            ->withSession([
                'chronomots' => [
                    'letters' => [
                        'draws' => [
                            $drawId => [
                                'draw_id' => $drawId,
                                'letters' => ['M', 'A', 'R', 'O', 'T', 'E', 'S', 'L'],
                                'started_at' => now()->subSeconds(90)->toIso8601String(),
                                'expires_at' => now()->subSecond()->toIso8601String(),
                                'game_type' => 'letters',
                                'age_group_id' => $ageGroup->id,
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
            ->assertStatus(422)
            ->assertSee('Temps dépassé pour ce tirage lettres');

        $this->assertDatabaseCount('game_sessions', 0);
        $this->assertDatabaseCount('letter_rounds', 0);
    }

    public function test_letters_game_rejects_word_submission_before_draw_is_complete(): void
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

        $drawId = 'draw-incomplete-letters';

        $response = $this
            ->withSession([
                'chronomots' => [
                    'letters' => [
                        'draws' => [
                            $drawId => [
                                'draw_id' => $drawId,
                                'letters' => ['M', 'A', 'R'],
                                'letters_target' => 8,
                                'allowed_choices' => ['vowel', 'consonant'],
                                'choice_history' => ['consonant', 'vowel', 'consonant'],
                                'started_at' => now()->subSeconds(10)->toIso8601String(),
                                'expires_at' => now()->addSeconds(50)->toIso8601String(),
                                'game_type' => 'letters',
                                'age_group_id' => $ageGroup->id,
                            ],
                        ],
                    ],
                ],
            ])
            ->actingAs($user)
            ->post(route('play.letters.submit', $ageGroup), [
                'draw_id' => $drawId,
                'submitted_word' => 'mar',
            ]);

        $response
            ->assertStatus(422)
            ->assertSee('Le tirage n’est pas encore complet');

        $this->assertDatabaseCount('game_sessions', 0);
        $this->assertDatabaseCount('letter_rounds', 0);
    }

    public function test_letters_game_rejects_words_not_present_in_dictionary(): void
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

        $drawId = 'draw-test-dictionary';

        $response = $this
            ->withSession([
                'chronomots' => [
                    'letters' => [
                        'draws' => [
                            $drawId => [
                                'draw_id' => $drawId,
                                'letters' => ['M', 'A', 'R', 'O', 'T', 'E', 'S', 'L'],
                                'started_at' => now()->toDateTimeString(),
                            ],
                        ],
                    ],
                ],
            ])
            ->actingAs($user)
            ->post(route('play.letters.submit', $ageGroup), [
                'draw_id' => $drawId,
                'submitted_word' => 'motels',
            ]);

        $response
            ->assertStatus(422)
            ->assertSee('Le mot proposé n’existe pas encore dans le dictionnaire de Chronomots.');

        $this->assertDatabaseCount('game_sessions', 0);
        $this->assertDatabaseCount('letter_rounds', 0);
    }

    public function test_letters_game_rejects_words_not_allowed_for_the_age_group(): void
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
        Word::query()->create([
            'word' => 'réflexion',
            'normalized_word' => 'REFLEXION',
            'length' => 9,
            'frequency' => 42,
            'age_level' => '14+',
        ]);

        $drawId = 'draw-test-age-level';

        $response = $this
            ->withSession([
                'chronomots' => [
                    'letters' => [
                        'draws' => [
                            $drawId => [
                                'draw_id' => $drawId,
                                'letters' => ['R', 'E', 'F', 'L', 'E', 'X', 'I', 'O', 'N', 'S'],
                                'started_at' => now()->toDateTimeString(),
                            ],
                        ],
                    ],
                ],
            ])
            ->actingAs($user)
            ->post(route('play.letters.submit', $ageGroup), [
                'draw_id' => $drawId,
                'submitted_word' => 'réflexion',
            ]);

        $response
            ->assertStatus(422)
            ->assertSee('Ce mot n’est pas encore autorisé pour cette catégorie d’âge.');

        $this->assertDatabaseCount('game_sessions', 0);
        $this->assertDatabaseCount('letter_rounds', 0);
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

    public function test_letters_game_can_display_vs_ai_results(): void
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

        Word::query()->create([
            'word' => 'mots',
            'normalized_word' => 'MOTS',
            'length' => 4,
            'frequency' => 95,
            'age_level' => '7-9',
        ]);

        Word::query()->create([
            'word' => 'mare',
            'normalized_word' => 'MARE',
            'length' => 4,
            'frequency' => 62,
            'age_level' => '7-9',
        ]);

        $drawId = 'draw-test-ai-letters';

        $response = $this
            ->withSession([
                'chronomots' => [
                    'letters' => [
                        'draws' => [
                            $drawId => [
                                'draw_id' => $drawId,
                                'letters' => ['M', 'A', 'R', 'O', 'T', 'E', 'S', 'L'],
                                'started_at' => now()->subSeconds(15)->toDateTimeString(),
                                'opponent_level' => 'expert',
                            ],
                        ],
                    ],
                ],
            ])
            ->actingAs($user)
            ->post(route('play.letters.submit', $ageGroup), [
                'draw_id' => $drawId,
                'submitted_word' => 'mots',
                'opponent_level' => 'expert',
            ]);

        $response
            ->assertOk()
            ->assertSee('VS IA')
            ->assertSee('IA Expert')
            ->assertSee('Comparaison joueur vs IA');
    }

    public function test_letters_game_submit_route_is_rate_limited(): void
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

        $drawId = 'draw-test-rate-limit-letters';
        $sessionPayload = [
            'chronomots' => [
                'letters' => [
                    'draws' => [
                        $drawId => [
                            'draw_id' => $drawId,
                            'letters' => ['M', 'A', 'R', 'O', 'T', 'E', 'S', 'L'],
                            'started_at' => now()->toIso8601String(),
                            'expires_at' => now()->addMinute()->toIso8601String(),
                            'game_type' => 'letters',
                            'age_group_id' => $ageGroup->id,
                        ],
                    ],
                ],
            ],
        ];

        for ($attempt = 0; $attempt < 8; $attempt++) {
            $this->withSession($sessionPayload)
                ->actingAs($user)
                ->post(route('play.letters.submit', $ageGroup), [
                    'draw_id' => $drawId,
                    'submitted_word' => 'motels',
                ])
                ->assertStatus(422);
        }

        $this->withSession($sessionPayload)
            ->actingAs($user)
            ->post(route('play.letters.submit', $ageGroup), [
                'draw_id' => $drawId,
                'submitted_word' => 'motels',
            ])
            ->assertStatus(429)
            ->assertSee('Trop de tentatives sur le mode lettres');
    }

    public function test_letters_game_unlocks_and_displays_first_word_achievement(): void
    {
        $this->seed(AchievementSeeder::class);

        $user = User::factory()->create();
        $ageGroup = AgeGroup::query()->create([
            'name' => '10-13 ans',
            'min_age' => 10,
            'max_age' => 13,
            'description' => 'Mode entraînement',
            'letters_timer_seconds' => 60,
            'numbers_timer_seconds' => 90,
        ]);

        Word::query()->create([
            'word' => 'mots',
            'normalized_word' => 'MOTS',
            'length' => 4,
            'frequency' => 95,
            'age_level' => '7-9',
        ]);

        $drawId = 'draw-test-achievement-letters';

        $response = $this
            ->withSession([
                'chronomots' => [
                    'letters' => [
                        'draws' => [
                            $drawId => [
                                'draw_id' => $drawId,
                                'letters' => ['M', 'A', 'R', 'O', 'T', 'E', 'S', 'L'],
                                'started_at' => now()->subSeconds(15)->toDateTimeString(),
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
            ->assertSee('Succès débloqués')
            ->assertSee('Premier Mot');

        $achievementId = Achievement::query()
            ->where('code', AchievementService::FIRST_VALID_WORD)
            ->value('id');

        $this->assertNotNull($achievementId);
        $this->assertDatabaseHas('user_achievements', [
            'user_id' => $user->id,
            'achievement_id' => $achievementId,
            'progress_value' => 1,
        ]);
        $this->assertNotNull(
            $user->fresh()->userAchievements()->where('achievement_id', $achievementId)->value('unlocked_at'),
        );
    }
}
