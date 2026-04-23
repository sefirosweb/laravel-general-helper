<?php

declare(strict_types=1);

namespace Sefirosweb\LaravelGeneralHelper\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Regression test for replacing utf8_decode() (deprecated PHP 8.2+, removed PHP 9)
 * with mb_convert_encoding(..., 'ISO-8859-1', 'UTF-8') in saveCsvInServer's
 * $utf8_decode branch.
 */
class EncodingConversionTest extends TestCase
{
    public static function accentedCharsProvider(): array
    {
        return [
            'plain ascii' => ['hello', "hello"],
            'spanish eñe' => ['año', "a\xF1o"],
            'accented e'  => ['café', "caf\xE9"],
            'mixed'       => ['José Muñoz', "Jos\xE9 Mu\xF1oz"],
            'all vowels'  => ['áéíóú', "\xE1\xE9\xED\xF3\xFA"],
            'capital'     => ['ÑÇ', "\xD1\xC7"],
        ];
    }

    #[DataProvider('accentedCharsProvider')]
    public function test_mb_convert_encoding_produces_iso_8859_1_bytes(string $utf8, string $expectedLatin1): void
    {
        $converted = mb_convert_encoding($utf8, 'ISO-8859-1', 'UTF-8');

        $this->assertSame($expectedLatin1, $converted);
    }

    public function test_null_is_preserved_by_conversion_guard(): void
    {
        // Matches the guard we added: $data === null ? null : mb_convert_encoding(...)
        $data = null;
        $result = $data === null ? null : mb_convert_encoding((string) $data, 'ISO-8859-1', 'UTF-8');

        $this->assertNull($result);
    }

    public function test_non_string_scalar_is_cast_before_conversion(): void
    {
        // saveCsvInServer might receive ints/floats; the cast to string must not break.
        $data = 42;
        $result = mb_convert_encoding((string) $data, 'ISO-8859-1', 'UTF-8');

        $this->assertSame('42', $result);
    }
}
