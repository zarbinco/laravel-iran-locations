# Data

Laravel Iran Locations ships versioned JSON data under the package `data/` directory. The manifest records the data version, country code, checksums, dataset presence, and expected counts.

## Current Packaged Data

- Provinces: 31
- Cities: 1226
- Neighborhoods: 505
- City regions: 0
- City areas: 0
- Aliases: 0

Neighborhood records are neighborhood or urban-place style records from the packaged dataset. City regions and city areas are supported in the schema, models, builders, admin UI, API, and components, but the packaged dataset does not populate them by default.

## Scope And Limitations

The package does not currently include complete official county, bakhsh, rural district, village, boundary, or geo data. It should not be treated as an always-current official administrative database without verification.

Review the data and its suitability for your application before using it in legal, regulatory, logistics, or high-stakes workflows.

## Package And Custom Records

Package-owned records use `source = package` and stable `code` values. Application records should use `source = custom`. The sync engine preserves custom records and does not overwrite them.

## Source Metadata

Records include package lifecycle fields such as `source`, `source_version`, `data_version`, `is_active`, and `deprecated_at`. These fields make it possible to track package data separately from application data.
