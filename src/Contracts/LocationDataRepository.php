<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Contracts;

interface LocationDataRepository
{
    /**
     * @return array<string, mixed>
     */
    public function manifest(): array;

    public function dataVersion(): string;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function provinces(): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function counties(): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function officialDistricts(): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function ruralDistricts(): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function cities(): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function cityRegions(): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function cityAreas(): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function neighborhoods(): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function neighborhoodRegion(): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function aliases(): array;

    public function count(string $dataset): int;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(string $dataset): array;
}
