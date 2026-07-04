# Blade Components

The package registers plain Blade select components under the `iran-locations` namespace.

## Examples

```blade
<x-iran-locations::province-select name="province_id" />
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

- City select: `provinceId`, `provinceCode`
- City region select: `cityId`, `cityCode`
- City area select: `cityRegionId`, `cityRegionCode`, `regionId`, `regionCode`, `cityId`, `cityCode`
- Neighborhood select: `cityId`, `cityCode`, `cityRegionId`, `cityRegionCode`, `regionId`, `regionCode`, `cityAreaId`, `cityAreaCode`, `areaId`, `areaCode`, `type`

The components require no JavaScript and use active, non-deprecated records by default.
