<?php

namespace Sefirosweb\LaravelGeneralHelper\Helpers;

use Exception;
use Sefirosweb\LaravelGeneralHelper\Http\Models\SavedFile;
use Illuminate\Support\Facades\Auth;

class ExcelHelper
{
    public function __construct($fileName, $path = null, $creator = null)
    {
        $this->fileName = $fileName;
        $this->path = $path;

        $locale = 'es';
        $validLocale = \PhpOffice\PhpSpreadsheet\Settings::setLocale($locale);
        if (!$validLocale) throw new Exception('Unable to set locale to ' . $locale);

        $this->spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheetIndex = $this->spreadsheet->getIndex(
            $this->spreadsheet->getSheetByName('Worksheet')
        );
        $this->spreadsheet->removeSheetByIndex($sheetIndex);

        if ($creator) {
            $this->spreadsheet->getProperties()
                ->setCreator($creator)
                ->setLastModifiedBy($creator);
        }

        $this->writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($this->spreadsheet);
    }

    public function addSheet($arrayData, $sheetName, $headers = true)
    {
        $sheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($this->spreadsheet, $sheetName);
        $arrayData = objectToArray($arrayData);
        $row = 1;

        if ($headers) {
            $firstData = current($arrayData);
            $col = 1;
            foreach ($firstData as $header => $value) {
                $sheet->setCellValueByColumnAndRow($col, $row, $header);
                $col++;
            }
            $row++;
        }

        foreach ($arrayData as $field) {
            $col = 1;
            foreach ($field as $value) {
                $sheet->setCellValueByColumnAndRow($col, $row, $value);
                $col++;
            }
            $row++;
        }
        $this->spreadsheet->addSheet($sheet);
    }

    public function save()
    {
        $path = $this->path ? $this->path :  pathTemp() . '/' . $this->fileName . '_' . date('YmdHis')  . '.xlsx';

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
