<?php

namespace Tests\Unit;

use App\Services\LetterBagService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class LetterBagServiceTest extends TestCase
{
    public function test_it_generates_a_draw_with_the_requested_number_of_letters(): void
    {
        $service = new LetterBagService();

        $draw = $service->generateDraw(10);

        $this->assertCount(10, $draw);
        $this->assertCount($this->fullBagSize() - 10, $service->getRemainingLetters());
        $this->assertSame(
            array_values($draw),
            array_values(array_map('strtoupper', $draw)),
        );
    }

    public function test_it_removes_drawn_letters_from_the_bag(): void
    {
        $service = new LetterBagService();
        $initialCounts = array_count_values($service->getRemainingLetters());

        $draw = $service->generateDraw(10);
        $remainingCounts = array_count_values($service->getRemainingLetters());

        foreach ($initialCounts as $letter => $initialCount) {
            $drawnCount = count(array_filter($draw, fn (string $drawnLetter): bool => $drawnLetter === $letter));

            $this->assertSame(
                $initialCount - $drawnCount,
                $remainingCounts[$letter] ?? 0,
                sprintf('Unexpected remaining count for letter %s.', $letter),
            );
        }
    }

    public function test_it_can_be_reset_to_the_full_distribution(): void
    {
        $service = new LetterBagService();

        $service->generateDraw(10);
        $service->resetBag();

        $remainingLetters = $service->getRemainingLetters();

        $this->assertCount($this->fullBagSize(), $remainingLetters);
        $this->assertSame($this->expectedDistribution(), array_count_values($remainingLetters));
    }

    public function test_it_returns_the_remaining_letters_after_multiple_draws(): void
    {
        $service = new LetterBagService();

        $firstDraw = $service->generateDraw(10);
        $secondDraw = $service->generateDraw(5);
        $remainingLetters = $service->getRemainingLetters();

        $this->assertCount($this->fullBagSize() - 15, $remainingLetters);
        $this->assertCount(15, array_merge($firstDraw, $secondDraw));
    }

    public function test_it_can_exhaust_the_bag_exactly(): void
    {
        $service = new LetterBagService();

        $draw = $service->generateDraw($this->fullBagSize());
        $expected = $this->expectedDistribution();
        $actual = array_count_values($draw);

        ksort($expected);
        ksort($actual);

        $this->assertCount($this->fullBagSize(), $draw);
        $this->assertSame([], $service->getRemainingLetters());
        $this->assertSame($expected, $actual);
    }

    public function test_it_rejects_a_non_positive_draw_size(): void
    {
        $service = new LetterBagService();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The draw size must be at least 1.');

        $service->generateDraw(0);
    }

    public function test_it_rejects_a_draw_larger_than_the_remaining_bag(): void
    {
        $service = new LetterBagService();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Not enough letters remain in the bag to satisfy this draw.');

        $service->generateDraw($this->fullBagSize() + 1);
    }

    /**
     * @return array<string, int>
     */
    private function expectedDistribution(): array
    {
        return [
            'E' => 15,
            'A' => 9,
            'I' => 8,
            'S' => 8,
            'N' => 7,
            'T' => 7,
            'R' => 6,
            'U' => 6,
            'O' => 5,
            'L' => 5,
            'D' => 4,
            'C' => 3,
            'M' => 3,
            'P' => 3,
            'G' => 2,
            'B' => 2,
            'F' => 2,
            'H' => 2,
            'V' => 2,
            'J' => 1,
            'K' => 1,
            'Q' => 1,
            'W' => 1,
            'X' => 1,
            'Y' => 1,
            'Z' => 1,
        ];
    }

    private function fullBagSize(): int
    {
        return array_sum($this->expectedDistribution());
    }
}
