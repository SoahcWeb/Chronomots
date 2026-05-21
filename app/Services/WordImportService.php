<?php

namespace App\Services;

use App\Models\Word;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use SplFileObject;

class WordImportService
{
    /**
     * @var array<int, string>|null
     */
    private ?array $cachedWordColumns = null;

    public function __construct(
        private readonly WordNormalizerService $wordNormalizerService,
    ) {
    }

    /**
     * Import a local CSV or TXT dictionary file in SQLite-friendly batches.
     *
     * @param  array<string, mixed>  $options
     * @return array{processed: int, imported: int, skipped: int, duplicates_in_file: int}
     */
    public function importFromFile(string $path, array $options = []): array
    {
        if (! is_file($path)) {
            throw new InvalidArgumentException('Le fichier de dictionnaire est introuvable.');
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $batchSize = max(100, (int) ($options['batch_size'] ?? 1000));
        $delimiter = $this->resolveDelimiter($path, $options['delimiter'] ?? null);
        $progressEvery = max(100, (int) ($options['progress_every'] ?? $batchSize));
        $source = $options['source'] ?? basename($path);
        $onProgress = is_callable($options['on_progress'] ?? null)
            ? $options['on_progress']
            : null;

        $rows = match ($extension) {
            'csv' => $this->readCsv($path, $delimiter),
            'txt' => $this->readTxt($path),
            default => throw new InvalidArgumentException('Format non supporté. Utilise un fichier CSV ou TXT.'),
        };

        return $this->importRows($rows, [
            'batch_size' => $batchSize,
            'progress_every' => $progressEvery,
            'source' => $source,
            'on_progress' => $onProgress,
        ]);
    }

    /**
     * Import a Lexique TSV file with flexible header mapping.
     *
     * @param  array<string, mixed>  $options
     * @return array{processed: int, imported: int, skipped: int, duplicates_in_file: int}
     */
    public function importLexiqueFile(string $path, array $options = []): array
    {
        if (! is_file($path)) {
            throw new InvalidArgumentException('Le fichier Lexique est introuvable.');
        }

        $batchSize = max(100, (int) ($options['batch_size'] ?? 1000));
        $progressEvery = max(100, (int) ($options['progress_every'] ?? $batchSize));
        $onProgress = is_callable($options['on_progress'] ?? null)
            ? $options['on_progress']
            : null;

        return $this->importRows(
            $this->readLexiqueTsv($path),
            [
                'batch_size' => $batchSize,
                'progress_every' => $progressEvery,
                'source' => 'lexique400',
                'on_progress' => $onProgress,
            ],
        );
    }

    /**
     * @param  iterable<int, array<string, mixed>>  $rows
     * @param  array<string, mixed>  $options
     * @return array{processed: int, imported: int, skipped: int, duplicates_in_file: int}
     */
    private function importRows(iterable $rows, array $options): array
    {
        $batchSize = (int) $options['batch_size'];
        $progressEvery = (int) $options['progress_every'];
        $source = (string) $options['source'];
        $onProgress = is_callable($options['on_progress'] ?? null)
            ? $options['on_progress']
            : null;

        $processed = 0;
        $imported = 0;
        $skipped = 0;
        $duplicatesInFile = 0;
        $batch = [];
        $seenNormalizedWords = [];
        $timestamp = now();

        foreach ($rows as $row) {
            $processed++;

            $record = $this->wordNormalizerService->prepareImportRecord(
                (string) ($row['word'] ?? ''),
                [
                    'frequency' => $row['frequency'] ?? null,
                    'difficulty_level' => $row['difficulty_level'] ?? null,
                    'source' => $row['source'] ?? $source,
                    'age_level' => $row['age_level'] ?? null,
                    'is_active' => $row['is_active'] ?? true,
                ],
            );

            if ($record === null) {
                $skipped++;
                continue;
            }

            if (isset($seenNormalizedWords[$record['normalized_word']])) {
                $duplicatesInFile++;
                continue;
            }

            $seenNormalizedWords[$record['normalized_word']] = true;
            $batch[] = [
                ...$record,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];

            if (count($batch) >= $batchSize) {
                $imported += $this->flushBatch($batch);
                $batch = [];
                $this->notifyProgress($onProgress, $processed, $imported, $skipped, $duplicatesInFile, false);
            }

            if ($processed % $progressEvery === 0) {
                $this->notifyProgress($onProgress, $processed, $imported, $skipped, $duplicatesInFile, false);
            }
        }

        if ($batch !== []) {
            $imported += $this->flushBatch($batch);
        }

        $result = [
            'processed' => $processed,
            'imported' => $imported,
            'skipped' => $skipped,
            'duplicates_in_file' => $duplicatesInFile,
        ];

        $this->notifyProgress($onProgress, $processed, $imported, $skipped, $duplicatesInFile, true);

        return $result;
    }

    /**
     * @return \Generator<int, array<string, mixed>>
     */
    private function readLexiqueTsv(string $path): \Generator
    {
        foreach ($this->readDelimitedWithHeaders($path, "\t") as $row) {
            $word = $this->firstNonEmptyValue($row, [
                'ortho',
                '1_Mot',
                'mot',
                'word',
            ]);

            if ($word === null) {
                continue;
            }

            yield [
                'word' => $word,
                'frequency' => $this->firstNonEmptyValue($row, [
                    'freqfilms2',
                    'freqlivres',
                    '10_FreqMot',
                    '11_FreqOrtho',
                    'frequency',
                ]),
                'source' => 'lexique400',
                'is_active' => true,
            ];
        }
    }

    /**
     * @return \Generator<int, array<string, mixed>>
     */
    private function readTxt(string $path): \Generator
    {
        $file = new SplFileObject($path, 'r');

        while (! $file->eof()) {
            $line = trim((string) $file->fgets());

            if ($line === '') {
                continue;
            }

            yield ['word' => $line];
        }
    }

    /**
     * @return \Generator<int, array<string, mixed>>
     */
    private function readCsv(string $path, string $delimiter): \Generator
    {
        foreach ($this->readDelimitedWithHeaders($path, $delimiter) as $payload) {
            if (($payload['word'] ?? '') === '') {
                continue;
            }

            yield $payload;
        }
    }

    /**
     * @return \Generator<int, array<string, mixed>>
     */
    private function readDelimitedWithHeaders(string $path, string $delimiter): \Generator
    {
        $file = new SplFileObject($path, 'r');
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);
        $file->setCsvControl($delimiter);

        $headers = null;

        foreach ($file as $row) {
            if (! is_array($row) || $row === [null]) {
                continue;
            }

            if ($headers === null) {
                $headers = array_map(
                    fn (?string $value): string => $this->normalizeHeader((string) $value),
                    $row,
                );

                continue;
            }

            $values = array_map(
                static fn ($value): ?string => is_string($value) ? trim($value) : null,
                $row,
            );

            $payload = [];

            foreach ($headers as $index => $header) {
                if ($header === '') {
                    continue;
                }

                $payload[$header] = $values[$index] ?? null;
            }

            yield $payload;
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $batch
     */
    private function flushBatch(array $batch): int
    {
        if ($batch === []) {
            return 0;
        }

        $insertColumns = $this->getAvailableWordInsertColumns();
        $updateColumns = array_values(array_filter(
            ['word', 'length', 'frequency', 'difficulty_level', 'source', 'age_level', 'is_active', 'updated_at'],
            static fn (string $column): bool => in_array($column, $insertColumns, true),
        ));

        $normalizedBatch = array_map(function (array $row) use ($insertColumns): array {
            $payload = [];

            foreach ($insertColumns as $column) {
                if (array_key_exists($column, $row)) {
                    $payload[$column] = $row[$column];
                }
            }

            return $payload;
        }, $batch);

        DB::transaction(function () use ($normalizedBatch, $updateColumns): void {
            Word::query()->upsert(
                $normalizedBatch,
                ['normalized_word'],
                $updateColumns,
            );
        });

        return count($batch);
    }

    private function resolveDelimiter(string $path, mixed $delimiter): string
    {
        if (is_string($delimiter) && $delimiter !== '') {
            return $delimiter;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($extension !== 'csv') {
            return ';';
        }

        $file = new SplFileObject($path, 'r');
        $firstLine = (string) $file->fgets();
        $candidates = [';', ',', "\t", '|'];
        $bestDelimiter = ';';
        $bestScore = -1;

        foreach ($candidates as $candidate) {
            $score = substr_count($firstLine, $candidate);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestDelimiter = $candidate;
            }
        }

        return $bestDelimiter;
    }

    private function notifyProgress(
        ?callable $onProgress,
        int $processed,
        int $imported,
        int $skipped,
        int $duplicatesInFile,
        bool $finished,
    ): void {
        if ($onProgress === null) {
            return;
        }

        $onProgress([
            'processed' => $processed,
            'imported' => $imported,
            'skipped' => $skipped,
            'duplicates_in_file' => $duplicatesInFile,
            'finished' => $finished,
        ]);
    }

    private function normalizeHeader(string $header): string
    {
        return ltrim(trim($header), "\xEF\xBB\xBF");
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $keys
     */
    private function firstNonEmptyValue(array $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = $row[$key] ?? null;

            if (! is_string($value)) {
                continue;
            }

            $trimmed = trim($value);

            if ($trimmed !== '') {
                return $trimmed;
            }
        }

        return null;
    }

    /**
     * Keep imports compatible with partially migrated SQLite files.
     *
     * @return array<int, string>
     */
    private function getAvailableWordInsertColumns(): array
    {
        if ($this->cachedWordColumns !== null) {
            return $this->cachedWordColumns;
        }

        $columns = Schema::getColumnListing('words');
        $requiredColumns = ['normalized_word', 'created_at', 'updated_at'];

        foreach ($requiredColumns as $column) {
            if (! in_array($column, $columns, true)) {
                $columns[] = $column;
            }
        }

        return $this->cachedWordColumns = $columns;
    }
}
