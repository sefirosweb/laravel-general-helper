# Laravel General Helper

Collection of helpers and utility classes for Laravel apps: array manipulation, CSV/Excel generation and parsing, PDF rendering, per-request caching, and a Redis wrapper.

## Requirements

- PHP `^8.2`
- Laravel `^12.0`
- For PDF helpers: `barryvdh/laravel-dompdf ^3.0` (installed as a dependency).
- For Excel helpers: `phpoffice/phpspreadsheet ^3.0` (installed as a dependency).

## Installation

```bash
composer require sefirosweb/laravel-general-helper:^12.0
```

The service provider auto-registers via Laravel's package discovery. It loads the global function helpers and exposes routes under the `general_helper` prefix for downloading/streaming saved files.

Run migrations to create the `saved_files` table used for tracking generated CSV/Excel/PDF/ZIP files:

```bash
php artisan migrate
```

## Configuration

Publish the config to change the route prefix or middleware:

```bash
php artisan vendor:publish --provider="Sefirosweb\LaravelGeneralHelper\LaravelGeneralHelperServiceProvider" --tag=config --force
```

Default `config/laravel-general-helper.php`:

```php
return [
    'prefix'     => 'general_helper',
    'middleware' => 'web',
];
```

---

## Global function helpers

All of these are loaded globally by the service provider and available anywhere in your app.

### Array helpers

#### `array_group_by(array $array, string|int|callable $key, bool $onlyFirstValue = false): array`

Group a list of associative arrays or objects by a field or callback. With `$onlyFirstValue = true` each group is reduced to just its last assigned element.

```php
$grouped = array_group_by($rows, 'type');
$parity  = array_group_by([1, 2, 3, 4], fn ($n) => $n % 2 === 0 ? 'even' : 'odd');
```

#### `array_group_by_multidimensional(array $array, array $union_by, bool $onlyFirstValue = false): ?array`

Same as `array_group_by` but nests groups one level per key:

```php
$tree = array_group_by_multidimensional($rows, ['country', 'city']);
// $tree['es']['mad'] = [ ...rows from Madrid... ]
```

#### `objectToArray(mixed $obj): mixed`

Recursively convert `stdClass` (and nested arrays of them) to pure arrays. Scalars pass through unchanged.

#### `mergeArrays($main, $secondary, $union_by, $union_first_value = false, $force_mmatch = false): array`

Join `$main` with `$secondary` by one or more keys (like a SQL `JOIN`). If `$force_mmatch = true` rows from `$main` with no match are dropped.

#### `mergeArraysOnSubArray($main, $secondary, $union_by, $name, $union_first_value = false): array`

Like `mergeArrays` but the matched records from `$secondary` are nested under a named key in each `$main` row.

### String helpers

#### `br2nl(string $string): string`

Replace any `<br>`/`<br/>`/`<br />` tag with `\n`.

#### `eliminar_tildes(string $cadena): string`

Strip Spanish accents / tildes (`á → a`, `ñ → n`, `ç → c`…).

#### `char_at(string $str, int $pos): string`

Return the character at `$pos` in `$str`. Thin wrapper over `$str[$pos]`.

### SQL parameter-marker helpers

#### `generateMarks(string $markName, array $values): stdClass`

Build a parameterised SQL placeholder list from an array of values. Returns `{sql, assoc}`.

```php
$r = generateMarks('id', [10, 20, 30]);
// $r->sql  = " :id_0,:id_1,:id_2\n"
// $r->assoc = ['id_0' => 10, 'id_1' => 20, 'id_2' => 30]
```

Returns `sql = 'false'` when the input array is empty.

#### `createMarks(array $rows): stdClass`

Concatenate multiple filter expressions into a single SQL fragment with associative bindings. Each row in `$rows` must have `filter`, `name`, `value` keys.

#### `query(string $query, ?array $marks = null, string|false $database = false): array`

Run a raw `DB::select` and return the result as a pure array. Optionally routes through a specific connection.

### File-saving helpers

All of these return a `SavedFile` Eloquent model (rows in `saved_files`). The file path is inside `storage_path('tmp')`, and the authenticated user is associated with the file when available (`Auth::user()` being `null` is safe — the FK is nullable).

| Helper | Description |
|---|---|
| `pathTemp(): string` | Return (and create if missing) `storage_path('tmp')`. Adds a `.gitignore` to keep the dir out of git. |
| `saveCsvInServer($arrayData, $fileName, $delimiter = ';', $enclosure = '"', $latingMode = false, $headers = true, $utf8_decode = false, $enclosureAll = false): SavedFile` | Write data to a CSV file. If `$utf8_decode = true`, output bytes are converted to ISO-8859-1 (useful for Excel-on-Windows). |
| `saveCsvInServerAndDownload(...): Response` | Like `saveCsvInServer` but returns a download response. |
| `saveExcelInServer($arrayData, $fileName, $headers = true, $creator = null): SavedFile` | Write data to an `.xlsx` file using `ExcelHelper` under the hood. |
| `saveExcelInServerAndDownload(...): Response` | Like `saveExcelInServer` but returns a download response. |
| `savingZipInServer($files, $fileName): SavedFile` | Bundle arbitrary files/folders into a `.zip`. |
| `saveZipInServerAndDownload(...): Response` | Like `savingZipInServer` but returns a download response. |

