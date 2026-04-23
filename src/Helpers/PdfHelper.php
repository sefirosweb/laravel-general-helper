<?php

declare(strict_types=1);

namespace Sefirosweb\LaravelGeneralHelper\Helpers;

use Barryvdh\DomPDF\PDF as DomPdfWrapper;
use Exception;
use Sefirosweb\LaravelGeneralHelper\Http\Models\SavedFile;
use Illuminate\Support\Facades\Auth;

class PdfHelper
{
    protected DomPdfWrapper $pdf;

    public function __construct()
    {
        $this->pdf = app('dompdf.wrapper');
        $this->set_option("enable_php", true);
    }

    public function loadView($view, $data = [])
    {
        $this->pdf->loadView($view, $data);
    }

    public function set_option($option, $value)
    {
        $this->pdf->getDomPDF()->set_option($option, $value);
    }

    public function setPaper($type, $direction)
    {
        $this->pdf->setPaper($type, $direction);
    }

    public function download($filename)
    {
        return $this->pdf->download($filename . '.pdf');
    }

    public function showFile($name = '')
    {
        return $this->pdf->stream($name);
    }

    public function save($filename, $path = null)
    {
        $path = $path ?: pathTemp() . '/' . $filename . '_' . uniqid('', true) . '.pdf';

        $this->pdf->save($path);

        $savedFile = new SavedFile;
        $savedFile->user()->associate(Auth::user());
        $savedFile->file_name = $filename;
        $savedFile->extension = 'pdf';
        $savedFile->path = $path;
        $savedFile->save();
        return $savedFile;
    }
}
