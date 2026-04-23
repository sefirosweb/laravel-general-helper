<?php

declare(strict_types=1);

namespace Sefirosweb\LaravelGeneralHelper\Tests\Feature;

use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Sefirosweb\LaravelGeneralHelper\Helpers\ExcelHelper;
use Sefirosweb\LaravelGeneralHelper\Tests\TestCase;

class ExcelToArrayTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    protected function tearDown(): void
    {
        $tmp = storage_path('tmp');
        if (File::isDirectory($tmp)) {
            File::deleteDirectory($tmp);
        }

        parent::tearDown();
    }

    private function writeSampleXlsx(array $rowsWithHeaders): string
    {
        $path = storage_path('tmp/e2a-' . uniqid('', true) . '.xlsx');
        File::ensureDirectoryExists(dirname($path));
        Auth::shouldReceive('user')->andReturn(null);

        $helper = new ExcelHelper('e2a', $path);
        $helper->addSheet($rowsWithHeaders, 'Data', true);
        $helper->save();

        return $path;
    }

    public function test_reads_xlsx_and_returns_assoc_rows_using_header_as_keys(): void
    {
        $path = $this->writeSampleXlsx([
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ]);

        $rows = excelToArray($path);

        $this->assertCount(2, $rows);
        $this->assertSame(['id' => 1, 'name' => 'Alice'], [
            'id' => (int) $rows[0]['id'],
            'name' => $rows[0]['name'],
        ]);
        $this->assertSame('Bob', $rows[1]['name']);
    }

    public function test_empty_spreadsheet_returns_empty_array(): void
    {
        $path = $this->writeSampleXlsx([]);

        $rows = excelToArray($path);

        $this->assertSame([], $rows);
    }

    public function test_throws_instead_of_exit_on_nonexistent_file(): void
    {
        // Regression: previously this function did print_r($e) + exit on ANY failure.
        // Now it should propagate the exception up so callers can handle it.
        $this->expectException(Exception::class);

        excelToArray('/nonexistent/path/to/file.xlsx');
    }
}
