# Extending

Laravel Iran Locations is designed to fit normal Laravel applications without forcing a fixed database structure.

## Models

Override model classes in `config/iran-locations.php`:

```php
'models' => [
    'province' => App\Models\Province::class,
],
```

Custom models should extend the package model or preserve the same relationships and fillable fields.

## Tables

Override table names in config:

```php
'tables' => [
    'province' => 'app_provinces',
],
```

Relationship and sync code uses configured table names through the package resolver.

## Route Keys

The `route_key` config supports `id`, `code`, and `slug`. Unsupported values fall back to `id`.

## Custom Records

Application-owned records should use `source = custom`. The sync engine preserves custom records and does not overwrite same-code custom records.

## Aliases

Aliases are polymorphic and available on provinces, cities, city regions, city areas, and neighborhoods. Alias normalization uses the configured `LocationNormalizer` contract.

## Normalization Contract

Bind your own implementation of `Zarbin\IranLocations\Contracts\LocationNormalizer` if you need different display, search, or slug normalization behavior. Do not duplicate Persian normalization maps in application code when the Persian Core adapter already provides the behavior.
