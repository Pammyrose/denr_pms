<?php

namespace App\Support;

use RuntimeException;

class ExcelProgramHierarchy
{
    public const FIELDS = [
        'title', 'program', 'subprogram', 'subsubprogram',
        'subsubsubprogram', 'subsubsubsubprogram', 'subsubsubsubsubprogram',
    ];

    public static function field(string $label): ?string
    {
        $label = strtolower(preg_replace('/[^a-z]/i', '', $label));
        if ($label === 'project') {
            return 'title';
        }
        if (preg_match('/^(?:sub)*program$/', $label)) {
            if (! in_array($label, self::FIELDS, true)) {
                throw new RuntimeException('Excel supports at most five sub-program levels.');
            }

            return $label;
        }

        return null;
    }

    public static function heading(string $text): ?array
    {
        if (! preg_match('/^\s*((?:sub[\s\-–—]*)*program|project)\s*:\s*(.*?)\s*$/iu', $text, $matches)) {
            return null;
        }

        return [self::field($matches[1]) => $matches[2]];
    }

    public static function merge(array $context, array $values, bool $resetParent = false, bool $validate = true): array
    {
        foreach (self::FIELDS as $index => $field) {
            if (! array_key_exists($field, $values)) {
                continue;
            }
            $value = trim((string) $values[$field]);
            if ($index >= 2 && self::absent($value)) {
                $value = '';
            }
            if ($resetParent || strcasecmp(trim((string) ($context[$field] ?? '')), $value) !== 0) {
                foreach (array_slice(self::FIELDS, $index + 1) as $child) {
                    unset($context[$child]);
                }
            }
            $context[$field] = $value;
        }

        if (! $validate) {
            return $context;
        }

        foreach (self::FIELDS as $index => $field) {
            $value = (string) ($context[$field] ?? '');
            $limit = $field === 'title' ? 255 : 150;
            if (mb_strlen($value) > $limit) {
                throw new RuntimeException("Excel {$field} exceeds {$limit} characters.");
            }
            if ($index < 2 || self::absent($value)) {
                continue;
            }
            foreach (array_slice(self::FIELDS, 1, $index - 1) as $parent) {
                if (self::absent((string) ($context[$parent] ?? ''))) {
                    throw new RuntimeException("Excel {$field} requires its parent {$parent}.");
                }
            }
        }

        return $context;
    }

    private static function absent(string $value): bool
    {
        return in_array(strtolower(trim($value)), ['', 'n/a', 'na', 'not applicable'], true);
    }
}
