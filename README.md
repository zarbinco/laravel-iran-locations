# Laravel Iran Locations

زبان‌ها: فارسی | [English](README.en.md)

این پکیج داده‌های مکانی ایران را برای پروژه‌های Laravel آماده می‌کند؛ از استان و شهرستان تا بخش، دهستان، شهر، منطقه شهری و محله. اگر فقط لیست آماده برای select، API خواندنی یا اعتبارسنجی می‌خواهید، می‌توانید از `JSON driver` استفاده کنید و هیچ migration یا sync اجرا نکنید. اگر لازم دارید داده‌ها وارد دیتابیس شوند، رکورد سفارشی داشته باشید، admin CRUD فعال کنید یا با رابطه‌های Eloquent کار کنید، `database driver` برای همین سناریو است.

> وضعیت انتشار: پکیج هنوز منتشر نشده و برای فاز beta/pre-release آماده می‌شود. نسخه داده فعلی `0.2.0-dev` است. برای release پایدار، وضعیت منبع داده، مجوز بازنشر و کامل‌بودن داده‌ها باید جداگانه بررسی شود.

## قابلیت‌ها

- داده‌های آماده برای استان، شهرستان، بخش، دهستان، شهر، مناطق شهری تهران و محله‌های تهران
- دو حالت ذخیره‌سازی: `database` برای sync و رکوردهای سفارشی، و `json` برای استفاده read-only بدون migration
- کدهای عمومی و پایدار مثل `p.01` و `s.01.01.01.01` به‌جای تکیه بر id دیتابیس
- نرمال‌سازی متن فارسی با `zarbinco/laravel-persian-core`
- sync امن دیتابیس با `--dry-run`، حفظ رکوردهای custom و منسوخ‌کردن رکوردهای package که از داده جدید حذف شده‌اند
- مدل‌ها، رابطه‌های Eloquent، query builder و فیلترهای آماده
- API خواندنی اختیاری
- Blade component ساده برای selectها، بدون نیاز به JavaScript
- admin UI اختیاری برای حالت database
- تست‌های کیفیت داده برای شمارش‌ها، checksum، رابطه‌های والد/فرزند، متن فارسی و مراکز استان‌ها

## نیازمندی‌ها

| Laravel | PHP |
| --- | --- |
| 11 | PHP 8.2+ |
| 12 | PHP 8.2+ |
| 13 | PHP 8.3+ |

- `zarbinco/laravel-persian-core` با نسخه `^0.1` یا `^1.0`
- اکستنشن PHP `zip` فقط برای توسعه خود پکیج و release/archive check لازم است؛ مصرف‌کننده پکیج برای runtime معمولی به آن نیاز ندارد.

## نصب

```bash
composer require zarbinco/laravel-iran-locations
```

پکیج به‌صورت پیش‌فرض با `database driver` بالا می‌آید:

```env
IRAN_LOCATIONS_DRIVER=database
```

اگر فقط داده آماده و read-only می‌خواهید، driver را روی `json` بگذارید:

```env
IRAN_LOCATIONS_DRIVER=json
```

## انتخاب driver

### JSON driver؛ بدون migration

در این حالت پکیج مستقیماً از فایل‌های `data/*.json` داخل package می‌خواند:

```env
IRAN_LOCATIONS_DRIVER=json
```

در `JSON driver` این‌ها لازم نیست:

- publish کردن migrationهای پکیج
- اجرای `php artisan migrate` برای جدول‌های پکیج
- اجرای `iran-locations:sync`

این حالت برای dropdown، لیست‌های خواندنی، API options/search، validation list و پروژه‌هایی مناسب است که نمی‌خواهند جدول جدید داشته باشند.

محدودیت‌های مهم JSON mode:

- read-only است.
- sync ندارد.
- admin CRUD ندارد.
- رکورد custom ندارد.
- رابطه‌های دیتابیسی Eloquent ندارد.
- مقدار select و API، `code` است نه database id.
- فیلترها و propهای id مثل `province_id` یا `provinceId` فقط برای `database driver` هستند. در JSON mode باید از `province_code`، `city_code`، `provinceCode`، `cityCode` و propهای code استفاده کنید.

Cache بین requestها در JSON mode به‌صورت پیش‌فرض خاموش است. اگر خودتان فعال کنید، cache key شامل data version و manifest checksum می‌شود:

```env
IRAN_LOCATIONS_JSON_CACHE=true
```

### Database driver؛ sync و رکورد سفارشی

در این حالت داده‌ها وارد جدول‌های برنامه می‌شوند و می‌توانید از مدل‌ها، relationها، admin، رکوردهای custom و queryهای دیتابیسی استفاده کنید:

