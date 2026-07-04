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
composer run-script format:test
composer analyse
composer validate --strict
```

Use `composer format` to apply Pint formatting.

## Data Updates

Data updates should preserve stable codes where possible, update the manifest counts and checksum, and include tests that prove the expected package counts. Do not include raw source dumps in package archives.

## Normalization

Persian display, search, slug, and alias normalization should continue to flow through the `LocationNormalizer` contract and Persian Core adapter. Do not add local Persian character replacement maps.
