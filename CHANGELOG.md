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
