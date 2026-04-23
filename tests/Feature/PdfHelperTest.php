<?php

namespace Sefirosweb\LaravelGeneralHelper\Tests\Feature;

use Illuminate\Support\Facades\View;
use Sefirosweb\LaravelGeneralHelper\Helpers\PdfHelper;
use Sefirosweb\LaravelGeneralHelper\Tests\TestCase;

class PdfHelperTest extends TestCase
{
    public function test_dompdf_wrapper_is_bound(): void
    {
        $wrapper = $this->app->make('dompdf.wrapper');

        $this->assertInstanceOf(\Barryvdh\DomPDF\PDF::class, $wrapper);
    }

    public function test_helper_can_be_instantiated(): void
    {
        $helper = new PdfHelper();

        $this->assertInstanceOf(PdfHelper::class, $helper);
    }

    public function test_load_view_produces_pdf_output(): void
    {
        View::addLocation(__DIR__ . '/../fixtures/views');

        $helper = new PdfHelper();
        $helper->loadView('sample', ['title' => 'Testbench PDF']);
        $helper->setPaper('a4', 'portrait');

        $stream = $helper->showFile('sample.pdf');
        $content = $stream->getContent();

        $this->assertNotEmpty($content);
        $this->assertStringStartsWith('%PDF', $content, 'Output should be a valid PDF file');
    }

    public function test_set_option_delegates_to_dompdf(): void
    {
        $helper = new PdfHelper();
        $helper->set_option('defaultFont', 'Helvetica');

        $wrapper = (new \ReflectionClass($helper))->getProperty('pdf')->getValue($helper);
        $this->assertSame('Helvetica', $wrapper->getDomPDF()->getOptions()->getDefaultFont());
    }
}
