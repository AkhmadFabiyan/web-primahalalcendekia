<?php

namespace App\Enums;

enum Role: string
{
    case SUPER_ADMIN = 'Super Admin';
    case DIREKTUR = 'Direktur';
    case MANAGER_OPERASIONAL = 'Manager Operasional';
    case MARKETING = 'Marketing';
    case FINANCE = 'Finance';
    case ADMIN = 'Admin';
    case ENTRY = 'Entry';
    case SPV_ENTRY = 'SPV Entry';
    case PENDAMPING_AUDITOR = 'Pendamping Auditor';
    case AUDITOR = 'Auditor';
    case ADMIN_PERUSAHAAN = 'Admin Perusahaan';
    case KLIEN = 'Klien';
}
