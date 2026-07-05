# Changelog

All notable changes to `zarbinco/laravel-iran-locations` will be documented in this file.

## 0.2.0-dev - unreleased

This is a private pre-release baseline, not a stable public `1.0.0` release.

### Versioning Notes

- Package version and data version are related but separate concepts.
- The current package baseline documents packaged data version `0.2.0-dev`.
- Future package releases may ship the same data version, and future data refreshes may happen without changing every package API surface.

### Added

- Added versioned Iran province, county, official district, rural district, city, Tehran city region, and Tehran neighborhood-style data.
- Added Persian Core-backed normalization.
- Added configurable models, tables, route keys, and relationships.
- Added safe database sync with dry-run, deprecation, custom record preservation, and data-version tracking.
- Added query builders, filters, and alias-aware search.
- Added optional admin UI, optional read-only API, and Blade select components.

### Changed

- Enforced `search.min_length` through API and admin HTTP request validation.
- Enforced package-owned admin edit/delete protection through `data.allow_package_record_direct_edit`.
- Removed misleading config keys that did not represent real runtime toggles: `normalization.on_sync` and `data.preserve_custom_records`.
- Changed alias `location_type` storage, admin/API filters, resources, and sync payloads to use stable public morph-map keys instead of PHP class names.
- Changed data-version sync tracking so repeated successful syncs for the same data version and checksum update the existing row instead of appending duplicates.
- Clarified alias deprecation lifecycle so aliases do not persist `replaced_by_id`, and normalized missing data-version checksums to an empty string for database-enforced idempotence.
- Made sync `--chunk` an active record-processing batch option for model datasets, aliases, and neighborhood-region mappings.
- Added lifecycle fields and stale deprecation handling for package-owned neighborhood-region mappings.
- Added stale deprecation handling for package-owned aliases while preserving custom aliases and mappings.
- Changed active search to ignore inactive and deprecated aliases.
- Changed normal neighborhood-region relationships to return active, non-deprecated mappings by default, with all-mapping helpers for maintenance access.
- Changed the alias API to default to active aliases, expose lifecycle fields, and support `status=active|inactive|deprecated|all`.
- Normalized packaged Persian display data from Arabic `ك/ي` to Persian `ک/ی`.
- Populated 31 province capital city flags and guarded them with data-quality tests.
- Added permanent packaged-data quality coverage for manifest checksums, references, duplicate codes, Persian display fields, and documented duplicate neighborhood names.
- Centralized admin gate enforcement so every admin route consistently applies the configured `admin.gate`.
- Added admin parent hierarchy validation for official districts, rural districts, cities, neighborhoods, and related ID fields.
- Added admin alias target validation against stable type keys and real target records.
- Changed the default API middleware stack to `['api']` while keeping API routes disabled by default.
- Made nested API route parent resolution active-safe so inactive or deprecated parents return `404`.
- Changed nested API endpoints to return `422` for conflicting parent filters instead of silently overriding them.
- Tightened admin and API ID filter validation so negative IDs fail validation.
