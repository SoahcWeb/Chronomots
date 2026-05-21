<?php

namespace Tests\Feature;

use App\Models\Word;
use App\Services\WordImportService;
use App\Services\WordNormalizerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WordImportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_word_normalizer_handles_accents_and_invalid_characters(): void
    {
        $normalizer = app(WordNormalizerService::class);

        $this->assertSame('ecole', $normalizer->normalize('École'));
        $this->assertSame('aujourdhui', $normalizer->normalize('Aujourd’hui'));
        $this->assertSame('cooperate', $normalizer->normalize('co-operate'));
        $this->assertTrue($normalizer->isValidNormalizedWord('francais'));
        $this->assertFalse($normalizer->isValidNormalizedWord('a'));
        $this->assertFalse($normalizer->isValidNormalizedWord('abc123'));
    }

    public function test_txt_import_prepares_lowercase_records_and_ignores_invalid_rows(): void
    {
        $path = storage_path('framework/testing-dictionary.txt');

        file_put_contents($path, implode(PHP_EOL, [
            'École',
            'MOTS',
            'mots',
            '1234',
            '',
            'AuJourd’hui',
        ]));

        $result = app(WordImportService::class)->importFromFile($path, [
            'source' => 'txt-local',
            'batch_size' => 2,
        ]);

        @unlink($path);

        $this->assertSame(5, $result['processed']);
        $this->assertSame(3, $result['imported']);
        $this->assertSame(1, $result['skipped']);
        $this->assertSame(1, $result['duplicates_in_file']);

        $this->assertDatabaseHas('words', [
            'word' => 'école',
            'normalized_word' => 'ecole',
            'source' => 'txt-local',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('words', [
            'word' => 'mots',
            'normalized_word' => 'mots',
            'source' => 'txt-local',
            'is_active' => true,
        ]);
    }

    public function test_csv_import_upserts_words_and_keeps_sqlite_friendly_metadata(): void
    {
        $path = storage_path('framework/testing-dictionary.csv');

        file_put_contents($path, implode(PHP_EOL, [
            'word;frequency;difficulty_level;source;age_level',
            'Éclair;50;2;csv-main;10-13',
            'Garçon;80;3;csv-main;14+',
            'Éclair;100;4;csv-updated;14+',
        ]));

        $result = app(WordImportService::class)->importFromFile($path, [
            'delimiter' => ';',
            'batch_size' => 100,
        ]);

        @unlink($path);

        $this->assertSame(3, $result['processed']);
        $this->assertSame(2, $result['imported']);
        $this->assertSame(0, $result['skipped']);
        $this->assertSame(1, $result['duplicates_in_file']);

        $this->assertDatabaseHas('words', [
            'normalized_word' => 'eclair',
            'word' => 'éclair',
            'frequency' => 50,
            'difficulty_level' => 2,
            'source' => 'csv-main',
            'age_level' => '10-13',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('words', [
            'normalized_word' => 'garcon',
            'word' => 'garçon',
            'frequency' => 80,
            'difficulty_level' => 3,
            'source' => 'csv-main',
            'age_level' => '14+',
            'is_active' => true,
        ]);

        $this->assertSame(2, Word::query()->count());
    }

    public function test_words_import_command_imports_a_local_dictionary_file(): void
    {
        $path = storage_path('framework/testing-command-dictionary.txt');

        file_put_contents($path, implode(PHP_EOL, [
            'École',
            'MOTS',
            'mots',
            '1234',
            'Garçon',
        ]));

        $this->artisan('words:import', [
            'path' => $path,
            '--source' => 'artisan-local',
            '--batch' => 2,
            '--progress' => 2,
        ])
            ->expectsOutputToContain('Import du dictionnaire Chronomots')
            ->expectsOutputToContain('Progression')
            ->expectsOutputToContain('Import terminé.')
            ->assertExitCode(0);

        @unlink($path);

        $this->assertDatabaseHas('words', [
            'normalized_word' => 'ecole',
            'source' => 'artisan-local',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('words', [
            'normalized_word' => 'garcon',
            'source' => 'artisan-local',
            'is_active' => true,
        ]);

        $this->assertSame(3, Word::query()->count());
    }

    public function test_lexique_import_handles_expected_headers_and_avoids_duplicates(): void
    {
        $path = storage_path('framework/testing-lexique.tsv');

        file_put_contents($path, implode(PHP_EOL, [
            "ortho\tfreqfilms2\tfreqlivres",
            "Poils\t12.75\t",
            "École\t\t8.5",
            "poils\t13.1\t",
            "1234\t1\t",
        ]));

        $result = app(WordImportService::class)->importLexiqueFile($path, [
            'batch_size' => 2,
        ]);

        @unlink($path);

        $this->assertSame(4, $result['processed']);
        $this->assertSame(2, $result['imported']);
        $this->assertSame(1, $result['skipped']);
        $this->assertSame(1, $result['duplicates_in_file']);

        $this->assertDatabaseHas('words', [
            'word' => 'poils',
            'normalized_word' => 'poils',
            'source' => 'lexique400',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('words', [
            'word' => 'école',
            'normalized_word' => 'ecole',
            'source' => 'lexique400',
            'is_active' => true,
        ]);
    }

    public function test_words_import_lexique_command_imports_tsv_and_reports_post_import_checks(): void
    {
        $path = storage_path('framework/testing-lexique-command.tsv');

        file_put_contents($path, implode(PHP_EOL, [
            "1_Mot\t10_FreqMot",
            "Poils\t0.003",
            "garçon\t1.75",
        ]));

        $this->artisan('words:import-lexique', [
            'path' => $path,
            '--batch' => 2,
            '--progress' => 2,
        ])
            ->expectsOutputToContain('Import du dictionnaire Lexique400')
            ->expectsOutputToContain(realpath($path) ?: $path)
            ->expectsOutputToContain('Progression')
            ->expectsOutputToContain('Lignes lues : 2')
            ->expectsOutputToContain('Mots importés : 2')
            ->expectsOutputToContain('Augmentation Word::count() : +2')
            ->expectsOutputToContain('Présence de "poils" : oui')
            ->expectsOutputToContain('Import Lexique terminé.')
            ->expectsOutputToContain('poils')
            ->assertExitCode(0);

        @unlink($path);

        $this->assertSame(2, Word::query()->count());
        $this->assertTrue(Word::query()->where('normalized_word', 'poils')->exists());
        $this->assertDatabaseHas('words', [
            'normalized_word' => 'garcon',
            'source' => 'lexique400',
            'is_active' => true,
        ]);
    }
}
