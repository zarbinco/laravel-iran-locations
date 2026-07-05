# Blade Components

The package registers plain Blade select components under the `iran-locations` namespace.
Components use the configured read repository, so code-based selects work in both database mode and read-only JSON mode.

## Examples

```blade
<x-iran-locations::province-select name="province_code" />
<x-iran-locations::county-select name="county_code" province-code="p.01" />
<x-iran-locations::official-district-select name="official_district_code" county-code="c.01.01" />
<x-iran-locations::rural-district-select name="rural_district_code" official-district-code="b.01.01.01" />
<x-iran-locations::city-select name="city_code" province-code="p.01" />
<x-iran-locations::city-region-select name="city_region_code" city-code="s.01.01.01.01" />
<x-iran-locations::city-area-select name="city_area_code" city-region-code="r.01.01.01.01.05" />
<x-iran-locations::neighborhood-select name="neighborhood_code" city-code="s.01.01.01.01" />
```

## Common Props

- `name`
- `selected`
- `placeholder`
- `disabled`
- `required`
- `class` and other normal Blade attributes

Components use `old($name, $selected)` style behavior for selected values.
Selected values and rendered option values are stable public location codes.

## Parent Filters

- County select: `provinceId`, `provinceCode`
- Official district select: `provinceId`, `provinceCode`, `countyId`, `countyCode`
- Rural district select: `provinceId`, `provinceCode`, `countyId`, `countyCode`, `officialDistrictId`, `officialDistrictCode`
- City select: `provinceId`, `provinceCode`, `countyId`, `countyCode`, `officialDistrictId`, `officialDistrictCode`
- City region select: `provinceId`, `provinceCode`, `countyId`, `countyCode`, `officialDistrictId`, `officialDistrictCode`, `cityId`, `cityCode`
- City area select: `provinceId`, `provinceCode`, `countyId`, `countyCode`, `officialDistrictId`, `officialDistrictCode`, `cityRegionId`, `cityRegionCode`, `regionId`, `regionCode`, `cityId`, `cityCode`
- Neighborhood select: `provinceId`, `provinceCode`, `countyId`, `countyCode`, `officialDistrictId`, `officialDistrictCode`, `cityId`, `cityCode`, `cityRegionId`, `cityRegionCode`, `regionId`, `regionCode`, `cityAreaId`, `cityAreaCode`, `areaId`, `areaCode`, `type`

The official division components follow the province, county, official district, and rural district hierarchy. Municipal components remain separate. The components require no JavaScript and use active, non-deprecated records by default.
Code props such as `provinceCode`, `cityCode`, `cityRegionCode`, and `cityAreaCode` work in JSON mode without database tables. ID props such as `provinceId` and `cityId` are database-driver conveniences; in JSON mode, passing only ID parent props returns no options instead of broad unfiltered options.
