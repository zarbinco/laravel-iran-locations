# Release Checklist

Use this checklist before manually tagging a reviewed release.

## Local Gate

- Start from a clean working tree.
- Confirm the package development PHP installation has the `zip` extension enabled; archive hygiene tooling requires `ext-zip`, but package runtime consumers do not.
- Run `composer validate --strict`.
- Run `composer test`.
- Run `vendor/bin/phpunit tests/Unit/LocationDataQualityTest.php`.
- Run `composer run-script format:test`.
- Run `composer analyse`.
- Run `composer run-script test:ci`.
- Run `composer run-script release:check`.
- Optionally run `bash tools/release-check.sh` from Git Bash or a compatible shell; it wraps the Composer release gate.

## Archive Hygiene

- Confirm `tools/check-archive.php <archive.zip>` passes for the Composer archive.
- Confirm no `_review/`, `.phpunit.cache/`, `.phpunit.result.cache`, `REVIEW_NOTES.md`, nested zip/tar archive, `vendor/`, `node_modules/`, `coverage/`, `artifacts/`, `_source/`, build, dist, or release directory is included in the archive.
- Confirm `.gitattributes` export-ignore rules cover private, cache, build, release, review, dependency, and generated archive paths.
- Confirm `.gitignore` keeps local caches, review folders, dependency folders, and generated archives out of the working tree.
- Confirm no local/private paths, drive-letter development paths, mounted temp paths, or local stack names are present in release files.

## Package Contract

- Confirm `config/iran-locations.php`, `README.md`, `docs/data.md`, and the data manifest all agree on data version and dataset counts.
- Confirm packaged JSON codes and manifest `code_scheme` use the generated short package code contract, with no legacy pre-release code compatibility layer.
- Confirm [DATA-SOURCES.md](../DATA-SOURCES.md) and [DATA-LICENSE.md](../DATA-LICENSE.md) describe the imported/curated pre-release data without overclaiming official or legal approval.
- Confirm packaged data quality tests pass for manifest counts, checksums, references, Persian `ک/ی`, province capitals, duplicate-code guards, and documented duplicate neighborhood names.
- Confirm `zarbinco/laravel-persian-core` uses a stable semver constraint.
- Confirm runtime dependencies do not include development-only extensions such as `ext-zip`.
- Confirm the default storage driver remains `database`.
- Confirm `IRAN_LOCATIONS_DRIVER=json` supports read-only no-migration lists, options, search, status, doctor, and Blade components.
- Confirm JSON mode blocks sync and does not register admin routes.
- Confirm admin and API routes are disabled by default.
- Confirm admin routes are protected by application auth middleware and the configured `admin.gate` when enabled.
- Confirm package-owned admin mutations remain blocked by default.
- Confirm public API middleware is deliberate, preferably including `api` middleware and throttling/auth when exposed publicly.
- Confirm nested API route parents hide inactive/deprecated records and conflicting nested filters return `422`.

## Consumer Smoke

- Run the [consumer smoke test](consumer-smoke-test.md) in a fresh Laravel application outside this repository.
- Confirm config, migration, and view publishing work.
- Run `php artisan iran-locations:doctor`.
- Run `php artisan iran-locations:sync --dry-run`.
- Run `php artisan iran-locations:sync`.
- Run `php artisan iran-locations:status`.
- In a separate no-migration smoke check, set `IRAN_LOCATIONS_DRIVER=json` and confirm `doctor`, `status`, select components, and read-only API options work without publishing migrations or running sync.
- Confirm route registration when admin/API are deliberately enabled in the smoke app.

## Manual Release Decision

- Choose the tag name only after review.
- Create the Git tag manually only after maintainer approval.
- Create any GitHub release manually only after maintainer approval.
- Do not publish from CI; CI should only validate tests, static analysis, formatting, archive hygiene, and release-gate commands.
- Do not add deployment secrets, Packagist publishing automation, or release publishing automation to this package workflow.