### Excel parsing

#### `excelToArray(string $filePath, string|false $encode = false): array`

Read `.xls`/`.xlsx`/`.csv` into an associative array. The first row is treated as headers.

```php
$rows = excelToArray('/path/to/users.xlsx');
// [
//   ['name' => 'Alice', 'age' => 30],
//   ['name' => 'Bob',   'age' => 25],
// ]
```

Throws `\Exception` on unsupported formats or unreadable files.

### Validation

#### `validateArray(array $array, array $rules): array`

Run Laravel's `Validator` against each row of `$array`. Returns an array of rows that failed, each with an extra `errors` key.

---

## Helper classes

### `CacheRequest` — per-request in-memory cache

Key/value cache that lives for the duration of a single HTTP request (or console command). Ideal for avoiding duplicate DB queries inside a single page render.

```php
use Sefirosweb\LaravelGeneralHelper\Helpers\CacheRequest;

CacheRequest::set('user_permissions', $perms);
$perms = CacheRequest::get('user_permissions');

// Compute lazily, cache the result:
$settings = CacheRequest::remember('settings', fn () => Setting::pluck('value', 'key'));

CacheRequest::delete('user_permissions');
CacheRequest::flush(); // clear the whole cache
```

### `RedisHelper` — Redis convenience wrapper

Wraps `Illuminate\Support\Facades\Redis` with JSON serialisation and exception-swallowing for read paths (so a Redis outage returns `null` instead of breaking the request).

```php
use Sefirosweb\LaravelGeneralHelper\Helpers\RedisHelper;

RedisHelper::set('user:1', ['name' => 'Alice'], 3600);
$user = RedisHelper::get('user:1');          // array or null
RedisHelper::delete('user:1');

// Publish on a channel:
RedisHelper::publish('events', ['event' => 'ping']);

// Read-through cache — $prod=true means cache in every environment,
// $prod=false caches only in `local` (useful for dev/testing without
// polluting prod-like environments).
$heavy = RedisHelper::call(
    fn () => expensiveQuery(),
    key: 'heavy:query',
    prod: true,
    EX: 600,
);
```

### `PdfHelper` — dompdf wrapper

Thin wrapper around `barryvdh/laravel-dompdf ^3.0`.

```php
use Sefirosweb\LaravelGeneralHelper\Helpers\PdfHelper;

$pdf = new PdfHelper();
$pdf->loadView('invoices.show', ['invoice' => $invoice]);
$pdf->setPaper('a4', 'portrait');

// Stream inline:
return $pdf->showFile('invoice-123.pdf');

// Force download:
return $pdf->download('invoice-123');

// Save to disk + persist a SavedFile row:
$savedFile = $pdf->save('invoice-123');

// Tweak a dompdf option directly:
$pdf->set_option('isRemoteEnabled', true);
```

### `ExcelHelper` — phpspreadsheet wrapper

Builder for multi-sheet `.xlsx` files backed by `phpoffice/phpspreadsheet ^3.0`.

```php
use Sefirosweb\LaravelGeneralHelper\Helpers\ExcelHelper;

$excel = new ExcelHelper('sales_report');
$excel->addSheet($customers, 'Customers', headers: true);
$excel->addSheet($orders,    'Orders',    headers: true);

$savedFile = $excel->save();
// returns a SavedFile; file is at $savedFile->path
```

---

## Testing

The package ships with an Orchestra Testbench suite covering array helpers, encoding conversion (regression for the `utf8_decode` removal in PHP 9), `CacheRequest`, `RedisHelper` (with the Redis facade mocked), `ExcelHelper` round-trips, `excelToArray`, `PdfHelper`, and CSV export encoding.

```bash
composer install
./vendor/bin/phpunit
```

When working from the [laravel-test](https://github.com/sefirosweb/laravel-test) harness with Sail:

```bash
docker exec -w /var/www/html/packages/laravel-general-helper laravel-test-laravel.test-1 ./vendor/bin/phpunit
```

## Versioning

Major versions are aligned with Laravel majors (`12.x`, `11.x`, `9.x` …). See the root [CLAUDE.md](https://github.com/sefirosweb/laravel-test/blob/12.0/CLAUDE.md) of the test harness for the full policy.

## License

MIT.
