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

Successful syncs write a data-version record with the package data version, checksum, and summary.
Repeated successful syncs for the same `data_version` and checksum update that row with the latest summary and timestamps instead of creating duplicate data-version rows. Missing checksums are persisted as an empty string so the latest-state uniqueness contract remains enforceable.

## Safety Behavior

- Tables are never truncated.
- Package-owned records are matched by stable `code`.
- Custom records with `source = custom` are always preserved; this is not configurable.
- Package data is required to contain normalized/searchable fields. Sync writes those normalized fields and fills missing normalized or slug fields through the configured `LocationNormalizer`.
- Missing package-owned records are deprecated by default.
- Hard delete behavior is rejected by the sync service.
- Empty non-authoritative datasets are not used to deprecate records during default full sync.
- Alias sync stores stable location type keys and package lifecycle fields. Alias deprecation uses `is_active` and `deprecated_at`; aliases do not use `replaced_by_id`. It does not perform full stale alias cleanup yet; broader alias and neighborhood-region stale policy is reserved for a later phase.

`package_record_delete_behavior` supports the safe `deprecate` behavior. Configuring hard delete is intentionally rejected by the sync service. Admin direct mutation of package-owned records is controlled separately by `data.allow_package_record_direct_edit`.

`normalization.on_sync` was removed because sync normalization is part of the package data contract and is not a runtime toggle. `normalization.on_save` still controls model save-time normalization for application/admin writes. If a future phase recomputes normalized fields during sync, that should be implemented explicitly and tested separately.

## Useful Commands

```bash
php artisan iran-locations:status
php artisan iran-locations:doctor
php artisan iran-locations:install
php artisan iran-locations:install --sync
```

`install` is non-mutating by default. Use `--sync` only when you want installation to apply package data after publishing assets.

## Options

Use `--dataset` to sync selected datasets. Use `--no-deprecate` to preserve missing package records during a sync. Use `--force` only when you intentionally want package sync to manage records with an unexpected source.
