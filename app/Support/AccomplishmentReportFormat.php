<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;
use ZipArchive;

/** Applies the reference's presentation without changing report data cells. */
class AccomplishmentReportFormat
{
    private const NS = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    public function apply(string $path, array $rows): void
    {
        $referencePath = __DIR__.'/../../resources/templates/gass-accomplishment-format.xml';
        if (! is_file($referencePath)) {
            return;
        }

        $referenceXml = file_get_contents($referencePath);
        if ($referenceXml === false) {
            return;
        }

        $reference = $this->document($referenceXml);
        $ref = new DOMXPath($reference);
        $ref->registerNamespace('s', self::NS);
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Cannot format the accomplishment report.');
        }

        try {
            $sheet = $this->document($zip->getFromName('xl/worksheets/sheet1.xml'));
            $xpath = new DOMXPath($sheet);
            $xpath->registerNamespace('s', self::NS);
            $styles = $this->document($reference->saveXML($ref->query('/report-format/s:styleSheet')->item(0)));
            $xfs = $styles->getElementsByTagName('cellXfs')->item(0);
            $labelStyles = [];
            $styleForLabel = function (int $style) use ($styles, $xfs, &$labelStyles): int {
                if (! isset($labelStyles[$style])) {
                    $copy = $xfs->getElementsByTagName('xf')->item($style)->cloneNode(true);
                    $alignment = $copy->getElementsByTagName('alignment')->item(0)
                        ?? $copy->appendChild($styles->createElementNS(self::NS, 'alignment'));
                    // Current labels occupy merged office blocks, unlike the
                    // manually split text lines in the source. Keep all text visible.
                    $alignment->setAttribute('vertical', 'top');
                    $alignment->setAttribute('wrapText', '1');
                    $copy->setAttribute('applyAlignment', '1');
                    $labelStyles[$style] = $xfs->getElementsByTagName('xf')->length;
                    $xfs->appendChild($copy);
                    $xfs->setAttribute('count', (string) $xfs->getElementsByTagName('xf')->length);
                }

                return $labelStyles[$style];
            };

            foreach (['sheetPr', 'sheetViews', 'sheetFormatPr', 'cols', 'printOptions', 'pageMargins', 'pageSetup', 'headerFooter'] as $name) {
                $source = $ref->query('/report-format/layout/s:'.$name)->item(0);
                $target = $xpath->query('/s:worksheet/s:'.$name)->item(0);
                if ($source) {
                    $copy = $sheet->importNode($source, true);
                    if ($target) {
                        $target->parentNode->replaceChild($copy, $target);
                    } else {
                        $sheet->documentElement->appendChild($copy);
                    }
                } elseif ($target) {
                    $target->parentNode->removeChild($target);
                }
            }
            $data = $xpath->query('/s:worksheet/s:sheetData')->item(0);
            $preservedCells = [];
            foreach (['A2', 'A12', 'A13'] as $address) {
                $cell = $xpath->query('//s:c[@r="'.$address.'"]')->item(0);
                if ($cell) {
                    $preservedCells[$address] = $cell->cloneNode(true);
                }
            }
            foreach ($ref->query('/report-format/headers/s:row') as $row) {
                $number = $row->getAttribute('r');
                $current = $xpath->query('s:row[@r="'.$number.'"]', $data)->item(0);
                $copy = $sheet->importNode($row, true);
                foreach ($preservedCells as $address => $preserved) {
                    if ($address !== 'A'.$number) {
                        continue;
                    }
                    $cell = $copy->getElementsByTagName('c')->item(0);
                    $preserved->setAttribute('s', $cell?->getAttribute('s') ?: '0');
                    if ($cell) {
                        $copy->replaceChild($preserved, $cell);
                    } else {
                        $copy->appendChild($preserved);
                    }
                }
                if ($number === '13' && ! isset($preservedCells['A13'])) {
                    foreach (iterator_to_array($copy->getElementsByTagName('c')) as $cell) {
                        $copy->removeChild($cell);
                    }
                }
                $data->replaceChild($copy, $current);
            }
            $merges = $xpath->query('/s:worksheet/s:mergeCells')->item(0);
            foreach (iterator_to_array($merges->childNodes) as $merge) {
                if ($merge instanceof DOMElement && preg_match('/:(?:[A-Z]+)(\d+)$/', $merge->getAttribute('ref'), $match) && (int) $match[1] < 15) {
                    $merges->removeChild($merge);
                }
            }
            foreach ($ref->query('/report-format/header-merges/s:mergeCell') as $merge) {
                $merges->appendChild($sheet->importNode($merge, true));
            }
            $merges->setAttribute('count', (string) $merges->getElementsByTagName('mergeCell')->length);

            $samples = [];
            foreach ($ref->query('/report-format/samples/sample') as $sample) {
                $cells = [];
                foreach ($sample->getElementsByTagName('c') as $cell) {
                    $cells[preg_replace('/\d+/', '', $cell->getAttribute('r'))] = (int) $cell->getAttribute('s');
                }
                $samples[$sample->getAttribute('kind')] = $cells;
            }
            $mergedHeights = [];
            foreach ($merges->getElementsByTagName('mergeCell') as $merge) {
                if (preg_match('/^([AB])(\d+):[AB](\d+)$/', $merge->getAttribute('ref'), $match)) {
                    $mergedHeights[$match[1].$match[2]] = (int) $match[3] - (int) $match[2] + 1;
                }
            }
            foreach ($xpath->query('s:row[number(@r) >= 15]', $data) as $row) {
                $number = (int) $row->getAttribute('r');
                $entry = $rows[$number - 15] ?? [];
                $office = strtoupper(trim((string) ($entry['office'] ?? '')));
                $isFinancial = (bool) ($entry['is_financial_summary'] ?? false);
                $kind = $isFinancial
                    ? ($office === 'CAR' ? 'financial-car' : 'financial-office')
                    : ($office === 'CAR' ? 'physical-car' : (in_array($office, ['ABRA', 'APAYAO', 'BENGUET', 'IFUGAO', 'KALINGA', 'MT.PROVINCE'], true) ? 'physical-province' : 'physical-office'));
                if ($office === '') {
                    $kind = 'section';
                }
                $height = 13.5;
                foreach ($row->getElementsByTagName('c') as $cell) {
                    $column = preg_replace('/\d+/', '', $cell->getAttribute('r'));
                    $style = $samples[$kind][$column];
                    if (in_array($column, ['A', 'B'], true)) {
                        $style = $styleForLabel($column === 'A' && $isFinancial ? $samples['financial-car']['A'] : $style);
                        $text = $cell->textContent;
                        $lines = 0;
                        foreach (explode("\n", $text) as $line) {
                            $lines += max(1, (int) ceil(mb_strlen($line) / ($column === 'A' ? 52 : 50)));
                        }
                        $span = $mergedHeights[$cell->getAttribute('r')] ?? 1;
                        $height = max($height, ceil($lines / $span) * 13.5);
                    }
                    $cell->setAttribute('s', (string) $style);
                }
                $row->setAttribute('ht', (string) $height);
                $row->setAttribute('customHeight', '1');
            }
            $zip->addFromString('xl/worksheets/sheet1.xml', $sheet->saveXML());
            $zip->addFromString('xl/styles.xml', $styles->saveXML());
            $theme = $ref->query('/report-format/*[local-name()="theme"]')->item(0);
            $zip->addFromString('xl/theme/theme1.xml', $reference->saveXML($theme));
            $relations = $this->document($zip->getFromName('xl/_rels/workbook.xml.rels'));
            $relation = $relations->createElementNS($relations->documentElement->namespaceURI, 'Relationship');
            $relation->setAttribute('Id', 'rIdReportTheme');
            $relation->setAttribute('Type', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/theme');
            $relation->setAttribute('Target', 'theme/theme1.xml');
            $relations->documentElement->appendChild($relation);
            $zip->addFromString('xl/_rels/workbook.xml.rels', $relations->saveXML());
            $contentTypes = $this->document($zip->getFromName('[Content_Types].xml'));
            $type = $contentTypes->createElementNS($contentTypes->documentElement->namespaceURI, 'Override');
            $type->setAttribute('PartName', '/xl/theme/theme1.xml');
            $type->setAttribute('ContentType', 'application/vnd.openxmlformats-officedocument.theme+xml');
            $contentTypes->documentElement->appendChild($type);
            $zip->addFromString('[Content_Types].xml', $contentTypes->saveXML());
        } finally {
            $zip->close();
        }
    }

    private function document(string $xml): DOMDocument
    {
        $document = new DOMDocument;
        if (! $document->loadXML($xml, LIBXML_NONET)) {
            throw new RuntimeException('Invalid report formatting XML.');
        }

        return $document;
    }
}
