<?php

namespace Tests\Feature;

use App\Services\GameIntelligence\DrawBalanceAnalyzer;
use App\Services\GameIntelligence\SmartDrawGenerator;
use App\Services\LetterBagService;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Tests\TestCase;

class SmartDrawGeneratorTest extends TestCase
{
    public function test_it_returns_a_balanced_draw_with_quality_metadata(): void
    {
        Log::spy();

        $letters = ['M', 'A', 'R', 'O', 'T', 'E', 'S', 'L', 'N', 'U'];
        $letterBag = new SmartDrawGeneratorFakeLetterBagService([$letters]);
        $analyzer = new DrawBalanceAnalyzer(app(\App\Services\GameIntelligence\LetterPoolService::class));
        $generator = new SmartDrawGenerator($letterBag, $analyzer);

        $result = $generator->generate(10);

        $this->assertCount(10, $result['letters']);
        $this->assertSame($letters, $result['letters']);
        $this->assertSame(100, $result['quality_score']);
        $this->assertSame(100, $result['analysis']['balance_score']);
        $this->assertSame([], $result['analysis']['problems']);
        $this->assertSame(1, $result['attempt']);
        $this->assertSame(1, $letterBag->resetCalls);

        Log::shouldHaveReceived('info')
            ->once()
            ->withArgs(fn (string $message, array $context): bool => $message === 'Smart draw accepted.'
                && $context['letters'] === $result['letters']
                && $context['quality_score'] === 100);
    }

    public function test_it_retries_until_a_balanced_draw_is_found(): void
    {
        Log::spy();

        $letterBag = new SmartDrawGeneratorFakeLetterBagService([
            ['B', 'C', 'D', 'F', 'G', 'H', 'L', 'M', 'N', 'R'],
            ['M', 'A', 'R', 'O', 'T', 'E', 'S', 'L', 'N', 'U'],
        ]);
        $analyzer = new DrawBalanceAnalyzer(app(\App\Services\GameIntelligence\LetterPoolService::class));
        $generator = new SmartDrawGenerator($letterBag, $analyzer);

        $result = $generator->generate(10);

        $this->assertSame(['M', 'A', 'R', 'O', 'T', 'E', 'S', 'L', 'N', 'U'], $result['letters']);
        $this->assertSame(2, $result['attempt']);
        $this->assertSame(2, $letterBag->resetCalls);

        Log::shouldHaveReceived('debug')
            ->once()
            ->withArgs(fn (string $message, array $context): bool => $message === 'Smart draw rejected as unbalanced.'
                && $context['attempt'] === 1
                && $context['letters'] === ['B', 'C', 'D', 'F', 'G', 'H', 'L', 'M', 'N', 'R']);
    }

    public function test_it_throws_after_fifty_unsuccessful_attempts(): void
    {
        Log::spy();

        $invalidDraw = ['B', 'C', 'D', 'F', 'G', 'H', 'L', 'M', 'N', 'R'];
        $letterBag = new SmartDrawGeneratorFakeLetterBagService(array_fill(0, 50, $invalidDraw));
        $analyzer = new DrawBalanceAnalyzer(app(\App\Services\GameIntelligence\LetterPoolService::class));
        $generator = new SmartDrawGenerator($letterBag, $analyzer);

        try {
            $generator->generate(10);
            $this->fail('A RuntimeException should have been thrown after 50 unsuccessful attempts.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Unable to generate a balanced letters draw after 50 attempts.', $exception->getMessage());
        }

        $this->assertSame(50, $letterBag->resetCalls);
        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(fn (string $message, array $context): bool => $message === 'Smart draw generation exhausted all attempts without finding a balanced draw.'
                && $context['max_attempts'] === 50
                && $context['count'] === 10);
    }
}

class SmartDrawGeneratorFakeLetterBagService extends LetterBagService
{
    /**
     * @param  array<int, array<int, string>>  $draws
     */
    public function __construct(
        private array $draws,
    ) {
    }

    public int $resetCalls = 0;

    /**
     * @return array<int, string>
     */
    public function generateDraw(int $count = 10): array
    {
        if ($this->draws === []) {
            return array_fill(0, $count, 'B');
        }

        return array_shift($this->draws);
    }

    public function resetBag(): void
    {
        $this->resetCalls++;
    }

    /**
     * @return array<int, string>
     */
    public function getRemainingLetters(): array
    {
        return [];
    }
}
