# Admin UI

The optional admin UI is disabled by default.

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

## Managed Records

The admin UI includes screens for provinces, counties, official districts, rural districts, cities, city regions, city areas, neighborhoods, and aliases.

County, official district, and rural district screens use the official administrative hierarchy. City region, city area, and neighborhood screens remain municipal hierarchy screens.

## Authorization

Set `iran-locations.admin.gate` to a Laravel gate name if you want package-level authorization. A null gate allows access when the configured middleware allows the request.

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
