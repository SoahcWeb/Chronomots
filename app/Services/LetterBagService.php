<?php

namespace App\Services;

use InvalidArgumentException;
use RuntimeException;

class LetterBagService
{
    /**
     * Approximate French letter frequency distribution for gameplay-oriented draws.
     *
     * The values represent the number of copies available in the bag.
     *
     * @var array<string, int>
     */
    private const DISTRIBUTION = [
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

    /**
     * Current mutable state of the bag.
     *
     * @var array<int, string>
     */
    private array $bag = [];

    /**
     * Active distribution currently used to rebuild the bag.
     *
     * @var array<string, int>
     */
    private array $distribution = [];

    public function __construct()
    {
        $this->distribution = self::DISTRIBUTION;
        $this->resetBag();
    }

    /**
     * Draw a number of letters from the bag without replacement.
     *
     * @return array<int, string>
     */
    public function generateDraw(int $count = 10): array
    {
        if ($count < 1) {
            throw new InvalidArgumentException('The draw size must be at least 1.');
        }

        if ($count > count($this->bag)) {
            throw new RuntimeException('Not enough letters remain in the bag to satisfy this draw.');
        }

        $draw = [];

        for ($index = 0; $index < $count; $index++) {
            $selectedIndex = random_int(0, count($this->bag) - 1);
            $draw[] = $this->bag[$selectedIndex];
            array_splice($this->bag, $selectedIndex, 1);
        }

        return $draw;
    }

    /**
     * Reset the bag to its original full distribution.
     */
    public function resetBag(): void
    {
        $this->bag = [];

        foreach ($this->distribution as $letter => $copies) {
            for ($index = 0; $index < $copies; $index++) {
                $this->bag[] = $letter;
            }
        }
    }

    /**
     * Return the remaining letters currently available in the bag.
     *
     * @return array<int, string>
     */
    public function getRemainingLetters(): array
    {
        return $this->bag;
    }

    /**
     * Return the immutable default French distribution used as a baseline.
     *
     * @return array<string, int>
     */
    public function getBaseDistribution(): array
    {
        return self::DISTRIBUTION;
    }

    /**
     * Return the currently active distribution applied to the bag.
     *
     * @return array<string, int>
     */
    public function getDistribution(): array
    {
        return $this->distribution;
    }

    /**
     * Replace the active distribution and rebuild the bag immediately.
     *
     * @param  array<string, int>  $distribution
     */
    public function setDistribution(array $distribution): void
    {
        $normalizedDistribution = [];

        foreach ($distribution as $letter => $copies) {
            if (! is_string($letter) || strlen($letter) !== 1) {
                throw new InvalidArgumentException('Each distribution key must be a single-letter string.');
            }

            if (! is_int($copies) || $copies < 0) {
                throw new InvalidArgumentException('Each distribution value must be a non-negative integer.');
            }

            if ($copies === 0) {
                continue;
            }

            $normalizedDistribution[strtoupper($letter)] = $copies;
        }

        if ($normalizedDistribution === []) {
            throw new InvalidArgumentException('The distribution must contain at least one available letter.');
        }

        $this->distribution = $normalizedDistribution;
        $this->resetBag();
    }
}
