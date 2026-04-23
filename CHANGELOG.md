# Changelog

All notable changes to `sefirosweb/laravel-general-helper` are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

## [12.0.3] - 2026-04-23

### Fixed
- **`purge:temp` command broke in PHP 8.x runtime** because of two implicit `SplFileInfo` → string coercions that PHP 8 no longer allows:
  - `pathinfo($file)` now uses `$file->getBasename('.' . $file->getExtension())`.
  - `unlink($file['file'])` now uses `$file['file']->getPathname()`.
- Switched the age check from `$file->getCTime()` to `$file->getMTime()`. ctime tracks inode metadata (permissions/ownership), not content age; mtime is the conventional "how old is this file" signal and matches what the cleanup intent actually is.
- `fopen()` calls in `pathTemp()` and `saveCsvInServer()` now throw an `Exception` on failure instead of passing `false` to subsequent `fwrite()` / `fputcsv()` and producing a silent no-op.

### Changed
- Native return types on all `PdfHelper` methods (`loadView`, `set_option`, `setPaper`, `download`, `showFile`, `save`).
- Cleaner output in `RemoveTempFiles`: uses `$this->line()` instead of raw `echo`, and returns `self::SUCCESS`.
- Removed an unused `App\Models\User` import from `RemoveTempFiles`.

### Added
- `tests/Feature/RemoveTempFilesCommandTest.php` — 4 tests covering empty temp dir, local-env behavior, and production-env age-based filtering. Caught the two SplFileInfo coercion bugs above.

## [12.0.2] - 2026-04-23

### Added
- **`ExcelHelper::getSpreadsheet(): Spreadsheet`** and **`ExcelHelper::getWriter(): Xlsx`** accessors. Restores the public-access contract of v12.0.0 after an unintended breaking change in v12.0.1 (see Fixed below).

### Fixed
- **Breaking-change regression from v12.0.1**: when `ExcelHelper`'s properties were tightened from dynamic (implicitly public) to declared as **`protected`**, consumer code that reached into the spreadsheet — for example `$excel->spreadsheet->getActiveSheet()` to apply formatting the helper does not wrap — started throwing visibility errors. The `getSpreadsheet()` getter restores that extensibility. Callers should migrate from `$excel->spreadsheet->…` to `$excel->getSpreadsheet()->…`; the property itself stays `protected` on purpose (encapsulation).

### Changed
- Enabled `declare(strict_types=1);` on every PHP file under `src/`. Notable given this package has the broadest public API (global helper functions + `ExcelHelper` / `PdfHelper` / `CacheRequest` / `RedisHelper` classes). Tests (56/109) pass unchanged.

## [12.0.1] - 2026-04-23

### Changed
- **`saveCsvInServer` — PHP 9 safety**: replaced deprecated `utf8_decode()` with `mb_convert_encoding(..., 'ISO-8859-1', 'UTF-8')`. `utf8_decode` is deprecated in PHP 8.2+ and will be removed in PHP 9.
- **`ExcelHelper` migrated to phpspreadsheet 3**: replaced the removed `setCellValueByColumnAndRow()` with `setCellValue(Coordinate::stringFromColumnIndex($col) . $row, …)`. Constructor now declares typed properties (`string $fileName`, `?string $path`, `Spreadsheet $spreadsheet`, `Xlsx $writer`) to avoid PHP 8.2+ dynamic-property deprecation.
- **`excelToArray` error handling**: throws `Exception` on unsupported formats / unreadable files instead of the prior `print_r($e) + exit` that killed the whole PHP process.
- **File-naming race condition**: replaced `date('YmdHis')` with `uniqid('', true)` across `saveCsvInServer`, `savingZipInServer`, `ExcelHelper::save()` and `PdfHelper::save()`. The previous naming could collide when the same helper was called twice within a single second.
- Bumped `phpoffice/phpspreadsheet` constraint to `^3.0`.

### Added
- Unit tests for every pure helper: `array_group_by`, `array_group_by_multidimensional`, `objectToArray`, `mergeArrays`, `mergeArraysOnSubArray`, `br2nl`, `eliminar_tildes`, `char_at`, `generateMarks`, `createMarks`.
- Unit tests for `CacheRequest` (set / get / delete / remember with null-caching / flush) and `RedisHelper` (with the Redis facade mocked via Mockery).
- Feature tests: CSV export encoding (regression for `utf8_decode`), `ExcelHelper` round-trip against phpspreadsheet 3, `excelToArray`, `PdfHelper::loadView` round-trip, unauthenticated save (CLI / queue-worker path).
- `strict_types=1` declared on all test files.
- Rewritten `README.md` with the full helper catalogue including per-function signatures and dedicated sections for `PdfHelper`, `ExcelHelper`, `CacheRequest`, `RedisHelper`. Replaces the prior 15 `TODO` placeholders.

## [12.0.0] - 2026-04-23

### Added
- Initial Laravel 12 release. Requires PHP `^8.2`, `laravel/framework ^12.0`, `barryvdh/laravel-dompdf ^3.0`.
- Orchestra Testbench baseline suite.

### Changed
- Bumped `barryvdh/laravel-dompdf` to `^3.0` (from `^2.0.1`).
- `PdfHelper` declares `protected DomPdfWrapper $pdf` property to avoid PHP 8.2+ dynamic-property deprecation.
- Converted the `create_saved_files` migration to anonymous-class syntax.
- Migrated `FileController` routes from the legacy `'Controller@method'` string syntax to FQCN array form.

### Removed
- Support for Laravel `< 12` on this branch. Older majors live on the `9.x` branch with their legacy tag lineage.
