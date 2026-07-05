<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Commands;

use Illuminate\Console\Command;
use Zarbin\IranLocations\Contracts\LocationDataRepository;
use Zarbin\IranLocations\Sync\LocationSyncDatasetResult;
use Zarbin\IranLocations\Sync\LocationSyncException;
use Zarbin\IranLocations\Sync\LocationSyncOptions;
use Zarbin\IranLocations\Sync\LocationSyncService;

class SyncCommand extends Command
{
    protected $signature = 'iran-locations:sync
        {--dry-run : Preview sync actions without writing data}
        {--only= : Comma-separated datasets to sync}
        {--force : Allow package sync to update records with unknown source}
        {--no-deprecate : Do not deprecate missing package records}
        {--chunk=500 : Number of package data records processed per sync chunk}';

    protected $description = 'Synchronize package Iran location data safely.';

    public function handle(LocationSyncService $sync, LocationDataRepository $repository): int
    {
        $this->info('Iran Locations Sync');
        $this->line('Data version: '.$repository->dataVersion());
        $this->line('Checksum: '.(string) ($repository->manifest()['checksum'] ?? ''));
        $this->line('Mode: '.$this->mode());
        $this->newLine();

        try {
            $result = $sync->sync(LocationSyncOptions::make(
                dryRun: (bool) $this->option('dry-run'),
                datasets: $this->onlyDatasets(),
                deprecateMissing: ! (bool) $this->option('no-deprecate'),
                force: (bool) $this->option('force'),
                chunkSize: (int) $this->option('chunk'),
            ));
        } catch (LocationSyncException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        foreach ($result->datasets() as $dataset) {
            $this->line($this->summaryLine($dataset));
        }

        $this->newLine();

        if ($result->dryRun) {
            $this->line('No database changes were made.');
        } elseif ($result->isSuccessful()) {
            $this->line('Database changes were applied safely.');
        } else {
            $this->error('Sync completed with failures. Data version was not recorded.');
        }

        return $result->isSuccessful() ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @return array<int, string>|null
     */
    private function onlyDatasets(): ?array
    {
        $only = $this->option('only');

        if (! is_string($only) || trim($only) === '') {
            return null;
        }

        return array_values(array_filter(array_map(
            static fn (string $dataset): string => trim($dataset),
            explode(',', $only),
        )));
    }

    private function mode(): string
    {
        return (bool) $this->option('dry-run') ? 'dry-run' : 'apply';
    }

    private function summaryLine(LocationSyncDatasetResult $dataset): string
    {
        $totals = $dataset->totals();

        return sprintf(
            '%s: +%d ~%d =%d -%d skipped %d failed %d',
            $dataset->dataset,
            $totals['created'],
            $totals['updated'],
            $totals['unchanged'],
            $totals['deprecated'],
            $totals['skipped'],
            $totals['failed'],
        );
    }
}
