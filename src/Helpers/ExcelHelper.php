<?php

declare(strict_types=1);

namespace Sefirosweb\LaravelGeneralHelper\Helpers;

use Exception;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Settings;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Sefirosweb\LaravelGeneralHelper\Http\Models\SavedFile;

class ExcelHelper
{
    protected string $fileName;

    protected ?string $path;

    protected Spreadsheet $spreadsheet;

    protected Xlsx $writer;

    public function __construct(string $fileName, ?string $path = null, ?string $creator = null)
    {
        $this->fileName = $fileName;
        $this->path = $path;

        $locale = 'es';
        if (!Settings::setLocale($locale)) {
            throw new Exception('Unable to set locale to ' . $locale);
        }

        $this->spreadsheet = new Spreadsheet();
        $sheetIndex = $this->spreadsheet->getIndex(
            $this->spreadsheet->getSheetByName('Worksheet')
        );
        $this->spreadsheet->removeSheetByIndex($sheetIndex);

        if ($creator !== null) {
            $this->spreadsheet->getProperties()
                ->setCreator($creator)
                ->setLastModifiedBy($creator);
        }

        $this->writer = new Xlsx($this->spreadsheet);
    }

    public function addSheet(iterable $arrayData, string $sheetName, bool $headers = true): void
    {
        $sheet = new Worksheet($this->spreadsheet, $sheetName);
        $arrayData = objectToArray($arrayData);
        $row = 1;

        if ($headers) {
            $firstData = current($arrayData);
            if ($firstData !== false) {
                $col = 1;
                foreach ($firstData as $header => $value) {
                    $sheet->setCellValue(Coordinate::stringFromColumnIndex($col) . $row, $header);
                    $col++;
                }
                $row++;
            }
        }

        foreach ($arrayData as $field) {
            $col = 1;
            foreach ($field as $value) {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($col) . $row, $value);
                $col++;
            }
            $row++;
        }

        $this->spreadsheet->addSheet($sheet);
    }

    public function save(): SavedFile
    {
        $path = $this->path ?: pathTemp() . '/' . $this->fileName . '_' . uniqid('', true) . '.xlsx';

        $this->writer->save($path);

        $savedFile = new SavedFile;
        $savedFile->user()->associate(Auth::user());
        $savedFile->file_name = $this->fileName;
        $savedFile->extension = 'xlsx';
        $savedFile->path = $path;
        $savedFile->save();

        return $savedFile;
    }
}
