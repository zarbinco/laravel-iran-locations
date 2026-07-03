<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Sync;

final class LocationSyncDatasetResult
{
    /**
     * @var array<int, LocationSyncChange>
     */
    private array $changes = [];

    /**
     * @var array<string, int>
     */
    private array $totals = [
        'created' => 0,
        'updated' => 0,
        'unchanged' => 0,
        'deprecated' => 0,
        'skipped' => 0,
        'failed' => 0,
    ];

    public function __construct(
        public readonly string $dataset,
    ) {}

    public function add(LocationSyncChange $change): void
    {
        $this->changes[] = $change;
        $this->totals[$this->totalKey($change->action)]++;
    }

    /**
     * @return array<int, LocationSyncChange>
     */
    public function changes(): array
    {
        return $this->changes;
    }

    /**
     * @return array<string, int>
     */
    public function totals(): array
    {
        return $this->totals;
    }

    public function hasChanges(): bool
    {
        return $this->totals['created'] > 0
            || $this->totals['updated'] > 0
            || $this->totals['deprecated'] > 0;
    }

    public function hasFailures(): bool
    {
        return $this->totals['failed'] > 0;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'dataset' => $this->dataset,
            'totals' => $this->totals(),
            'changes' => array_map(
                static fn (LocationSyncChange $change): array => $change->toArray(),
                $this->changes,
            ),
        ];
    }

    private function totalKey(string $action): string
    {
        return match ($action) {
            'create' => 'created',
            'update' => 'updated',
            'deprecate' => 'deprecated',
            'skip' => 'skipped',
            'fail' => 'failed',
            default => 'unchanged',
        };
    }
}