```env
IRAN_LOCATIONS_DRIVER=database
```

مسیر معمول راه‌اندازی:

```bash
php artisan vendor:publish --tag=iran-locations-config
php artisan vendor:publish --tag=iran-locations-migrations
php artisan migrate
php artisan iran-locations:sync --dry-run
php artisan iran-locations:sync
```

برای بررسی وضعیت:

```bash
php artisan iran-locations:status
php artisan iran-locations:doctor
```

`sync` جدول‌ها را truncate نمی‌کند. رکوردهای `source = custom` حفظ می‌شوند. رکوردهای package که دیگر در داده فعلی نیستند، به‌صورت پیش‌فرض deprecated می‌شوند و hard delete نمی‌شوند.

## نمونه config

```php
return [
    'storage' => [
        'driver' => env('IRAN_LOCATIONS_DRIVER', 'database'),

        'json' => [
            'cache' => env('IRAN_LOCATIONS_JSON_CACHE', false),
            'cache_key' => 'iran_locations.json_data',
        ],
    ],

    'admin' => [
        'enabled' => false,
        'prefix' => 'admin/iran-locations',
        'middleware' => ['web', 'auth'],
    ],

    'api' => [
        'enabled' => false,
        'prefix' => 'iran-locations/api',
        'middleware' => ['api'],
    ],
];
```

## استفاده خواندنی با Facade

این API با driver فعلی کار می‌کند؛ یعنی همین کد هم در JSON mode و هم در database mode قابل استفاده است:

```php
use Zarbin\IranLocations\Facades\IranLocations;

$provinces = IranLocations::all('provinces');

$tehran = IranLocations::find('city', 's.01.01.01.01');

$cityOptions = IranLocations::options('cities', [
    'province_code' => 'p.01',
]);

$matches = IranLocations::search('تهران');
```

## استفاده با read repository

اگر dependency injection را ترجیح می‌دهید، contract خواندنی پکیج را inject کنید:

```php
use Zarbin\IranLocations\Contracts\LocationReadRepository;

final class LocationController
{
    public function __construct(
        private readonly LocationReadRepository $locations,
    ) {}

    public function cities()
    {
        return $this->locations->options('cities', [
            'province_code' => 'p.01',
        ]);
    }
}
```

متدهای اصلی repository:

- `all(string $type, array $filters = [])`
- `find(string $type, string $code)`
- `options(string $type, array $filters = [], ?int $limit = null)`
- `search(string $term, array $types = [], ?int $limit = null)`

## استفاده Eloquent در database mode

مدل‌های Eloquent فقط وقتی معنا دارند که داده‌ها در دیتابیس sync شده باشند:

```php
use Zarbin\IranLocations\Models\City;
use Zarbin\IranLocations\Models\CityRegion;
use Zarbin\IranLocations\Models\Neighborhood;
use Zarbin\IranLocations\Models\Province;

$province = Province::query()->byCode('p.01')->first();

$cities = City::query()
    ->forProvince($province)
    ->active()
    ->ordered()
    ->get();

$regions = CityRegion::query()
    ->forCityCode('s.01.01.01.01')
    ->orderedByNumber()
    ->get();

$neighborhoods = Neighborhood::query()
    ->forRegionCode('r.01.01.01.01.05')
    ->ordered()
    ->get();
```

## Blade componentها

Componentها زیر namespace `iran-locations` ثبت می‌شوند. مقدار optionها `code` است:

```blade
<x-iran-locations::province-select name="province_code" />
<x-iran-locations::city-select name="city_code" province-code="p.01" />
<x-iran-locations::neighborhood-select name="neighborhood_code" city-code="s.01.01.01.01" />
```

Propهای code مثل `province-code` و `city-code` در هر دو driver کار می‌کنند. Propهای id مثل `province-id` فقط برای database mode هستند و در JSON mode نباید برای گرفتن فرزندها استفاده شوند.

## API خواندنی

API به‌صورت پیش‌فرض خاموش است. برای فعال‌سازی:

```php
'api' => [
    'enabled' => true,
],
```

Endpointها زیر prefix پیش‌فرض `iran-locations/api` قرار می‌گیرند:

```bash
curl "https://example.test/iran-locations/api/status"
curl "https://example.test/iran-locations/api/options/cities?province_code=p.01"
curl "https://example.test/iran-locations/api/search?q=تهران"
curl "https://example.test/iran-locations/api/cities/s.01.01.01.01/regions"
```

API فقط خواندنی است. در JSON mode مقدارها و route parentها بر اساس `code` هستند. اگر فیلتر database id مثل `province_id=1` بفرستید، پاسخ `422` می‌گیرید و باید فیلتر code متناظر را استفاده کنید.

