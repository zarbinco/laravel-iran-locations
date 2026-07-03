<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Sync;

final class LocationSyncOptions
{
    /**
     * @param  array<int, string>|null  $datasets
     */
    public function __construct(
        public readonly bool $dryRun = false,
        public readonly ?array $datasets = null,
        public readonly bool $deprecateMissing = true,
        public readonly bool $force = false,
        public readonly int $chunkSize = 500,
    ) {}

    /**
     * @param  array<int, string>|null  $datasets
     */
    public static function make(
        bool $dryRun = false,
        ?array $datasets = null,
        bool $deprecateMissing = true,
        bool $force = false,
        int $chunkSize = 500,
    ): self {
        return new self(
            dryRun: $dryRun,
            datasets: $datasets === null ? null : array_values(array_unique($datasets)),
            deprecateMissing: $deprecateMissing,
            force: $force,
            chunkSize: max(1, $chunkSize),
        );
    }

    public function hasExplicitDatasets(): bool
    {
        return $this->datasets !== null;
    }
}
