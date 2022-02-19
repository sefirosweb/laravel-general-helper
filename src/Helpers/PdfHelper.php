<?php

namespace Sefirosweb\LaravelGeneralHelper\Helpers;

use Exception;
use Sefirosweb\LaravelGeneralHelper\Http\Models\SavedFile;
use Illuminate\Support\Facades\Auth;

class PdfHelper
{
    public function __construct()
    {
        $this->pdf = app('dompdf.wrapper');
    }

    public function loadView($view, $data = [])
    {
        $this->pdf->loadView($view, $data);
    }

    public function download($filename)
    {
        return $this->pdf->download($filename . '.pdf');
    }

    public function showFile()
    {
        return $this->pdf->stream();
    }

    public function save($filename, $path = null)
    {
        $path = $path ? $path : pathTemp() . '/' . $filename . '_' . date('YmdHis') . '.pdf';

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
