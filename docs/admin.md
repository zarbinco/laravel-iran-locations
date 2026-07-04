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

## Authorization

Set `iran-locations.admin.gate` to a Laravel gate name if you want package-level authorization. A null gate allows access when the configured middleware allows the request.

## Views

Views are loaded from the package and can be published:

```bash
php artisan vendor:publish --tag=iran-locations-views
```

## Safe Destroy

Package-owned records are deprecated instead of deleted. Custom records are deleted when safe, or deactivated if related records prevent deletion.
