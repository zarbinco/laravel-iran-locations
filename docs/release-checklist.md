# Release Checklist

- Run `composer validate --strict`.
- Run `composer test`.
- Run `composer run-script format:test`.
- Run `composer analyse`.
- Confirm no raw SQL source files are included in the package archive.
- Confirm no generated zip files are included in the package archive.
- Confirm no `vendor/` or `node_modules/` directories are included.
- Confirm no `.env`, secrets, logs, caches, or coverage output are included.
- Confirm `zarbinco/laravel-persian-core` uses a stable semver constraint.
- Confirm Packagist metadata is correct.
- Confirm README examples match the current API.
- Confirm config publishing works.
- Confirm migration publishing works.
- Confirm view publishing works.
- Confirm admin and API routes are disabled by default.
- Run the [consumer smoke test](consumer-smoke-test.md) in a fresh Laravel app.
- Run `php artisan iran-locations:sync --dry-run` in a test app.
- Confirm safe sync behavior before applying data.
