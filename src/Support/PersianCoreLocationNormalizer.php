<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Support;

use Illuminate\Support\Str;
use Zarbin\IranLocations\Contracts\LocationNormalizer;
use Zarbinco\PersianCore\PersianManager;

final class PersianCoreLocationNormalizer implements LocationNormalizer
{
    public function __construct(
        private readonly PersianManager $persian,
    ) {}

    public function display(string $value): string
    {
        return $this->persian->normalize($value)->forDisplay();
    }

    public function search(string $value): string
    {
        return $this->persian->search($value)->normalize();
    }

    public function slug(string $value): string
    {
        $normalized = $this->search($value);
        $slug = Str::slug($normalized);

        if ($slug !== '') {
            return $slug;
        }

        return (string) Str::of($normalized)
            ->replaceMatches('/\s+/u', '-')
            ->trim('-');
    }
}
