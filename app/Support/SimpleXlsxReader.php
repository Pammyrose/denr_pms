<?php

namespace App\Support;

use Generator;
use RuntimeException;
use SimpleXMLElement;
use XMLReader;
use ZipArchive;

class SimpleXlsxReader
{
    /** @return array<int, string> */
    public function sheetNames(string $path): array
    {
        if (! is_file($path)) {
            throw new RuntimeException("File not found: {$path}");
        }

        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Unable to open Excel file.');
        }

        try {
            $workbookXml = $zip->getFromName('xl/workbook.xml');
            if ($workbookXml === false) {
                throw new RuntimeException('Unable to read the Excel workbook.');
            }

            $workbook = simplexml_load_string($workbookXml);
            if (! $workbook instanceof SimpleXMLElement) {
                throw new RuntimeException('Unable to parse the Excel workbook.');
            }

            $names = [];
            foreach ($workbook->sheets->sheet as $sheet) {
                $name = trim((string) ($sheet->attributes()['name'] ?? ''));
                if ($name !== '') {
                    $names[] = $name;
                }
            }

            return $names;
        } finally {
            $zip->close();
        }
    }

    /** @param array<int, string> $candidates */
    public function resolveSheetName(string $path, array $candidates): string
    {
        $sheetNames = $this->sheetNames($path);

        foreach ($candidates as $candidate) {
            foreach ($sheetNames as $sheetName) {
                if (strcasecmp(trim($sheetName), trim((string) $candidate)) === 0) {
                    return $sheetName;
                }
            }
        }

        $expected = implode(' or ', array_map(
            static fn (string $name): string => '"'.$name.'"',
            array_values(array_filter(array_map('trim', $candidates)))
        ));

        throw new RuntimeException('Sheet not found: '.$expected);
    }

    public function rows(string $path, string $sheetName, bool $skipHiddenRows = false): Generator
    {
        foreach ($this->rowsWithStyles($path, $sheetName, $skipHiddenRows) as $rowNumber => $row) {
            yield $rowNumber => $row['values'];
        }
    }

    public function rowsWithStyles(string $path, string $sheetName, bool $skipHiddenRows = false): Generator
    {
        if (! is_file($path)) {
            throw new RuntimeException("File not found: {$path}");
        }

        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Unable to open Excel file.');
        }

        $reader = null;
        try {
            $sharedStrings = $this->sharedStrings($zip);
            $styles = $this->styles($zip);
            $sheetPath = $this->sheetPath($zip, $sheetName);
            $sheetXml = $zip->getFromName($sheetPath);

            if ($sheetXml === false) {
                throw new RuntimeException("Unable to read sheet: {$sheetName}");
            }

            $reader = new XMLReader;
            $reader->XML($sheetXml);

            // Position at the first row, then skip entire row subtrees.
            while ($reader->read() && ($reader->nodeType !== XMLReader::ELEMENT || $reader->name !== 'row')) {
            }
            while ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'row') {

                if ($skipHiddenRows && (string) $reader->getAttribute('hidden') === '1') {
                    $reader->next('row');

                    continue;
                }

                $rowXml = simplexml_load_string($reader->readOuterXML());
                $rowNumber = (int) $rowXml->attributes()['r'];
                $cells = [];
                $cellStyles = [];

                foreach ($rowXml->c as $cell) {
                    $cellAttrs = $cell->attributes();
                    $ref = (string) ($cellAttrs['r'] ?? '');
                    $type = (string) ($cellAttrs['t'] ?? '');
                    $styleIndex = (int) ($cellAttrs['s'] ?? 0);
                    $column = preg_replace('/\d+/', '', $ref);
                    $value = isset($cell->v) ? (string) $cell->v : '';
                    $sharedStringStyle = ['bold' => false];

                    if ($type === 's' && $value !== '') {
                        $sharedString = $sharedStrings[(int) $value] ?? null;
                        if (is_array($sharedString)) {
                            $value = (string) ($sharedString['text'] ?? $value);
                            $sharedStringStyle = ['bold' => (bool) ($sharedString['bold'] ?? false)];
                        } else {
                            $value = $sharedString ?? $value;
                        }
                    } elseif ($type === 'inlineStr' && isset($cell->is->t)) {
                        $value = (string) $cell->is->t;
                    }

                    $value = trim(preg_replace('/\s+/', ' ', $value));
                    if ($value !== '') {
                        $cells[$column] = $value;
                        $cellStyle = $styles[$styleIndex] ?? ['bold' => false, 'fill' => null, 'fill_type' => null];
                        $cellStyle['bold'] = (bool) ($cellStyle['bold'] ?? false) || (bool) ($sharedStringStyle['bold'] ?? false);
                        $cellStyles[$column] = $cellStyle;
                    }
                }

                yield $rowNumber => [
                    'values' => $cells,
                    'styles' => $cellStyles,
                ];
                $reader->next('row');
            }
        } finally {
            $reader?->close();
            $zip->close();
        }
    }

    private function sharedStrings(ZipArchive $zip): array
    {
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml === false) {
            return [];
        }

        $strings = [];
        $xml = simplexml_load_string($sharedXml);
        foreach ($xml->si as $si) {
            $parts = [];
            $isBold = false;
            if (isset($si->t)) {
                $parts[] = (string) $si->t;
            }
            foreach ($si->r as $run) {
                $parts[] = (string) $run->t;
                if (isset($run->rPr->b)) {
                    $isBold = true;
                }
            }
            $strings[] = [
                'text' => implode('', $parts),
                'bold' => $isBold,
            ];
        }

        return $strings;
    }

    private function styles(ZipArchive $zip): array
    {
        $stylesXml = $zip->getFromName('xl/styles.xml');
        if ($stylesXml === false) {
            return [];
        }

        $xml = simplexml_load_string($stylesXml);
        if (! $xml instanceof SimpleXMLElement) {
            return [];
        }

        $fonts = [];
        foreach ($xml->fonts->font ?? [] as $font) {
            $fonts[] = isset($font->b);
        }

        $fills = [];
        foreach ($xml->fills->fill ?? [] as $fill) {
            $patternFill = $fill->patternFill ?? null;
            $fgColor = $patternFill?->fgColor ?? null;
            $attrs = $fgColor instanceof SimpleXMLElement ? $fgColor->attributes() : null;
            $patternAttrs = $patternFill instanceof SimpleXMLElement ? $patternFill->attributes() : null;
            $fills[] = [
                'color' => $attrs ? (string) ($attrs['rgb'] ?? $attrs['indexed'] ?? $attrs['theme'] ?? '') : null,
                'type' => $patternAttrs ? (string) ($patternAttrs['patternType'] ?? '') : null,
            ];
        }

        $styles = [];
        foreach ($xml->cellXfs->xf ?? [] as $xf) {
            $attrs = $xf->attributes();
            $fontId = (int) ($attrs['fontId'] ?? 0);
            $fillId = (int) ($attrs['fillId'] ?? 0);
            $fill = $fills[$fillId] ?? ['color' => null, 'type' => null];
            $styles[] = [
                'bold' => $fonts[$fontId] ?? false,
                'fill' => $fill['color'] ?? null,
                'fill_type' => $fill['type'] ?? null,
            ];
        }

        return $styles;
    }

    private function sheetPath(ZipArchive $zip, string $sheetName): string
    {
        $workbook = simplexml_load_string($zip->getFromName('xl/workbook.xml'));
        $rels = simplexml_load_string($zip->getFromName('xl/_rels/workbook.xml.rels'));
        $relMap = [];

        foreach ($rels->Relationship as $rel) {
            $attrs = $rel->attributes();
            $target = (string) $attrs['Target'];
            $relMap[(string) $attrs['Id']] = str_starts_with($target, 'xl/')
                ? $target
                : 'xl/'.ltrim($target, '/');
        }

        $workbook->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        foreach ($workbook->sheets->sheet as $sheet) {
            $attrs = $sheet->attributes();
            if (strcasecmp(trim((string) $attrs['name']), trim($sheetName)) !== 0) {
                continue;
            }

            $relAttrs = $sheet->attributes('r', true);

            return $relMap[(string) $relAttrs['id']] ?? throw new RuntimeException("Sheet path missing: {$sheetName}");
        }

        throw new RuntimeException("Sheet not found: {$sheetName}");
    }
}
