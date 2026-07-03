<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Contracts;

interface LocationNormalizer
{
    public function display(string $value): string;

    public function search(string $value): string;

    public function slug(string $value): string;
}
