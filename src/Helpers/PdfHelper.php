<?php

declare(strict_types=1);

namespace Sefirosweb\LaravelGeneralHelper\Helpers;

use Barryvdh\DomPDF\PDF as DomPdfWrapper;
use Illuminate\Http\Response;
use Sefirosweb\LaravelGeneralHelper\Http\Models\SavedFile;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PdfHelper
{
    protected DomPdfWrapper $pdf;

    public function __construct()
    {
        $this->pdf = app('dompdf.wrapper');
        $this->set_option('enable_php', true);
    }

    public function loadView(string $view, array $data = []): void
    {
        $this->pdf->loadView($view, $data);
    }

    public function set_option(string $option, mixed $value): void
    {
        $this->pdf->getDomPDF()->set_option($option, $value);
    }

    public function setPaper(string $type, string $direction): void
    {
        $this->pdf->setPaper($type, $direction);
    }

    public function download(string $filename): Response
    {
        return $this->pdf->download($filename . '.pdf');
    }

    public function showFile(string $name = ''): StreamedResponse|Response
    {
        return $this->pdf->stream($name);
    }

    public function save(string $filename, ?string $path = null): SavedFile
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
