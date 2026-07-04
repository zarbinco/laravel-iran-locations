# Blade Components

The package registers plain Blade select components under the `iran-locations` namespace.

## Examples

```blade
<x-iran-locations::province-select name="province_id" />
<x-iran-locations::county-select name="county_id" :province-id="$provinceId" />
<x-iran-locations::official-district-select name="official_district_id" :county-id="$countyId" />
<x-iran-locations::rural-district-select name="rural_district_id" :official-district-id="$officialDistrictId" />
<x-iran-locations::city-select name="city_id" :province-id="$provinceId" />
<x-iran-locations::city-region-select name="city_region_id" :city-id="$cityId" />
<x-iran-locations::city-area-select name="city_area_id" :city-region-id="$regionId" />
<x-iran-locations::neighborhood-select name="neighborhood_id" :city-id="$cityId" />
```

## Common Props

- `name`
- `selected`
- `placeholder`
- `disabled`
- `required`
- `class` and other normal Blade attributes

Components use `old($name, $selected)` style behavior for selected values.

## Parent Filters

- County select: `provinceId`, `provinceCode`
- Official district select: `provinceId`, `provinceCode`, `countyId`, `countyCode`
- Rural district select: `provinceId`, `provinceCode`, `countyId`, `countyCode`, `officialDistrictId`, `officialDistrictCode`
- City select: `provinceId`, `provinceCode`, `countyId`, `countyCode`, `officialDistrictId`, `officialDistrictCode`
- City region select: `cityId`, `cityCode`
- City area select: `cityRegionId`, `cityRegionCode`, `regionId`, `regionCode`, `cityId`, `cityCode`
- Neighborhood select: `cityId`, `cityCode`, `cityRegionId`, `cityRegionCode`, `regionId`, `regionCode`, `cityAreaId`, `cityAreaCode`, `areaId`, `areaCode`, `type`

The official division components follow the province, county, official district, and rural district hierarchy. Municipal components remain separate. The components require no JavaScript and use active, non-deprecated records by default.
