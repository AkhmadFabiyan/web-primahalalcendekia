<?php

namespace Database\Seeders;

use App\Enums\Permission as PermissionEnum;
use App\Enums\Role as RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Bersihkan cache sebelum mapping
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Buat seluruh Permission secara idempotent
        $permissions = [];
        foreach (PermissionEnum::cases() as $permissionEnum) {
            $permissions[$permissionEnum->value] = Permission::firstOrCreate([
                'name' => $permissionEnum->value,
                'guard_name' => 'web'
            ]);
        }

        // 2. Buat seluruh Role secara idempotent
        $roles = [];
        foreach (RoleEnum::cases() as $roleEnum) {
            $roles[$roleEnum->value] = Role::firstOrCreate([
                'name' => $roleEnum->value,
                'guard_name' => 'web'
            ]);
        }

        // 3. Mapping hak akses (syncPermissions)
        
        // Super Admin: Semua permission yang tersedia
        $roles[RoleEnum::SUPER_ADMIN->value]->syncPermissions(Permission::all());

        // Direktur
        $roles[RoleEnum::DIREKTUR->value]->syncPermissions([
            $permissions[PermissionEnum::ViewDashboard->value],
            $permissions[PermissionEnum::ViewLeads->value],
            $permissions[PermissionEnum::ViewClients->value],
            $permissions[PermissionEnum::ViewTasks->value],
            $permissions[PermissionEnum::ViewInvoices->value],
            $permissions[PermissionEnum::ViewDocuments->value],
            $permissions[PermissionEnum::ViewCertificates->value],
            $permissions[PermissionEnum::ViewReports->value],
        ]);

        // Manager Operasional
        $roles[RoleEnum::MANAGER_OPERASIONAL->value]->syncPermissions([
            $permissions[PermissionEnum::ViewDashboard->value],
            $permissions[PermissionEnum::ViewLeads->value],
            $permissions[PermissionEnum::ViewClients->value],
            $permissions[PermissionEnum::ViewTasks->value],
            $permissions[PermissionEnum::ViewInvoices->value],
            $permissions[PermissionEnum::ViewDocuments->value],
            $permissions[PermissionEnum::ViewCertificates->value],
            $permissions[PermissionEnum::ViewReports->value],
        ]);

        // Marketing
        $roles[RoleEnum::MARKETING->value]->syncPermissions([
            $permissions[PermissionEnum::ViewDashboard->value],
            $permissions[PermissionEnum::ViewLeads->value],
            $permissions[PermissionEnum::CreateLeads->value],
            $permissions[PermissionEnum::UpdateLeads->value],
            $permissions[PermissionEnum::ChangeStatusLeads->value],
            $permissions[PermissionEnum::ViewClients->value],
            $permissions[PermissionEnum::ViewCertificates->value],
        ]);

        // Finance
        $roles[RoleEnum::FINANCE->value]->syncPermissions([
            $permissions[PermissionEnum::ViewDashboard->value],
            $permissions[PermissionEnum::ViewClients->value],
            $permissions[PermissionEnum::ViewInvoices->value],
            $permissions[PermissionEnum::PublishInvoices->value],
            $permissions[PermissionEnum::ViewPayments->value],
            $permissions[PermissionEnum::VerifyPayments->value],
            $permissions[PermissionEnum::ViewDocuments->value],
            $permissions[PermissionEnum::ViewCertificates->value],
        ]);

        // Admin
        $roles[RoleEnum::ADMIN->value]->syncPermissions([
            $permissions[PermissionEnum::ViewDashboard->value],
            $permissions[PermissionEnum::ViewClients->value],
            $permissions[PermissionEnum::UpdateClients->value],
            $permissions[PermissionEnum::ViewTasks->value],
            $permissions[PermissionEnum::UpdateTasks->value],
            $permissions[PermissionEnum::ViewDocuments->value],
            $permissions[PermissionEnum::UploadDocuments->value],
            $permissions[PermissionEnum::UpdateDocuments->value],
            $permissions[PermissionEnum::ViewCertificates->value],
        ]);

        // Entry
        $roles[RoleEnum::ENTRY->value]->syncPermissions([
            $permissions[PermissionEnum::ViewDashboard->value],
            $permissions[PermissionEnum::ViewClients->value],
            $permissions[PermissionEnum::ViewTasks->value],
            $permissions[PermissionEnum::UpdateTasks->value],
            $permissions[PermissionEnum::ViewDocuments->value],
            $permissions[PermissionEnum::UpdateAssignedEntry->value],
            $permissions[PermissionEnum::ViewCertificates->value],
        ]);

        // SPV Entry
        $roles[RoleEnum::SPV_ENTRY->value]->syncPermissions([
            $permissions[PermissionEnum::ViewDashboard->value],
            $permissions[PermissionEnum::ViewClients->value],
            $permissions[PermissionEnum::ViewTasks->value],
            $permissions[PermissionEnum::UpdateTasks->value],
            $permissions[PermissionEnum::ViewDocuments->value],
            $permissions[PermissionEnum::ReviewEntry->value],
            $permissions[PermissionEnum::ViewCertificates->value],
        ]);

        // Pendamping Auditor
        $roles[RoleEnum::PENDAMPING_AUDITOR->value]->syncPermissions([
            $permissions[PermissionEnum::ViewDashboard->value],
            $permissions[PermissionEnum::ViewClients->value],
            $permissions[PermissionEnum::ViewTasks->value],
            $permissions[PermissionEnum::UpdateTasks->value],
            $permissions[PermissionEnum::UploadDocuments->value],
            $permissions[PermissionEnum::UpdateAssignedCompanion->value],
            $permissions[PermissionEnum::ViewCertificates->value],
        ]);

        // Auditor
        $roles[RoleEnum::AUDITOR->value]->syncPermissions([
            $permissions[PermissionEnum::ViewDashboard->value],
            $permissions[PermissionEnum::ViewClients->value],
            $permissions[PermissionEnum::ViewTasks->value],
            $permissions[PermissionEnum::UpdateTasks->value],
            $permissions[PermissionEnum::ViewDocuments->value],
            $permissions[PermissionEnum::UpdateAssignedAuditor->value],
            $permissions[PermissionEnum::ViewCertificates->value],
        ]);

        // Admin Perusahaan
        $roles[RoleEnum::ADMIN_PERUSAHAAN->value]->syncPermissions([
            $permissions[PermissionEnum::ViewDashboard->value],
            $permissions[PermissionEnum::ViewClients->value],
            $permissions[PermissionEnum::UpdateClients->value],
            $permissions[PermissionEnum::ViewTasks->value],
            $permissions[PermissionEnum::UpdateTasks->value],
            $permissions[PermissionEnum::ViewInvoices->value],
            $permissions[PermissionEnum::UploadDocuments->value],
            $permissions[PermissionEnum::UpdatePostAudit->value],
            $permissions[PermissionEnum::UploadCertificates->value],
        ]);

        // Klien
        $roles[RoleEnum::KLIEN->value]->syncPermissions([
            $permissions[PermissionEnum::ViewDashboard->value],
        ]);

        // Bersihkan cache setelah mapping
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
