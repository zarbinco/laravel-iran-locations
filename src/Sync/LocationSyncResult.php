<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Sync;

final class LocationSyncResult
{
    /**
     * @param  array<int, LocationSyncDatasetResult>  $datasets
     */
    public function __construct(
        public readonly string $dataVersion,
        public readonly bool $dryRun,
        private readonly array $datasets,
    ) {}

    /**
     * @return array<int, LocationSyncDatasetResult>
     */
    public function datasets(): array
    {
        return $this->datasets;
    }

    /**
     * @return array<string, LocationSyncDatasetResult>
     */
    public function datasetsByName(): array
    {
        $datasets = [];

        foreach ($this->datasets as $dataset) {
            $datasets[$dataset->dataset] = $dataset;
        }

        return $datasets;
    }

    /**
     * @return array<string, int>
     */
    public function totals(): array
    {
        $totals = [
            'created' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'deprecated' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        foreach ($this->datasets as $dataset) {
            foreach ($dataset->totals() as $key => $value) {
                $totals[$key] += $value;
            }
        }

        return $totals;
    }

    public function hasChanges(): bool
    {
        foreach ($this->datasets as $dataset) {
            if ($dataset->hasChanges()) {
                return true;
            }
        }

        return false;
    }

    public function isSuccessful(): bool
    {
        foreach ($this->datasets as $dataset) {
            if ($dataset->hasFailures()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        $datasets = [];

        foreach ($this->datasets as $dataset) {
            $datasets[$dataset->dataset] = $dataset->totals();
        }

        return [
            'data_version' => $this->dataVersion,
            'dry_run' => $this->dryRun,
            'totals' => $this->totals(),
            'datasets' => $datasets,
        ];
    }
}
