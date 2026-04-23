<?php

declare(strict_types=1);

namespace Sefirosweb\LaravelGeneralHelper\Tests\Feature;

use Illuminate\Support\Facades\Auth;
use Sefirosweb\LaravelGeneralHelper\Tests\Fixtures\User;
use Sefirosweb\LaravelGeneralHelper\Tests\TestCase;

class CsvExportEncodingTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $user = new User();
        $user->forceFill(['id' => 1, 'name' => 'Tester', 'email' => 'tester@example.com']);
        $user->save();
        Auth::login($user);
    }

    public function test_utf8_decode_flag_produces_iso_8859_1_bytes(): void
    {
        $rows = [
            ['nombre' => 'José', 'ciudad' => 'Málaga'],
            ['nombre' => 'Ñoño',  'ciudad' => 'Córdoba'],
        ];

        $savedFile = saveCsvInServer($rows, 'encoding_test', ';', '"', false, true, true);

        $bytes = file_get_contents($savedFile->path);
        @unlink($savedFile->path);

        // ISO-8859-1 encodes accented letters as single bytes > 0x7F
        // whereas UTF-8 would encode them as 2-byte sequences starting 0xC3.
        // If utf8_decode was replaced correctly, we should NOT see 0xC3 bytes
        // for these specific characters.
        $this->assertStringNotContainsString("\xC3", $bytes, 'Expected Latin-1 output, got UTF-8 bytes');
        $this->assertStringContainsString("\xE9", $bytes, 'Expected 0xE9 (é) as Latin-1 single byte');
        $this->assertStringContainsString("\xF1", $bytes, 'Expected 0xF1 (ñ) as Latin-1 single byte');
    }

    public function test_utf8_decode_flag_false_keeps_utf8_bytes(): void
    {
        $rows = [['nombre' => 'Málaga']];

        $savedFile = saveCsvInServer($rows, 'encoding_test_utf8', ';', '"', false, true, false);

        $bytes = file_get_contents($savedFile->path);
        @unlink($savedFile->path);

        // UTF-8 "á" is 0xC3 0xA1
        $this->assertStringContainsString("\xC3\xA1", $bytes, 'Expected UTF-8 multibyte for á');
    }
}
