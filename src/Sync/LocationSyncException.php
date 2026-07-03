<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Sync;

use RuntimeException;

class LocationSyncException extends RuntimeException
{
    /**
     * @param  array<int, string>  $errors
     */
    public static function validationFailed(array $errors): self
    {
        return new self('Iran Locations package data validation failed: '.implode('; ', $errors));
    }
}
