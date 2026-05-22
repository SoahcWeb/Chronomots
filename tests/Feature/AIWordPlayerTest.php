<?php

namespace Tests\Feature;

use App\Services\GameIntelligence\AIWordPlayer;
use App\Models\Word;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use Tests\TestCase;

class AIWordPlayerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array']);
        Cache::store('array')->flush();
    }

    public function test_beginner_chooses_a_suboptimal_word(): void
    {
        $this->seedPlayableWords();

        $result = app(AIWordPlayer::class)->play(['M', 'A', 'I', 'S', 'O', 'N'], 'beginner');

        $this->assertSame('AMIS', $result['chosen_word']);
        $this->assertSame(40, $result['score']);
        $this->assertSame(2270, $result['thinking_time']);
        $this->assertSame(50, $result['confidence']);
    }

    public function test_intermediate_chooses_a_good_but_not_best_word(): void
    {
        $this->seedPlayableWords();

        $result = app(AIWordPlayer::class)->play(['M', 'A', 'I', 'S', 'O', 'N'], 'intermediate');

        $this->assertSame('MASON', $result['chosen_word']);
        $this->assertSame(50, $result['score']);
        $this->assertSame(1640, $result['thinking_time']);
        $this->assertSame(77, $result['confidence']);
    }

    public function test_expert_picks_the_best_available_word(): void
    {
        $this->seedPlayableWords();

        $result = app(AIWordPlayer::class)->play(['M', 'A', 'I', 'S', 'O', 'N'], 'expert');

        $this->assertSame('MAISON', $result['chosen_word']);
        $this->assertSame(60, $result['score']);
        $this->assertSame(1110, $result['thinking_time']);
        $this->assertSame(99, $result['confidence']);
    }

    public function test_it_returns_an_empty_move_when_no_word_is_possible(): void
    {
        $result = app(AIWordPlayer::class)->play(['Q', 'W', 'X', 'Y', 'Z'], 'beginner');

        $this->assertSame('', $result['chosen_word']);
        $this->assertSame(0, $result['score']);
        $this->assertSame(2200, $result['thinking_time']);
        $this->assertSame(35, $result['confidence']);
    }

    public function test_it_rejects_unsupported_difficulty_levels(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported AI difficulty: impossible');

        app(AIWordPlayer::class)->play(['M', 'A', 'I'], 'impossible');
    }

    public function test_it_uses_cached_candidates_without_requiring_a_second_query_path(): void
    {
        $this->seedPlayableWords();
        $player = app(AIWordPlayer::class);

        $first = $player->play(['M', 'A', 'I', 'S', 'O', 'N'], 'expert');

        Word::query()->delete();

        $second = $player->play(['M', 'A', 'I', 'S', 'O', 'N'], 'expert');

        $this->assertSame($first, $second);
    }

    private function seedPlayableWords(): void
    {
        $words = [
            ['normalized_word' => 'maison', 'length' => 6, 'frequency' => 100],
            ['normalized_word' => 'mason', 'length' => 5, 'frequency' => 90],
            ['normalized_word' => 'amis', 'length' => 4, 'frequency' => 80],
            ['normalized_word' => 'sain', 'length' => 4, 'frequency' => 70],
            ['normalized_word' => 'ami', 'length' => 3, 'frequency' => 60],
        ];

        foreach ($words as $attributes) {
            Word::query()->create([
                'word' => $attributes['normalized_word'],
                'normalized_word' => $attributes['normalized_word'],
                'length' => $attributes['length'],
                'frequency' => $attributes['frequency'],
                'is_active' => true,
            ]);
        }
    }
}
