<?php

namespace App\Modules\Projects\Enums;

use Filament\Support\Contracts\HasLabel;

enum ProjectStatus: string implements HasLabel
{
    case WAITING_ACTIVATION = 'WAITING_ACTIVATION';
    case ACTIVE = 'ACTIVE';
    case OPERATIONAL = 'OPERATIONAL';
    case WAITING_GOVERNMENT_INVOICE = 'WAITING_GOVERNMENT_INVOICE';
    case WAITING_CERTIFICATE = 'WAITING_CERTIFICATE';
    case CERTIFICATE_ISSUED = 'CERTIFICATE_ISSUED';
    case WAITING_SETTLEMENT = 'WAITING_SETTLEMENT';
    case COMPLETED = 'COMPLETED';
    case CANCELLED = 'CANCELLED';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::WAITING_ACTIVATION => 'Menunggu Aktivasi',
            self::ACTIVE => 'Aktif',
            self::OPERATIONAL => 'Operasional',
            self::WAITING_GOVERNMENT_INVOICE => 'Menunggu Tagihan Negara',
            self::WAITING_CERTIFICATE => 'Menunggu Sertifikat',
            self::CERTIFICATE_ISSUED => 'Sertifikat Terbit',
            self::WAITING_SETTLEMENT => 'Menunggu Pelunasan',
            self::COMPLETED => 'Selesai',
            self::CANCELLED => 'Dibatalkan',
        };
    }
}
