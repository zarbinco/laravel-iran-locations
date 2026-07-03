<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Sync;

final class LocationSyncChange
{
    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public function __construct(
        public readonly string $dataset,
        public readonly string $code,
        public readonly string $action,
        public readonly ?array $before = null,
        public readonly ?array $after = null,
        public readonly ?string $message = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'dataset' => $this->dataset,
            'code' => $this->code,
            'action' => $this->action,
            'before' => $this->before,
            'after' => $this->after,
            'message' => $this->message,
        ];
    }
}
