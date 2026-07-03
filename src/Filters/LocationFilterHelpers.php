<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Filters;

final class LocationFilterHelpers
{
    public static function filled(mixed $value): bool
    {
        return ! self::blank($value);
    }

    public static function blank(mixed $value): bool
    {
        return $value === null || (is_string($value) && trim($value) === '');
    }

    public static function string(mixed $value): ?string
    {
        if (self::blank($value)) {
            return null;
        }

        return is_string($value) || is_numeric($value)
            ? trim((string) $value)
            : null;
    }

    public static function boolean(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return match ($value) {
                1 => true,
                0 => false,
                default => null,
            };
        }

        if (! is_string($value)) {
            return null;
        }

        return match (strtolower(trim($value))) {
            '1', 'true', 'yes', 'on' => true,
            '0', 'false', 'no', 'off' => false,
            default => null,
        };
    }
}
