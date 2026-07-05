# Contributing

Thank you for helping improve Laravel Iran Locations.

## Setup

```bash
composer install
```

## Checks

Run the same checks before opening a pull request:

```bash
composer test
composer run-script test:ci
composer run-script format:test
composer analyse
composer validate --strict
composer run-script release:check
```

Use `composer format` to apply Pint formatting.
The release/archive gate requires the PHP `zip` extension in development checkouts.

## Data Updates

Data updates should preserve stable codes where possible, update the manifest counts and checksum, and include tests that prove the expected package counts. Do not include raw source dumps in package archives.
Run the data quality tests when changing packaged data so counts, checksums, Persian display fields, references, duplicate-code guards, and province-capital flags stay accurate.

## Normalization

Persian display, search, slug, and alias normalization should continue to flow through the `LocationNormalizer` contract and Persian Core adapter. Do not add local Persian character replacement maps.
