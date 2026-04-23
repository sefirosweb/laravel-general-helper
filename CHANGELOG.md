# Changelog

All notable changes to `sefirosweb/laravel-general-helper` are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

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
