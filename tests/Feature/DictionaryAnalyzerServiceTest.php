<?php

namespace Tests\Feature;

use App\Services\DictionaryAnalyzerService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Tests\TestCase;

class DictionaryAnalyzerServiceTest extends TestCase
{
    private string $dictionaryDirectory;

    private string $dictionaryPath;

    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array']);
        Cache::store('array')->flush();

        $this->dictionaryDirectory = storage_path('app/dictionary');
        $this->dictionaryPath = $this->dictionaryDirectory.DIRECTORY_SEPARATOR.'french_words.txt';

        File::ensureDirectoryExists($this->dictionaryDirectory);
    }

    protected function tearDown(): void
    {
        if (File::exists($this->dictionaryPath)) {
            File::delete($this->dictionaryPath);
        }

        Cache::store('array')->flush();

        parent::tearDown();
    }

    public function test_it_finds_all_possible_words_for_a_draw(): void
    {
        $this->writeDictionary([
            'ami',
            'amis',
            'maison',
            'mason',
            'sain',
            'lion',
            'zebre',
        ]);

        $service = app(DictionaryAnalyzerService::class);

        $words = $service->findPossibleWords(['M', 'A', 'I', 'S', 'O', 'N']);

        $this->assertSame(['MAISON', 'MASON', 'AMIS', 'SAIN', 'AMI'], $words);
    }

    public function test_it_returns_the_longest_possible_word(): void
    {
        $this->writeDictionary([
            'ami',
            'amis',
            'maison',
            'sain',
        ]);

        $service = app(DictionaryAnalyzerService::class);

        $this->assertSame('MAISON', $service->getLongestWord(['M', 'A', 'I', 'S', 'O', 'N']));
    }

    public function test_it_calculates_a_high_playability_score_for_a_good_draw(): void
    {
        $this->writeDictionary([
            'ami',
            'amis',
            'maison',
            'sain',
        ]);

        $service = app(DictionaryAnalyzerService::class);

        $score = $service->calculatePlayabilityScore(['M', 'A', 'I', 'S', 'O', 'N']);

        $this->assertSame(4, $score['possible_words_count']);
        $this->assertSame('MAISON', $score['longest_word']);
        $this->assertSame(6, $score['longest_word_length']);
        $this->assertFalse($score['too_difficult']);
        $this->assertGreaterThan(0, $score['score']);
        $this->assertLessThanOrEqual(100, $score['score']);
    }

    public function test_it_marks_a_draw_as_too_difficult_when_no_words_are_found(): void
    {
        $this->writeDictionary([
            'ami',
            'maison',
        ]);

        $service = app(DictionaryAnalyzerService::class);

        $score = $service->calculatePlayabilityScore(['Q', 'W', 'X', 'Y', 'Z']);

        $this->assertSame(0, $score['possible_words_count']);
        $this->assertNull($score['longest_word']);
        $this->assertSame(0, $score['longest_word_length']);
        $this->assertTrue($score['too_difficult']);
        $this->assertSame(0, $score['score']);
    }

    public function test_it_throws_a_clear_exception_when_the_dictionary_file_is_missing(): void
    {
        if (File::exists($this->dictionaryPath)) {
            File::delete($this->dictionaryPath);
        }

        $service = app(DictionaryAnalyzerService::class);

        $messagePrefix = 'Dictionary file not found at path: ';

        try {
            $service->findPossibleWords(['A', 'M', 'I']);
            $this->fail('Expected a RuntimeException when the dictionary file is missing.');
        } catch (RuntimeException $exception) {
            $this->assertStringStartsWith($messagePrefix, $exception->getMessage());

            $actualPath = substr($exception->getMessage(), strlen($messagePrefix));
            $normalizePath = static fn (string $path): string => str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

            $this->assertSame(
                $normalizePath($this->dictionaryPath),
                $normalizePath($actualPath),
            );
        }
    }

    /**
     * @param  array<int, string>  $words
     */
    private function writeDictionary(array $words): void
    {
        File::put($this->dictionaryPath, implode(PHP_EOL, $words).PHP_EOL);
        Cache::store('array')->flush();
    }
}
