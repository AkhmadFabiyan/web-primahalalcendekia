<?php

namespace App\Modules\Projects\Enums;

use Filament\Support\Contracts\HasLabel;

enum ProjectArchiveStatus: string implements HasLabel
{
    case NOT_CREATED = 'NOT_CREATED';
    case PROCESSING = 'PROCESSING';
    case READY = 'READY';
    case FAILED = 'FAILED';
    case EXPIRED = 'EXPIRED';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::NOT_CREATED => 'Belum Dibuat',
            self::PROCESSING => 'Sedang Diproses',
            self::READY => 'Siap Diunduh',
            self::FAILED => 'Gagal',
            self::EXPIRED => 'Kedaluwarsa',
        };
    }
}
