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
