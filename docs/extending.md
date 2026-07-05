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

Application-owned records should use `source = custom`. The sync engine always preserves custom records and does not overwrite same-code custom records.

The admin UI rejects `source = package` input and blocks package-owned record mutation unless `data.allow_package_record_direct_edit` is deliberately enabled.

Package data is versioned package data, not automatically complete, official, current national coverage. Verify source assumptions and licensing suitability before production or high-stakes use.

## Aliases

Aliases are polymorphic and available on provinces, counties, official districts, rural districts, cities, city regions, city areas, and neighborhoods. Alias normalization uses the configured `LocationNormalizer` contract.
The package stores alias `location_type` values as stable morph-map keys: `province`, `county`, `official_district`, `rural_district`, `city`, `city_region`, `city_area`, and `neighborhood`.

The service provider registers those keys with Eloquent using the configured model classes and merges the map with any existing application morph map. It does not require a global enforced morph map. If you override model classes, the alias morph map points to your configured models.

## Normalization Contract

Bind your own implementation of `Zarbin\IranLocations\Contracts\LocationNormalizer` if you need different display, search, or slug normalization behavior. Do not duplicate Persian normalization maps in application code when the Persian Core adapter already provides the behavior.
Package data is required to contain normalized/searchable fields. Sync writes those normalized fields and fills missing normalized or slug fields through the configured normalizer. Model save-time normalization can be disabled with `normalization.on_save` for application writes, but sync normalization is not optional.
