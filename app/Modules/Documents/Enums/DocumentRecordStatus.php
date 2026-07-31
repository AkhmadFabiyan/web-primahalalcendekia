<?php

namespace App\Modules\Documents\Enums;

enum DocumentRecordStatus: string
{
    case UPLOADED = 'UPLOADED';
    case REPLACED = 'REPLACED';
    case ARCHIVED = 'ARCHIVED';

    public function label(): string
    {
        return match($this) {
            self::UPLOADED => 'Diunggah',
            self::REPLACED => 'Diganti',
            self::ARCHIVED => 'Diarsipkan',
        };
    }
}
