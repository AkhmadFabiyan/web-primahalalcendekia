<?php

namespace App\Modules\Workflows\Enums;

use Filament\Support\Contracts\HasLabel;

enum WorkflowNoteType: string implements HasLabel
{
    case WORK_NOTE = 'WORK_NOTE';
    case OBSTACLE = 'OBSTACLE';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::WORK_NOTE => 'Catatan Pekerjaan',
            self::OBSTACLE => 'Kendala',
        };
    }
}
