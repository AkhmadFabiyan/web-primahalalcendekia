<?php

namespace App\Modules\Documents\Enums;

enum DocumentModuleStatus: string
{
    case NOT_STARTED = 'NOT_STARTED';
    case IN_PROGRESS = 'IN_PROGRESS';
    case REVISION = 'REVISION';
    case COMPLETE = 'COMPLETE';

    public function label(): string
    {
        return match($this) {
            self::NOT_STARTED => 'Belum Mulai',
            self::IN_PROGRESS => 'Proses',
            self::REVISION => 'Revisi',
            self::COMPLETE => 'Lengkap',
        };
    }
}
