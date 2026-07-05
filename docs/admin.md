# Admin UI

The optional admin UI is disabled by default.
Admin routes are available only in the database driver. With `IRAN_LOCATIONS_DRIVER=json`, admin routes are not registered because JSON mode is read-only and has no custom records or database mutations.

## Enable

```php
'admin' => [
    'enabled' => true,
    'prefix' => 'admin/iran-locations',
    'middleware' => ['web', 'auth'],
    'gate' => null,
],
```

Routes are named under `iran-locations.admin.*`.
Admin search inputs that include `q` are validated against `search.min_length`.
Keep these routes behind application auth middleware. The package does not force authentication if you replace the middleware stack, but it does enforce the configured `admin.gate` whenever one is set.

## Managed Records

The admin UI includes screens for provinces, counties, official districts, rural districts, cities, city regions, city areas, neighborhoods, and aliases.

County, official district, and rural district screens use the official administrative hierarchy. City region, city area, and neighborhood screens remain municipal hierarchy screens.

Alias forms and filters use stable location type keys: `province`, `county`, `official_district`, `rural_district`, `city`, `city_region`, `city_area`, and `neighborhood`. Admin requests persist those keys directly and reject unsupported type values such as PHP class names.
Alias mutation requests also validate that the selected target record exists for the submitted stable type key.

Mutation forms reject inconsistent parent selections:

- Official districts require a county from the selected province.
- Rural districts require county, province, and official district parents that belong together.
- Cities require optional county and official district parents to match the selected province and each other.
- Neighborhood default regions and areas must belong to the selected city, and default areas must match the selected default region when one is provided.

## Authorization

Set `iran-locations.admin.gate` to a Laravel gate name if you want package-level authorization. A null gate allows access when the configured middleware allows the request.
When set, the gate is applied centrally to every admin route, including dashboard/data routes and resource mutations.

## Views

Views are loaded from the package and can be published:

```bash
php artisan vendor:publish --tag=iran-locations-views
```

## Safe Destroy

With the default `data.allow_package_record_direct_edit = false`, package-owned records cannot be directly updated, deleted, or deprecated through the admin UI. Admin create and update requests also reject `source = package` while that setting is false.

Source controls match that policy: forms do not offer `Package` as a selectable source while direct edit is disabled. Existing package-owned records show a read-only package-managed source indicator.

Custom records remain editable. Custom records are deleted when safe, or deactivated if related records prevent deletion.

Set `data.allow_package_record_direct_edit` to `true` only when you deliberately need to override package-owned records during private testing or release preparation. When enabled, package-owned records are deprecated instead of deleted where the model supports deprecation.

The admin UI is for application maintenance and custom records. Package-owned data updates should normally flow through the sync command.
