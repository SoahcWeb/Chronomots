<?php

namespace Tests\Unit;

use App\Services\GameIntelligence\DrawBalanceAnalyzer;
use App\Services\GameIntelligence\LetterPoolService;
use PHPUnit\Framework\TestCase;

class DrawBalanceAnalyzerTest extends TestCase
{
    private DrawBalanceAnalyzer $analyzer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->analyzer = new DrawBalanceAnalyzer(new LetterPoolService());
    }

    public function test_it_reports_a_balanced_draw(): void
    {
        $analysis = $this->analyzer->analyze(['M', 'A', 'R', 'O', 'T', 'E', 'S', 'L', 'N', 'U']);

        $this->assertSame(4, $analysis['vowel_count']);
        $this->assertSame(6, $analysis['consonant_count']);
        $this->assertSame(0, $analysis['rare_letters_count']);
        $this->assertSame(100, $analysis['balance_score']);
        $this->assertSame([], $analysis['problems']);
        $this->assertTrue($this->analyzer->isBalanced(['M', 'A', 'R', 'O', 'T', 'E', 'S', 'L', 'N', 'U']));
    }

    public function test_it_detects_when_there_are_not_enough_vowels(): void
    {
        $analysis = $this->analyzer->analyze(['B', 'C', 'D', 'F', 'G', 'A', 'E', 'L', 'N', 'R']);

        $this->assertSame(2, $analysis['vowel_count']);
        $this->assertFalse($this->analyzer->isBalanced(['B', 'C', 'D', 'F', 'G', 'A', 'E', 'L', 'N', 'R']));
        $this->assertContains('Not enough vowels: 2 found, 3 required.', $analysis['problems']);
        $this->assertLessThan(100, $analysis['balance_score']);
    }

    public function test_it_detects_when_there_are_too_many_vowels(): void
    {
        $analysis = $this->analyzer->analyze(['A', 'E', 'I', 'O', 'U', 'A', 'E', 'L', 'N', 'R']);

        $this->assertSame(7, $analysis['vowel_count']);
        $this->assertContains('Too many vowels: 7 found, maximum allowed is 6.', $analysis['problems']);
        $this->assertFalse($this->analyzer->isBalanced(['A', 'E', 'I', 'O', 'U', 'A', 'E', 'L', 'N', 'R']));
    }

    public function test_it_detects_too_many_rare_letters(): void
    {
        $analysis = $this->analyzer->analyze(['A', 'E', 'I', 'R', 'S', 'T', 'J', 'K', 'L', 'N']);

        $this->assertSame(2, $analysis['rare_letters_count']);
        $this->assertContains('Too many rare letters: 2 found, maximum allowed is 1.', $analysis['problems']);
        $this->assertFalse($this->analyzer->isBalanced(['A', 'E', 'I', 'R', 'S', 'T', 'J', 'K', 'L', 'N']));
    }

    public function test_it_detects_too_many_consecutive_consonants(): void
    {
        $analysis = $this->analyzer->analyze(['A', 'B', 'C', 'D', 'F', 'E', 'I', 'L', 'N', 'O']);

        $this->assertContains(
            'Too many consecutive difficult consonants: longest run is 4, maximum allowed is 3.',
            $analysis['problems'],
        );
        $this->assertFalse($this->analyzer->isBalanced(['A', 'B', 'C', 'D', 'F', 'E', 'I', 'L', 'N', 'O']));
    }

    public function test_it_handles_lowercase_letters_consistently(): void
    {
        $analysis = $this->analyzer->analyze(['a', 'e', 'i', 'r', 's', 't', 'l', 'n', 'o', 'u']);

        $this->assertSame(5, $analysis['vowel_count']);
        $this->assertSame(5, $analysis['consonant_count']);
        $this->assertSame(0, $analysis['rare_letters_count']);
        $this->assertTrue($this->analyzer->isBalanced(['a', 'e', 'i', 'r', 's', 't', 'l', 'n', 'o', 'u']));
    }

    public function test_it_can_report_multiple_problems_at_once(): void
    {
        $analysis = $this->analyzer->analyze(['J', 'K', 'Q', 'W', 'B', 'C', 'D', 'A', 'E', 'L']);

        $this->assertCount(3, $analysis['problems']);
        $this->assertContains('Not enough vowels: 2 found, 3 required.', $analysis['problems']);
        $this->assertContains('Too many rare letters: 4 found, maximum allowed is 1.', $analysis['problems']);
        $this->assertContains(
            'Too many consecutive difficult consonants: longest run is 7, maximum allowed is 3.',
            $analysis['problems'],
        );
        $this->assertFalse($this->analyzer->isBalanced(['J', 'K', 'Q', 'W', 'B', 'C', 'D', 'A', 'E', 'L']));
        $this->assertLessThan(100, $analysis['balance_score']);
    }
}
