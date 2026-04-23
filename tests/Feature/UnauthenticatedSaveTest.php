<?php

declare(strict_types=1);

namespace Sefirosweb\LaravelGeneralHelper\Tests\Feature;

use Illuminate\Support\Facades\Auth;
use Sefirosweb\LaravelGeneralHelper\Helpers\ExcelHelper;
use Sefirosweb\LaravelGeneralHelper\Http\Models\SavedFile;
use Sefirosweb\LaravelGeneralHelper\Tests\TestCase;

/**
 * Regression: helpers that persist SavedFile records must not crash
 * when called from CLI / queue workers where Auth::user() is null.
 * This is exercised by saveCsvInServer, saveExcelInServer, PdfHelper::save,
 * and savingZipInServer.
 */
class UnauthenticatedSaveTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Auth::logout();
    }

    public function test_save_csv_in_server_without_auth_stores_null_user_id(): void
    {
        $savedFile = saveCsvInServer([['a' => 1]], 'anon_csv');
        @unlink($savedFile->path);

        $this->assertNull($savedFile->user_id);
    }

    public function test_excel_helper_save_without_auth_stores_null_user_id(): void
    {
        $helper = new ExcelHelper('anon_xlsx');
        $helper->addSheet([['a' => 1]], 'Sheet', true);
        $savedFile = $helper->save();
        @unlink($savedFile->path);

        $this->assertNull($savedFile->user_id);
    }

    public function test_saved_file_with_null_user_id_persists_correctly(): void
    {
        $file = new SavedFile();
        $file->file_name = 'orphan';
        $file->extension = 'txt';
        $file->path = '/tmp/orphan.txt';
        $file->save();

        $reloaded = SavedFile::find($file->id);
        $this->assertNull($reloaded->user_id);
        $this->assertNull($reloaded->user);
    }
}
