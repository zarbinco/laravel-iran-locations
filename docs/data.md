# Data

Laravel Iran Locations ships versioned JSON data under the package `data/` directory. The manifest records the data version, country code, checksums, dataset presence, and expected counts.

The current data version is `0.2.0-dev`. Treat this as private pre-release package data, not as a stable package tag. Package version and data version are separate concepts: future package releases may contain the same data version, and future data refreshes may happen without changing every package API surface.

## Current Packaged Data

- Provinces: 31
- Counties: 484
- Official districts: 1087
- Rural districts: 73
- Cities: 1456
- City regions: 22
- City areas: 0
- Neighborhoods: 568
- Neighborhood-region mappings: 568
- Aliases: 0

The packaged data is generated from spreadsheet source files. The official hierarchy is province, county, official district, city, and rural district. Municipal data remains separate: city regions, city areas, and neighborhoods. Official divisions and municipal locations are available through the schema, models, builders, sync, admin UI, API, and Blade components. City areas and aliases are structurally supported, but packaged data version `0.2.0-dev` does not populate them by default.

## Scope And Limitations

The package does not currently include village, boundary, latitude/longitude, postal-code, routing, or always-current official gazette data unless a release explicitly documents that scope. Treat the data as versioned package data, not as automatically complete, official, current national coverage.

Verify source assumptions, completeness, and licensing suitability before using it in production, legal, regulatory, logistics, or high-stakes workflows.

Before release, verify manifest counts, checksums, Composer archive contents, and consumer smoke install results.

## Package And Custom Records

Package-owned records use `source = package` and stable `code` values. Application records should use `source = custom`. The sync engine always preserves custom records and does not overwrite them.

By default, admin direct edits and delete/deprecate actions for package-owned records are blocked by `data.allow_package_record_direct_edit = false`. Enable that option only when you deliberately need to override package-owned records during private testing or release preparation.

## Source Metadata

Records include package lifecycle fields such as `source`, `source_version`, `data_version`, `is_active`, and `deprecated_at`. These fields make it possible to track package data separately from application data.
Package data is required to contain normalized/searchable fields. Sync writes those normalized fields and fills missing normalized or slug fields through the configured `LocationNormalizer`. Sync normalization is part of the package data contract and is not user-toggleable. Model save-time normalization for application/admin writes remains controlled by `normalization.on_save`.

Neighborhood-region mappings also carry lifecycle fields: `is_active`, `source`, `source_version`, `data_version`, and `deprecated_at`. Stale package-owned mappings are deprecated during sync; custom mappings are preserved. Normal `Neighborhood::regions()` and `CityRegion::neighborhoods()` relationships return active, non-deprecated mappings by default. Use `allRegions()` or `allNeighborhoods()` to inspect inactive or deprecated mapping rows.

## Alias Location Types

Alias records use stable public location type keys, not PHP class names. Supported keys are `province`, `county`, `official_district`, `rural_district`, `city`, `city_region`, `city_area`, and `neighborhood`. Package data validation also accepts plural dataset-style names such as `cities` and normalizes them to the stored singular key.

The alias lifecycle columns are `is_active`, `source`, `source_version`, `data_version`, and `deprecated_at`. Alias rows are unique per `location_type`, `location_id`, and `normalized_alias`. Aliases do not have a replacement pointer; alias deprecation does not use `replaced_by_id`.

Stale package-owned aliases are deprecated during sync. Custom aliases are preserved. Normal location search consumes active, non-deprecated aliases only. Use `activeAliases()` for lifecycle-filtered alias access and `aliases()` when an admin or maintenance workflow needs the full alias relationship.

Data-version rows store the latest applied state for each `data_version` and checksum pair. Missing package checksums are persisted as an empty string so the uniqueness contract remains enforceable across databases.