## کدهای عمومی داده

`code` مقدار عمومی و پایدار پکیج است. این مقدار را در فرم‌ها، URLها، API و تنظیمات خودتان نگه دارید؛ نه id جدول دیتابیس را. idهای دیتابیس محلی هستند و بعد از sync در هر برنامه می‌توانند متفاوت باشند.

نمونه کدها:

- استان: `p.01`
- شهرستان: `c.01.01`
- بخش: `b.01.01.01`
- دهستان: `d.01.01.01.01`
- شهر: `s.01.01.01.01`
- منطقه شهری: `r.01.01.01.01.05`
- محله: `n.01.01.01.01.05.001`

کدها با scheme کوتاه و fixed-width خود پکیج ساخته شده‌اند. مقدارهای شبیه کد در فایل‌های منبع به‌عنوان `source_id` نگه داشته می‌شوند و public `code` نیستند. چون پکیج هنوز public نشده، کدهای قدیمی pre-release مثل `ir.*` حفظ نشده‌اند و compatibility map هم برای آن‌ها وجود ندارد.

## محدوده داده

داده فعلی package شامل این تعداد رکورد است:

- 31 استان
- 484 شهرستان
- 1087 بخش
- 73 دهستان
- 1456 شهر
- 22 منطقه شهری تهران
- 0 ناحیه شهری
- 568 محله یا مکان شهری تهران
- 568 رابطه محله-منطقه
- 0 alias

ساختار رسمی داده شامل استان، شهرستان، بخش، شهر و دهستان است. ساختار شهری جدا نگه داشته شده: منطقه شهری، ناحیه شهری و محله. ناحیه شهری و alias در schema پشتیبانی می‌شوند، ولی در داده package نسخه `0.2.0-dev` فعلاً خالی هستند مگر اینکه برنامه شما رکورد custom اضافه کند.

نام‌های فارسی public از `ك/ي` عربی به `ک/ی` فارسی نرمال شده‌اند. مرکز استان‌ها هم در داده علامت‌گذاری شده‌اند.

## شفافیت منبع و مجوز داده

داده package از فایل‌های spreadsheet وارد و curate شده است. این پکیج ادعا نمی‌کند که داده‌ها همیشه کامل، رسمی، به‌روز یا مناسب استفاده حقوقی، لجستیکی، مقرراتی یا حساس هستند.

قبل از استفاده production که به خود داده وابسته است، وضعیت منبع، مجوز بازنشر، نیازهای attribution و کامل‌بودن داده‌ها را بررسی کنید:

- [DATA-SOURCES.md](DATA-SOURCES.md)
- [DATA-LICENSE.md](DATA-LICENSE.md)

## چیزی که فعلاً در scope نیست

- پوشش کامل روستاها
- boundary، routing، postal code یا مختصات کامل
- به‌روزرسانی تضمینی روزنامه رسمی یا تقسیمات کشوری
- تضمین مناسب‌بودن برای کاربردهای حقوقی، مقرراتی، لجستیکی یا high-stakes

## Admin UI

Admin UI پیش‌فرض خاموش است و فقط در `database driver` ثبت می‌شود:

```php
'admin' => [
    'enabled' => true,
],
```

برای محیط واقعی، routeهای admin را پشت middleware احراز هویت و در صورت نیاز gate برنامه خودتان نگه دارید. به‌صورت پیش‌فرض، ویرایش مستقیم رکوردهای `source = package` محدود است و رکوردهای custom برای تغییرات برنامه شما در نظر گرفته شده‌اند.

## تست و release gate

```bash
composer validate --strict
composer test
composer run-script format:test
composer analyse
composer run-script release:check
```

`release:check` validation، تست‌ها، Pint، PHPStan، ساخت Composer archive و archive hygiene را اجرا می‌کند.

## مستندات بیشتر

- [English README](README.en.md)
- [Data](docs/data.md)
- [Query examples with Tehran city](docs/query-examples.md)
- [Sync](docs/sync.md)
- [Admin UI](docs/admin.md)
- [API](docs/api.md)
- [Blade components](docs/components.md)
- [Extending](docs/extending.md)
- [Consumer smoke test](docs/consumer-smoke-test.md)
- [Release checklist](docs/release-checklist.md)
- [Changelog](CHANGELOG.md)

## License

کد پکیج با مجوز [MIT](LICENSE.md) منتشر می‌شود. درباره وضعیت داده‌های package، [DATA-LICENSE.md](DATA-LICENSE.md) را جداگانه بخوانید.
