<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Data;

use InvalidArgumentException;
use JsonException;
use RuntimeException;
use Zarbin\IranLocations\Contracts\LocationDataRepository;

class JsonLocationDataRepository implements LocationDataRepository
{
    public function __construct(
        private readonly ?string $dataPath = null,
    ) {}

    public function path(): string
    {
        return $this->dataPath ?? dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'data';
    }

    public function manifest(): array
    {
        return $this->readJsonObject(LocationDataManifest::MANIFEST_FILE);
    }

    public function dataVersion(): string
    {
        $version = $this->manifest()['data_version'] ?? null;

        return is_string($version) && $version !== ''
            ? $version
            : (string) config('iran-locations.data.current_version', '0.1.0-dev');
    }

    public function provinces(): array
    {
        return $this->all('provinces');
    }

    public function cities(): array
    {
        return $this->all('cities');
    }

    public function cityRegions(): array
    {
        return $this->all('city_regions');
    }

    public function cityAreas(): array
    {
        return $this->all('city_areas');
    }

    public function neighborhoods(): array
    {
        return $this->all('neighborhoods');
    }

    public function aliases(): array
    {
        return $this->all('aliases');
    }

    public function count(string $dataset): int
    {
        return count($this->all($dataset));
    }

    public function all(string $dataset): array
    {
        try {
            $file = LocationDataManifest::fileFor($dataset);
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage(), previous: $exception);
        }

        return $this->readJsonArray($file);
    }

    /**
     * @return array<string, mixed>
     */
    private function readJsonObject(string $file): array
    {
        $data = $this->readJson($file);

        if (! is_array($data) || array_is_list($data)) {
            throw new RuntimeException("Iran Locations data file [{$file}] must contain a JSON object.");
        }

        return $data;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readJsonArray(string $file): array
    {
        $data = $this->readJson($file);

        if (! is_array($data) || ! array_is_list($data)) {
            throw new RuntimeException("Iran Locations data file [{$file}] must contain a JSON array.");
        }

        foreach ($data as $index => $record) {
            if (! is_array($record)) {
                throw new RuntimeException("Iran Locations data file [{$file}] record [{$index}] must be a JSON object.");
            }
        }

        return $data;
    }

    private function readJson(string $file): mixed
    {
        $path = $this->path().DIRECTORY_SEPARATOR.$file;

        if (! is_file($path)) {
            throw new RuntimeException("Iran Locations data file [{$file}] does not exist.");
        }

        try {
            return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException("Iran Locations data file [{$file}] is not valid JSON: {$exception->getMessage()}", previous: $exception);
        }
    }
}
