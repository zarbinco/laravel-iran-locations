# API

The optional API is disabled by default and read-only.
Responses expose versioned package data. Treat that data as package-maintained location data, not automatically complete, official, current national coverage.

## Enable

```php
'api' => [
    'enabled' => true,
    'prefix' => 'iran-locations/api',
    'middleware' => ['web'],
],
```

Route names use `iran-locations.api.*`.

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

Route parameters can resolve records by id, code, or slug where practical.

## Filters

List endpoints support builder-backed filters such as `q`, `status`, `source`, `code`, `slug`, `sort`, `province_id`, `province_code`, `county_id`, `county_code`, `official_district_id`, `official_district_code`, `city_id`, `city_code`, `region_id`, `region_code`, `area_id`, `area_code`, `type`, `has_neighborhoods`, and `has_areas` where relevant.

When `q` is present, HTTP request validation enforces the configured `search.min_length`. The grouped `/search` endpoint requires `q`; list, option, and alias endpoints allow it to be omitted.

Pagination uses `per_page` and `page` with the configured maximum.

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

Search terms are normalized through the configured `LocationNormalizer`.

## Options

Option endpoints return small dropdown-friendly arrays:

```json
[
  {
    "value": 1,
    "code": "ir.province.001",
    "label": "Tehran",
    "name_fa": "Tehran"
  }
]
```
