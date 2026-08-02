<?php

namespace App\Filament\Exports;

/**
 * Backwards-compatible name retained for report integrations.
 */
class ProjectExporter extends ProjectReportExporter
{
    protected static function sanitizeCsvFormula(mixed $value): mixed
    {
        if (is_string($value) && preg_match('/^[=+\-@]/', $value)) {
            return "'{$value}";
        }

        return $value;
    }
}
