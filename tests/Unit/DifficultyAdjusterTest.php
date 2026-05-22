<?php

namespace Tests\Unit;

use App\Enums\DifficultyLevel;
use App\Services\DifficultyAdjuster;
use App\Services\LetterBagService;
use PHPUnit\Framework\TestCase;

class DifficultyAdjusterTest extends TestCase
{
    public function test_difficulty_level_enum_exposes_the_expected_cases(): void
    {
        $this->assertSame('easy', DifficultyLevel::EASY->value);
        $this->assertSame('normal', DifficultyLevel::NORMAL->value);
        $this->assertSame('hard', DifficultyLevel::HARD->value);
        $this->assertSame('expert', DifficultyLevel::EXPERT->value);
    }

    public function test_easy_increases_vowels_and_frequent_letters(): void
    {
        $bag = new LetterBagService();
        $adjuster = new DifficultyAdjuster($bag);
        $baseDistribution = $bag->getBaseDistribution();

        $adjuster->applyDifficulty(DifficultyLevel::EASY);

        $distribution = $bag->getDistribution();

        $this->assertGreaterThan($baseDistribution['E'], $distribution['E']);
        $this->assertGreaterThan($baseDistribution['A'], $distribution['A']);
        $this->assertGreaterThan($baseDistribution['U'], $distribution['U']);
        $this->assertLessThanOrEqual($baseDistribution['J'], $distribution['J']);
        $this->assertLessThanOrEqual($baseDistribution['Z'], $distribution['Z']);
        $this->assertCount(array_sum($distribution), $bag->getRemainingLetters());
    }

    public function test_normal_restores_the_base_distribution(): void
    {
        $bag = new LetterBagService();
        $adjuster = new DifficultyAdjuster($bag);

        $adjuster->applyDifficulty(DifficultyLevel::EXPERT);
        $adjuster->applyDifficulty(DifficultyLevel::NORMAL);

        $this->assertSame($bag->getBaseDistribution(), $bag->getDistribution());
        $this->assertCount(array_sum($bag->getBaseDistribution()), $bag->getRemainingLetters());
    }

    public function test_hard_favors_complex_consonants(): void
    {
        $bag = new LetterBagService();
        $adjuster = new DifficultyAdjuster($bag);
        $baseDistribution = $bag->getBaseDistribution();

        $adjuster->applyDifficulty(DifficultyLevel::HARD);

        $distribution = $bag->getDistribution();

        $this->assertGreaterThan($baseDistribution['C'], $distribution['C']);
        $this->assertGreaterThan($baseDistribution['D'], $distribution['D']);
        $this->assertGreaterThan($baseDistribution['V'], $distribution['V']);
        $this->assertLessThan($baseDistribution['E'], $distribution['E']);
        $this->assertLessThan($baseDistribution['A'], $distribution['A']);
    }

    public function test_expert_makes_rare_letters_more_frequent(): void
    {
        $bag = new LetterBagService();
        $adjuster = new DifficultyAdjuster($bag);
        $baseDistribution = $bag->getBaseDistribution();

        $adjuster->applyDifficulty(DifficultyLevel::EXPERT);

        $distribution = $bag->getDistribution();

        $this->assertGreaterThan($baseDistribution['J'], $distribution['J']);
        $this->assertGreaterThan($baseDistribution['K'], $distribution['K']);
        $this->assertGreaterThan($baseDistribution['Q'], $distribution['Q']);
        $this->assertGreaterThan($baseDistribution['Z'], $distribution['Z']);
        $this->assertLessThan($baseDistribution['E'], $distribution['E']);
    }

    public function test_reset_bag_uses_the_active_difficulty_distribution(): void
    {
        $bag = new LetterBagService();
        $adjuster = new DifficultyAdjuster($bag);

        $adjuster->applyDifficulty(DifficultyLevel::HARD);
        $distribution = $bag->getDistribution();

        $bag->generateDraw(10);
        $bag->resetBag();

        $this->assertSame($distribution, array_count_values($bag->getRemainingLetters()));
    }
}
