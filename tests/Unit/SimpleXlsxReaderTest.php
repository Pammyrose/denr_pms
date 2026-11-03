<?php

namespace Tests\Unit;

use App\Support\SimpleXlsxReader;
use App\Support\SimpleXlsxWriter;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use ZipArchive;

class SimpleXlsxReaderTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        $this->path = tempnam(sys_get_temp_dir(), 'pms-reader-');
        (new SimpleXlsxWriter)->writeWfp($this->path, 'GASS', 'GASS', 2026, []);
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($this->path) === true);
        $zip->addFromString('xl/sharedStrings.xml', '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><si><t>Hidden</t></si><si><r><rPr><b/></rPr><t>Tail</t></r></si></sst>');
        $zip->addFromString('xl/worksheets/sheet1.xml', '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>
            <row r="1"><c r="A1" t="inlineStr"><is><t> First  row </t></is></c><c r="Z1"><v>1.5</v></c></row>
            <row r="2" hidden="1"><c r="A2" t="s"><v>0</v></c></row>
            <row r="3"/>
            <row r="4"><c r="A4" t="s"><v>1</v></c><c r="B4"><v>0</v></c></row>
            </sheetData></worksheet>');
        $zip->close();
    }

    protected function tearDown(): void
    {
        if (is_file($this->path)) {
            unlink($this->path);
        }
        parent::tearDown();
    }

    public function test_row_skipping_preserves_values_styles_and_original_row_numbers(): void
    {
        $reader = new SimpleXlsxReader;
        $rows = iterator_to_array($reader->rowsWithStyles($this->path, 'GASS', true));
        $this->assertSame([1, 3, 4], array_keys($rows));
        $this->assertSame(['A' => 'First row', 'Z' => '1.5'], $rows[1]['values']);
        $this->assertSame([], $rows[3]['values']);
        $this->assertSame(['A' => 'Tail', 'B' => '0'], $rows[4]['values']);
        $this->assertTrue($rows[4]['styles']['A']['bold']);
        $this->assertSame([1, 2, 3, 4], array_keys(iterator_to_array($reader->rows($this->path, 'GASS'))));
    }

    public function test_stopping_a_header_scan_early_releases_the_workbook(): void
    {
        $rows = (new SimpleXlsxReader)->rowsWithStyles($this->path, 'GASS');
        $this->assertSame('First row', $rows->current()['values']['A']);
        unset($rows);
        $destination = $this->path.'.moved';
        $this->assertTrue(rename($this->path, $destination));
        $this->path = $destination;
        $this->assertCount(4, iterator_to_array((new SimpleXlsxReader)->rows($this->path, 'GASS')));
    }

    public function test_missing_sheet_reports_an_error_and_releases_the_workbook(): void
    {
        try {
            iterator_to_array((new SimpleXlsxReader)->rowsWithStyles($this->path, 'MISSING'));
            $this->fail('Expected a missing worksheet error.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Sheet not found', $exception->getMessage());
        }
        $destination = $this->path.'.moved';
        $this->assertTrue(rename($this->path, $destination));
        $this->path = $destination;
    }
}
