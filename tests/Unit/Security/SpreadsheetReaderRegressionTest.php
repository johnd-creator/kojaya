<?php

namespace Tests\Unit\Security;

use PhpOffice\PhpSpreadsheet\Reader\Exception;
use PhpOffice\PhpSpreadsheet\Reader\Ods;
use PhpOffice\PhpSpreadsheet\Reader\Security\XmlScanner;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ZipArchive;

class SpreadsheetReaderRegressionTest extends TestCase
{
    /** @return array<string, array{string}> */
    public static function escapeSequences(): array
    {
        return [
            'ASCII designation' => ["\x1b(B"],
            'JIS designation' => ["\x1b".'$B'],
        ];
    }

    #[DataProvider('escapeSequences')]
    public function test_scanner_rejects_doctype_obfuscated_with_iso_2022_escapes(string $escape): void
    {
        // Scan only; do not parse entities or open a network/file reference.
        $xml = "\xef\xbb\xbf".'<?xml version="1.0" encoding="ISO-2022-JP"?>'
            .implode($escape, str_split('<!DOCTYPE'))
            .' root [<!ENTITY harmless "safe">]><root/>';

        $this->expectException(Exception::class);
        (new XmlScanner)->scan($xml);
    }

    public function test_scanner_still_rejects_null_interleaved_doctype(): void
    {
        $this->expectException(Exception::class);
        (new XmlScanner)->scan(implode("\0", str_split('<!DOCTYPE')).' root><root/>');
    }

    public function test_scanner_preserves_ordinary_utf_8_xml(): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><root><value>100000</value></root>';

        $this->assertSame($xml, (new XmlScanner)->scan($xml));
        $this->assertSame($xml, (new XmlScanner)->scan("\xef\xbb\xbf".$xml));
    }

    /** @return array<string, array{int, int, string}> */
    public static function oversizedRepeats(): array
    {
        return [
            'row limit' => [2097152, 1, 'number-rows-repeated'],
            'column limit' => [1, 32768, 'number-columns-repeated'],
        ];
    }

    #[DataProvider('oversizedRepeats')]
    public function test_ods_metadata_rejects_out_of_range_repeats(int $rows, int $columns, string $message): void
    {
        $file = $this->createOds($rows, $columns);

        try {
            $this->expectException(Exception::class);
            $this->expectExceptionMessage($message);
            // Metadata inspection does not materialize repeated cells, even on
            // the old version, so the regression stays bounded on both versions.
            (new Ods)->listWorksheetInfo($file);
        } finally {
            unlink($file);
        }
    }

    public function test_ods_metadata_counts_small_valid_repeats(): void
    {
        $file = $this->createOds(3, 2);

        try {
            $info = (new Ods)->listWorksheetInfo($file);
            $this->assertSame(3, $info[0]['totalRows']);
            $this->assertSame(2, $info[0]['totalColumns']);
        } finally {
            unlink($file);
        }
    }

    private function createOds(int $rows, int $columns): string
    {
        $file = tempnam(sys_get_temp_dir(), 'kojaya-ods-regression-');
        $this->assertNotFalse($file);
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($file, ZipArchive::OVERWRITE));
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<office:document-content xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"'
            .' xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0"'
            .' xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0">'
            .'<office:body><office:spreadsheet><table:table table:name="Synthetic">'
            .'<table:table-row table:number-rows-repeated="'.$rows.'">'
            .'<table:table-cell table:number-columns-repeated="'.$columns.'"><text:p>safe</text:p></table:table-cell>'
            .'</table:table-row></table:table></office:spreadsheet></office:body></office:document-content>';
        $zip->addFromString('content.xml', $xml);
        $zip->close();

        return $file;
    }
}
