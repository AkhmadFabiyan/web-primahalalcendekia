<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Workflows\Models\ChecklistTemplate;
use App\Modules\Workflows\Models\ChecklistTemplateItem;
use App\Modules\Workflows\Enums\TaskType;

class ChecklistTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $onlineTemplate = ChecklistTemplate::firstOrCreate(
            ['code' => 'AUDIT_PLANNING_ONLINE'],
            [
                'task_type' => TaskType::AUDIT_PLANNING->value,
                'context' => 'Audit Planning Online',
                'is_active' => true,
            ]
        );

        $onlineItems = [
            'Jadwal audit sudah dikonfirmasi.',
            'Metode audit sudah ditentukan (Online).',
            'Link pertemuan tersedia.',
            'Auditor utama sudah ditunjuk.',
            'Dokumen awal sudah diperiksa.',
            'Kontak PIC Klien tersedia.',
            'Agenda audit telah disiapkan.',
            'Catatan risiko awal tersedia jika diperlukan.',
        ];

        foreach ($onlineItems as $index => $label) {
            ChecklistTemplateItem::firstOrCreate(
                [
                    'checklist_template_id' => $onlineTemplate->id,
                    'code' => 'AUDIT_PLANNING_ONLINE_' . ($index + 1),
                ],
                [
                    'label' => $label,
                    'is_required' => true,
                    'sort_order' => $index + 1,
                ]
            );
        }

        $onsiteTemplate = ChecklistTemplate::firstOrCreate(
            ['code' => 'AUDIT_PLANNING_ONSITE'],
            [
                'task_type' => TaskType::AUDIT_PLANNING->value,
                'context' => 'Audit Planning Onsite',
                'is_active' => true,
            ]
        );

        $onsiteItems = [
            'Jadwal audit sudah dikonfirmasi.',
            'Metode audit sudah ditentukan (Onsite).',
            'Lokasi fisik kunjungan tersedia.',
            'Auditor utama sudah ditunjuk.',
            'Dokumen awal sudah diperiksa.',
            'Kontak PIC Klien tersedia.',
            'Agenda audit telah disiapkan.',
            'Peralatan atau kebutuhan lapangan telah disiapkan.',
            'Catatan risiko awal tersedia jika diperlukan.',
        ];

        foreach ($onsiteItems as $index => $label) {
            ChecklistTemplateItem::firstOrCreate(
                [
                    'checklist_template_id' => $onsiteTemplate->id,
                    'code' => 'AUDIT_PLANNING_ONSITE_' . ($index + 1),
                ],
                [
                    'label' => $label,
                    'is_required' => true,
                    'sort_order' => $index + 1,
                ]
            );
        }

        // AUDIT_EXECUTION_ONLINE
        $execOnlineTemplate = ChecklistTemplate::firstOrCreate(
            ['code' => 'AUDIT_EXECUTION_ONLINE'],
            [
                'task_type' => TaskType::AUDIT_EXECUTION->value,
                'context' => 'Audit Execution Online',
                'is_active' => true,
            ]
        );

        $execCommonItems = [
            'Identitas dan ruang lingkup audit dikonfirmasi.',
            'Opening meeting dilakukan.',
            'Dokumen pendukung diperiksa.',
            'Proses produksi diverifikasi.',
            'Bahan dan produk diverifikasi.',
            'Fasilitas diperiksa.',
            'Bukti lapangan dikumpulkan.',
            'Temuan dicatat atau dikonfirmasi tidak ada temuan.',
            'Closing meeting dilakukan.',
            'Ringkasan pelaksanaan telah diisi.',
        ];

        foreach ($execCommonItems as $index => $label) {
            ChecklistTemplateItem::firstOrCreate(
                [
                    'checklist_template_id' => $execOnlineTemplate->id,
                    'code' => 'AUDIT_EXECUTION_ONLINE_' . ($index + 1),
                ],
                [
                    'label' => $label,
                    'is_required' => true,
                    'sort_order' => $index + 1,
                ]
            );
        }

        // AUDIT_EXECUTION_ONSITE
        $execOnsiteTemplate = ChecklistTemplate::firstOrCreate(
            ['code' => 'AUDIT_EXECUTION_ONSITE'],
            [
                'task_type' => TaskType::AUDIT_EXECUTION->value,
                'context' => 'Audit Execution Onsite',
                'is_active' => true,
            ]
        );

        foreach ($execCommonItems as $index => $label) {
            ChecklistTemplateItem::firstOrCreate(
                [
                    'checklist_template_id' => $execOnsiteTemplate->id,
                    'code' => 'AUDIT_EXECUTION_ONSITE_' . ($index + 1),
                ],
                [
                    'label' => $label,
                    'is_required' => true,
                    'sort_order' => $index + 1,
                ]
            );
        }
    }
}
