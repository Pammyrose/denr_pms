<?php

namespace Tests\Unit;

use App\Support\AccomplishmentReportFormat;
use App\Support\SimpleXlsxReader;
use App\Support\SimpleXlsxWriter;
use DateTimeImmutable;
use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\TestCase;
use ZipArchive;

class AccomplishmentReportFormatTest extends TestCase
{
    public function test_reference_format_is_applied_without_changing_any_body_cell(): void
    {
        $referencePath = __DIR__.'/../../resources/templates/gass-accomplishment-format.xml';
        if (! is_file($referencePath)) {
            $this->markTestSkipped('The optional GASS reference-format asset is not installed.');
        }

        $path = sys_get_temp_dir().'/report-format-'.bin2hex(random_bytes(5)).'.xlsx';
        $rows = [
            ['pap' => 'Current major PAP', 'office' => 'CAR', 'is_financial_summary' => true, 'financial_target' => ['jan' => 123.45], 'financial_accomplishment' => ['jan' => 50]],
            ['pap' => 'Current activity', 'indicator' => 'Current indicator', 'office' => 'CAR', 'physical_target' => ['jan' => 3], 'physical_accomplishment' => ['jan' => 2]],
            ['pap' => 'Current activity', 'indicator' => 'Current indicator', 'office' => 'RO', 'physical_target' => ['jan' => 3], 'physical_accomplishment' => ['jan' => 2], 'remarks' => 'Keep this text'],
        ];
        try {
            (new SimpleXlsxWriter)->writePerformanceReport($path, 'Baseline', 'GENERAL ADMINISTRATION AND SUPPORT SERVICES (GASS)', 2026, $rows, new DateTimeImmutable('2026-10-01'));
            $after = iterator_to_array((new SimpleXlsxReader)->rows($path, 'Baseline', false));
            $this->assertSame('123.45', $after[15]['L']);
            $this->assertSame('Current indicator', $after[16]['B']);
            $this->assertSame('As of Fourth Quarter 2026', $after[2]['A']);

            $zip = new ZipArchive;
            $this->assertTrue($zip->open($path) === true);
            $sheet = $this->xml($zip->getFromName('xl/worksheets/sheet1.xml'));
            $styles = $this->xml($zip->getFromName('xl/styles.xml'));
            $theme = $this->xml($zip->getFromName('xl/theme/theme1.xml'));
            $zip->close();
            $reference = $this->xml(file_get_contents($referencePath));
            $s = new DOMXPath($sheet);
            $r = new DOMXPath($reference);
            $ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
            $s->registerNamespace('s', $ns);
            $r->registerNamespace('s', $ns);
            foreach (['cols', 'sheetFormatPr', 'sheetViews', 'pageMargins', 'pageSetup'] as $element) {
                $this->assertSame(
                    $r->query('/report-format/layout/s:'.$element)->item(0)->C14N(),
                    $s->query('/s:worksheet/s:'.$element)->item(0)->C14N(),
                    $element
                );
            }
            foreach (['fonts', 'fills', 'borders', 'numFmts'] as $element) {
                $this->assertSame($reference->getElementsByTagName($element)->item(0)->C14N(), $styles->getElementsByTagName($element)->item(0)->C14N(), $element);
            }
            $this->assertSame($reference->getElementsByTagName('theme')->item(0)->C14N(), $theme->documentElement->C14N());
            foreach ([6, 7, 8, 9, 10, 11] as $row) {
                $this->assertSame($r->query('/report-format/headers/s:row[@r="'.$row.'"]')->item(0)->C14N(), $s->query('//s:sheetData/s:row[@r="'.$row.'"]')->item(0)->C14N());
            }
            $this->assertSame('13.5', $s->query('//s:row[@r="17"]')->item(0)->getAttribute('ht'));
            $this->assertSame('162', $s->query('//s:c[@r="L15"]')->item(0)->getAttribute('s'));
            $this->assertSame('116', $s->query('//s:c[@r="D17"]')->item(0)->getAttribute('s'));
            $this->assertSame(0, $r->query('/report-format/samples//s:v | /report-format/samples//s:is')->length);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    private function xml(string $xml): DOMDocument
    {
        $document = new DOMDocument;
        $document->loadXML($xml, LIBXML_NONET);

        return $document;
    }
}
