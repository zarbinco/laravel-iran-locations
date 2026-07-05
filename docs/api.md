# API

The optional API is disabled by default and read-only.
Responses expose versioned package data. Treat that data as package-maintained location data, not automatically complete, official, current national coverage.

## Enable

```php
'api' => [
    'enabled' => true,
    'prefix' => 'iran-locations/api',
    'middleware' => ['api'],
],
```

Route names use `iran-locations.api.*`.
The API remains disabled by default. If exposing it publicly, configure middleware deliberately, for example `['api', 'throttle:60,1']` or your application's own throttle/auth stack.
With `IRAN_LOCATIONS_DRIVER=json`, read/list/options/search/status endpoints read packaged JSON directly and do not require migrations or sync. Admin/write behavior is not part of JSON mode.

## Endpoints

- `GET /status`
- `GET /search?q=...`
- `GET /provinces`
- `GET /provinces/{province}/counties`
- `GET /provinces/{province}/cities`
- `GET /counties`
- `GET /counties/{county}/official-districts`
- `GET /counties/{county}/cities`
- `GET /counties/{county}/rural-districts`
- `GET /official-districts`
- `GET /official-districts/{officialDistrict}/cities`
- `GET /official-districts/{officialDistrict}/rural-districts`
- `GET /rural-districts`
- `GET /cities`
- `GET /cities/{city}/regions`
- `GET /cities/{city}/areas`
- `GET /cities/{city}/neighborhoods`
- `GET /city-regions`
- `GET /city-regions/{region}/areas`
- `GET /city-regions/{region}/neighborhoods`
- `GET /city-areas`
- `GET /city-areas/{area}/neighborhoods`
- `GET /neighborhoods`
- `GET /aliases`
- `GET /options/provinces`
- `GET /options/counties`
- `GET /options/official-districts`
- `GET /options/rural-districts`
- `GET /options/cities`
- `GET /options/city-regions`
- `GET /options/city-areas`
- `GET /options/neighborhoods`

Route parameters can resolve records by id, code, or slug where practical in database mode. JSON mode uses public codes for route parents and option values. Nested route parents resolve active, non-deprecated records by default, so inactive or deprecated parents return `404` even though list endpoints can still use `status=all` when that endpoint supports lifecycle filtering.

## Filters

List endpoints support builder-backed filters such as `q`, `status`, `source`, `code`, `slug`, `sort`, `province_id`, `province_code`, `county_id`, `county_code`, `official_district_id`, `official_district_code`, `city_id`, `city_code`, `region_id`, `region_code`, `area_id`, `area_code`, `type`, `has_neighborhoods`, and `has_areas` where relevant. JSON mode supports the code-based filters and lifecycle/source/search filters; database ID filters are database-driver only and return `422` with a suggestion to use the corresponding code filter.

When `q` is present, HTTP request validation enforces the configured `search.min_length`. The grouped `/search` endpoint requires `q`; list, option, and alias endpoints allow it to be omitted.

Pagination uses `per_page` and `page` with the configured maximum.
Integer ID filters reject non-integer and negative values.

Nested endpoints reject conflicting parent filters instead of silently overriding them. Matching duplicate filters are allowed, but a conflicting filter returns `422` with field errors:

```http
GET /iran-locations/api/provinces/p.01/cities?province_code=p.02
```

```json
{
  "message": "The selected parent filter conflicts with the route parent.",
  "errors": {
    "province_code": [
      "The selected province_code conflicts with the route parent."
    ]
  }
}
```

`GET /aliases` accepts `location_type` as one of the stable public keys: `province`, `county`, `official_district`, `rural_district`, `city`, `city_region`, `city_area`, or `neighborhood`. Responses return that same stable key. Class names and unsupported type strings are rejected by request validation.

`GET /aliases` defaults to active aliases. Use `status=active`, `status=inactive`, `status=deprecated`, or `status=all` to control lifecycle filtering. Alias resources expose `is_active`, `deprecated_at`, `source_version`, and `data_version` so consumers can inspect package lifecycle state.

## Search

```http
GET /iran-locations/api/search?q=تهران
```

Search returns grouped results:

```json
{
  "query": "تهران",
  "results": {
    "provinces": [],
    "counties": [],
    "official_districts": [],
    "rural_districts": [],
    "cities": [],
    "city_regions": [],
    "city_areas": [],
    "neighborhoods": []
  }
}
```

Search terms are normalized through the configured `LocationNormalizer`. Alias-backed search only consumes active, non-deprecated aliases. Deprecated aliases may still be visible through `/aliases` when an explicit lifecycle status requests them.

Normal neighborhood-region API and builder consumption uses active mappings by default. The Eloquent models expose `allRegions()` and `allNeighborhoods()` for code that needs to inspect inactive or deprecated mappings directly.

## Options

Option endpoints return small dropdown-friendly arrays:

```json
[
  {
    "value": "p.01",
    "code": "p.01",
    "label": "Tehran",
    "name_fa": "Tehran"
  }
]
```

