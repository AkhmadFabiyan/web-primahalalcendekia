<?php

namespace App\Filament\Support;

use App\Enums\Role;
use App\Models\User;

final class RoleNavigation
{
    public const ACQUISITION = 'Akuisisi & Marketing';

    public const FINANCE = 'Keuangan';

    public const DOCUMENTS = 'Administrasi Dokumen';

    public const ENTRY = 'Entry SIHALAL';

    public const ENTRY_REVIEW = 'Review Entry';

    public const AUDIT_ASSISTANCE = 'Pendampingan Audit';

    public const AUDIT_REVIEW = 'Review Audit';

    public const FINALIZATION = 'Sertifikat & Finalisasi';

    public const MONITORING = 'Monitoring & Laporan';

    public const OPERATIONS = 'Operasional';

    public const MASTER_DATA = 'Master Data';

    public const SETTINGS = 'Pengaturan Sistem';

    /**
     * Return the navigation category that matches the signed-in user's stage.
     */
    public static function forModule(string $module, ?User $user = null): string
    {
        $user ??= auth()->user();
        $role = $user?->roles->first()?->name;

        return match ($module) {
            'leads' => self::ACQUISITION,
            'finance' => self::FINANCE,
            'settings' => self::SETTINGS,
            'reports' => self::MONITORING,
            'clients', 'projects', 'tasks' => self::workflowGroup($role, $module),
            default => self::OPERATIONS,
        };
    }

    private static function workflowGroup(?string $role, string $module): string
    {
        return match ($role) {
            Role::MARKETING->value => self::ACQUISITION,
            Role::FINANCE->value => self::FINANCE,
            Role::ADMIN->value => self::DOCUMENTS,
            Role::ENTRY->value => self::ENTRY,
            Role::SPV_ENTRY->value => self::ENTRY_REVIEW,
            Role::PENDAMPING_AUDITOR->value => self::AUDIT_ASSISTANCE,
            Role::AUDITOR->value => self::AUDIT_REVIEW,
            Role::ADMIN_PERUSAHAAN->value => self::FINALIZATION,
            Role::DIREKTUR->value, Role::MANAGER_OPERASIONAL->value => self::MONITORING,
            Role::SUPER_ADMIN->value => match ($module) {
                'clients' => self::MASTER_DATA,
                'tasks', 'projects' => self::OPERATIONS,
                default => self::OPERATIONS,
            },
            default => self::OPERATIONS,
        };
    }
}
