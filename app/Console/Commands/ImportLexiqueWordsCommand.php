<?php

namespace App\Console\Commands;

use App\Models\Word;
use App\Services\WordImportService;
use Illuminate\Console\Command;
use InvalidArgumentException;

class ImportLexiqueWordsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'words:import-lexique
        {path? : Chemin local vers le fichier TSV Lexique}
        {--batch=1000 : Taille des lots d\'import}
        {--progress=1000 : Fréquence d\'affichage de la progression}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importe le dictionnaire Lexique dans la table words';

    public function __construct(
        private readonly WordImportService $wordImportService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $path = $this->resolvePath();
        $resolvedPath = realpath($path) ?: $path;
        $batchSize = max(100, (int) $this->option('batch'));
        $progressEvery = max(100, (int) $this->option('progress'));
        $lastReportedProcessed = 0;
        $wordsBeforeImport = Word::query()->count();

        $this->components->info('Import du dictionnaire Lexique400');
        $this->line("Fichier : {$resolvedPath}");
        $this->line("Lot : {$batchSize} entrées");
        $this->line('[Progression] traités: 0 | importés: 0 | ignorés: 0 | doublons fichier: 0');

        try {
            $result = $this->wordImportService->importLexiqueFile($path, [
                'batch_size' => $batchSize,
                'progress_every' => $progressEvery,
                'on_progress' => function (array $progress) use (&$lastReportedProcessed): void {
                    if (! $progress['finished'] && $progress['processed'] === $lastReportedProcessed) {
                        return;
                    }

                    $lastReportedProcessed = $progress['processed'];
                    $status = $progress['finished'] ? 'Final' : 'Progression';

                    $this->line(sprintf(
                        '[%s] traités: %d | importés: %d | ignorés: %d | doublons fichier: %d',
                        $status,
                        $progress['processed'],
                        $progress['imported'],
                        $progress['skipped'],
                        $progress['duplicates_in_file'],
                    ));
                },
            ]);
        } catch (InvalidArgumentException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        } catch (\Throwable $exception) {
            $this->components->error('Import interrompu: '.$exception->getMessage());

            return self::FAILURE;
        }

        $wordsAfterImport = Word::query()->count();
        $wordsDelta = $wordsAfterImport - $wordsBeforeImport;
        $poilsExists = Word::query()->where('normalized_word', 'poils')->exists();

        $this->newLine();
        $this->line(sprintf('Lignes lues : %d', $result['processed']));
        $this->line(sprintf('Mots importés : %d', $result['imported']));
        $this->line(sprintf('Total words avant import : %d', $wordsBeforeImport));
        $this->line(sprintf('Total words après import : %d', $wordsAfterImport));
        $this->line(sprintf('Augmentation Word::count() : %+d', $wordsDelta));
        $this->line(sprintf('Présence de "poils" : %s', $poilsExists ? 'oui' : 'non'));
        $this->newLine();
        $this->table(
            ['Traités', 'Importés', 'Ignorés', 'Doublons fichier', 'Avant', 'Après', 'Delta', 'poils'],
            [[
                $result['processed'],
                $result['imported'],
                $result['skipped'],
                $result['duplicates_in_file'],
                $wordsBeforeImport,
                $wordsAfterImport,
                $wordsDelta,
                $poilsExists ? 'oui' : 'non',
            ]],
        );
        $this->components->info('Import Lexique terminé.');

        return self::SUCCESS;
    }

    private function resolvePath(): string
    {
        $requestedPath = $this->argument('path');

        if (is_string($requestedPath) && trim($requestedPath) !== '') {
            return $requestedPath;
        }

        $lexique4Path = storage_path('app/dictionaries/Lexique4.tsv');

        if (is_file($lexique4Path)) {
            return $lexique4Path;
        }

        $lexique400Path = storage_path('app/dictionaries/Lexique400.tsv');

        if (is_file($lexique400Path)) {
            $this->components->warn('Lexique4.tsv introuvable, utilisation de Lexique400.tsv.');

            return $lexique400Path;
        }

        return $lexique4Path;
    }
}
