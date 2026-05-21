<?php

namespace App\Console\Commands;

use App\Services\WordImportService;
use Illuminate\Console\Command;
use InvalidArgumentException;

class ImportWordsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'words:import
        {path : Chemin local vers un fichier CSV ou TXT}
        {--delimiter= : Délimiteur CSV à utiliser}
        {--source= : Nom de la source à enregistrer en base}
        {--batch=1000 : Taille des lots d\'import}
        {--progress=1000 : Fréquence d\'affichage de la progression}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importe un dictionnaire français local dans la table words';

    public function __construct(
        private readonly WordImportService $wordImportService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $path = (string) $this->argument('path');
        $batchSize = max(100, (int) $this->option('batch'));
        $progressEvery = max(100, (int) $this->option('progress'));
        $source = $this->option('source');
        $delimiter = $this->option('delimiter');
        $lastReportedProcessed = 0;

        $this->components->info('Import du dictionnaire Chronomots');
        $this->line("Fichier : {$path}");
        $this->line("Lot : {$batchSize} entrées");
        $this->line('[Progression] traités: 0 | importés: 0 | ignorés: 0 | doublons fichier: 0');

        try {
            $result = $this->wordImportService->importFromFile($path, [
                'batch_size' => $batchSize,
                'progress_every' => $progressEvery,
                'source' => $source,
                'delimiter' => $delimiter,
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

        $this->newLine();
        $this->table(
            ['Traités', 'Importés', 'Ignorés', 'Doublons fichier'],
            [[
                $result['processed'],
                $result['imported'],
                $result['skipped'],
                $result['duplicates_in_file'],
            ]],
        );
        $this->components->info('Import terminé.');

        return self::SUCCESS;
    }
}
