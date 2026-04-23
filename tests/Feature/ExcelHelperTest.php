<?php

declare(strict_types=1);

namespace Sefirosweb\LaravelGeneralHelper\Tests\Feature;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Sefirosweb\LaravelGeneralHelper\Helpers\ExcelHelper;
use Sefirosweb\LaravelGeneralHelper\Tests\TestCase;

class ExcelHelperTest extends TestCase
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

    public function test_can_generate_an_xlsx_with_headers_and_read_it_back(): void
    {
        $target = storage_path('tmp/sample-' . uniqid('', true) . '.xlsx');
        File::ensureDirectoryExists(dirname($target));

        Auth::shouldReceive('user')->andReturn(null);

        $helper = new ExcelHelper('sample', $target, 'Testbench');
        $helper->addSheet([
            ['name' => 'José',  'age' => 30],
            ['name' => 'Muñoz', 'age' => 42],
        ], 'People', true);
        $saved = $helper->save();

        $this->assertFileExists($saved->path);
        $this->assertSame('xlsx', $saved->extension);

        $read = IOFactory::load($saved->path);
        $sheet = $read->getSheetByName('People');

        $this->assertNotNull($sheet, 'Expected "People" sheet to exist in written xlsx');
        $this->assertSame('name', $sheet->getCell('A1')->getValue());
        $this->assertSame('age', $sheet->getCell('B1')->getValue());
        $this->assertSame('José', $sheet->getCell('A2')->getValue());
        $this->assertSame(30, (int) $sheet->getCell('B2')->getValue());
        $this->assertSame('Muñoz', $sheet->getCell('A3')->getValue());
        $this->assertSame(42, (int) $sheet->getCell('B3')->getValue());
    }

    public function test_can_generate_an_xlsx_without_headers(): void
    {
        $target = storage_path('tmp/sample-noheaders-' . uniqid('', true) . '.xlsx');
        File::ensureDirectoryExists(dirname($target));

        Auth::shouldReceive('user')->andReturn(null);

        $helper = new ExcelHelper('sample_nh', $target);
        $helper->addSheet([
            ['a', 'b'],
            ['c', 'd'],
        ], 'Raw', false);
        $helper->save();

        $read = IOFactory::load($target);
        $sheet = $read->getSheetByName('Raw');

        $this->assertSame('a', $sheet->getCell('A1')->getValue());
        $this->assertSame('b', $sheet->getCell('B1')->getValue());
        $this->assertSame('c', $sheet->getCell('A2')->getValue());
        $this->assertSame('d', $sheet->getCell('B2')->getValue());
    }

    public function test_getSpreadsheet_exposes_the_underlying_spreadsheet_instance(): void
    {
        // Regression: v12.0.1 accidentally changed $spreadsheet from a dynamic
        // (public-by-default) property to a typed protected one, breaking any
        // caller doing `$excel->spreadsheet->getActiveSheet()`. The getter
        // restores the access contract.
        Auth::shouldReceive('user')->andReturn(null);

        $helper = new ExcelHelper('accessor');
        $spreadsheet = $helper->getSpreadsheet();

        $this->assertInstanceOf(\PhpOffice\PhpSpreadsheet\Spreadsheet::class, $spreadsheet);

        // Mutating through the accessor must reach the internal state that
        // save() flushes.
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('ManuallyAdded');
        $sheet->setCellValue('A1', 'from-outside');

        $target = storage_path('tmp/accessor-' . uniqid('', true) . '.xlsx');
        File::ensureDirectoryExists(dirname($target));

        $helper = new ExcelHelper('accessor_save', $target);
        $helper->getSpreadsheet()->createSheet()->setTitle('Injected');
        $helper->save();

        $read = \PhpOffice\PhpSpreadsheet\IOFactory::load($target);
        $this->assertNotNull(
            $read->getSheetByName('Injected'),
            'A sheet added via getSpreadsheet() should end up in the written file',
        );
    }

    public function test_getWriter_exposes_the_underlying_xlsx_writer(): void
    {
        Auth::shouldReceive('user')->andReturn(null);

        $helper = new ExcelHelper('writer_accessor');
        $writer = $helper->getWriter();

        $this->assertInstanceOf(\PhpOffice\PhpSpreadsheet\Writer\Xlsx::class, $writer);
    }

    public function test_throws_if_file_name_collides_no_longer_using_date_based_names(): void
    {
        // Two rapid-fire saves to the default temp path must produce two distinct files
        // (regression: old code used date('YmdHis') which collided in same second).
        Auth::shouldReceive('user')->andReturn(null);

        $first = (new ExcelHelper('raceproof'))->addSheet([['x' => 1]], 'S');
        $saved1 = (new ExcelHelper('raceproof'));
        $saved1->addSheet([['x' => 1]], 'S');
        $a = $saved1->save();

        $saved2 = (new ExcelHelper('raceproof'));
        $saved2->addSheet([['x' => 2]], 'S');
        $b = $saved2->save();

        $this->assertNotSame($a->path, $b->path, 'Temp filenames must be unique across rapid saves');
        $this->assertFileExists($a->path);
        $this->assertFileExists($b->path);
    }
}
