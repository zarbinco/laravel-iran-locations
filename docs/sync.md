# Sync

The sync engine imports packaged JSON data into your application database through configured models and table names.

Default sync order follows data dependencies: provinces, counties, official districts, rural districts, cities, municipal regions and areas, neighborhoods, neighborhood-region mappings, and aliases.

## Dry Run

Always inspect a sync first:

```bash
php artisan iran-locations:sync --dry-run
```

Dry-run mode validates package data, resolves dependencies, reports creates, updates, skips, and deprecations, and does not write database records.

## Apply Sync

```bash
php artisan iran-locations:sync
```

Successful full syncs write a data-version record with the package data version, checksum, and summary.
Repeated successful syncs for the same `data_version` and checksum update that row with the latest summary and timestamps instead of creating duplicate data-version rows. Normal packaged data must include a manifest checksum, and `iran-locations:doctor` validates it against the packaged JSON data.

## Safety Behavior

- Tables are never truncated.
- Package-owned records are matched by stable generated `code` values. Source spreadsheet code-like values are not used as public package codes.
- Custom records with `source = custom` are always preserved; this is not configurable.
- Package data is required to contain normalized/searchable fields. Sync writes those normalized fields and fills missing normalized or slug fields through the configured `LocationNormalizer`.
- Missing package-owned records, aliases, and neighborhood-region mappings are deprecated by default.
- Custom records, aliases, and neighborhood-region mappings are always preserved.
- Hard delete behavior is rejected by the sync service.
- Empty non-authoritative datasets are not used to deprecate records during default full sync.
- Alias sync stores stable location type keys and package lifecycle fields. Alias deprecation uses `is_active` and `deprecated_at`; aliases do not use `replaced_by_id`.
- Neighborhood-region mappings have lifecycle fields and stale package mappings are deprecated, not deleted.
- Deprecated aliases are not used by normal active location search.
- Normal neighborhood-region relationships consume active, non-deprecated mappings. Use `allRegions()` and `allNeighborhoods()` when maintenance code needs inactive or deprecated mapping rows.
- Explicit `--only=<dataset>` with an empty dataset is treated as intentional and may deprecate stale package-owned rows for that dataset.

`package_record_delete_behavior` supports the safe `deprecate` behavior. Configuring hard delete is intentionally rejected by the sync service. Admin direct mutation of package-owned records is controlled separately by `data.allow_package_record_direct_edit`.

Sync normalization is part of the package data contract and has no runtime config toggle. `normalization.on_save` still controls model save-time normalization for application/admin writes. Future changes to sync-time normalized fields should be implemented explicitly and covered by tests.

## Useful Commands

```bash
php artisan iran-locations:status
php artisan iran-locations:doctor
php artisan iran-locations:install
php artisan iran-locations:install --sync
```

`install` is non-mutating by default. Use `--sync` only when you want installation to apply package data after publishing assets.

## Options

Use `--only` to sync selected datasets. Partial syncs apply only the selected datasets and do not mark the global package data version as fully applied. Use `--no-deprecate` to preserve missing package records during a sync. Use `--force` only when you intentionally want package sync to manage records with an unexpected source.

Use `--chunk` to control how many package data records are processed per sync batch:

```bash
php artisan iran-locations:sync --chunk=100
```

The current JSON repository still loads package data from local JSON files before sync processing starts. Chunking controls processing batches for normal datasets, aliases, and neighborhood-region mappings; it is not streaming file I/O.
