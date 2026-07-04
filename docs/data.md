# Data

Laravel Iran Locations ships versioned JSON data under the package `data/` directory. The manifest records the data version, country code, checksums, dataset presence, and expected counts.

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

The packaged data is generated from spreadsheet source files. The official hierarchy is province, county, official district, city, and rural district. Municipal data remains separate: city regions, city areas, and neighborhoods. Official divisions and municipal locations are available through the schema, models, builders, sync, admin UI, API, and Blade components. City areas are supported by those surfaces, but the packaged dataset does not populate them by default.

## Scope And Limitations

The package does not currently include village, boundary, or geo-coordinate data. It should not be treated as an always-current official administrative database without verification.

Review the data and its suitability for your application before using it in legal, regulatory, logistics, or high-stakes workflows.

## Package And Custom Records

Package-owned records use `source = package` and stable `code` values. Application records should use `source = custom`. The sync engine preserves custom records and does not overwrite them.

## Source Metadata

Records include package lifecycle fields such as `source`, `source_version`, `data_version`, `is_active`, and `deprecated_at`. These fields make it possible to track package data separately from application data.
