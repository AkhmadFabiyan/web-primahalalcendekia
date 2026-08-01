<?php

namespace App\Modules\Reports\DataTransferObjects;

use Carbon\Carbon;

class ManagementReportFilterData
{
    public function __construct(
        public ?string $preset = 'this_month',
        public ?string $start_date = null,
        public ?string $end_date = null,
        public ?string $marketing_id = null,
        public ?string $admin_id = null,
        public ?string $entry_id = null,
        public ?string $pendamping_id = null,
        public ?string $auditor_id = null,
        public ?string $layanan_id = null,
        public ?string $client_type = null,
        public ?string $partner_id = null,
        public ?string $provinsi_id = null,
        public ?string $kota_id = null,
        public ?string $status_project = null,
        public ?string $status_workflow = null,
    ) {
        $this->applyPreset();
    }

    protected function applyPreset()
    {
        $now = Carbon::now();
        
        switch ($this->preset) {
            case 'this_month':
                $this->start_date = $now->copy()->startOfMonth()->toDateString();
                $this->end_date = $now->copy()->endOfMonth()->toDateString();
                break;
            case 'last_month':
                $this->start_date = $now->copy()->subMonth()->startOfMonth()->toDateString();
                $this->end_date = $now->copy()->subMonth()->endOfMonth()->toDateString();
                break;
            case 'this_quarter':
                $this->start_date = $now->copy()->startOfQuarter()->toDateString();
                $this->end_date = $now->copy()->endOfQuarter()->toDateString();
                break;
            case 'this_year':
                $this->start_date = $now->copy()->startOfYear()->toDateString();
                $this->end_date = $now->copy()->endOfYear()->toDateString();
                break;
            case 'last_12_months':
                $this->start_date = $now->copy()->subMonths(11)->startOfMonth()->toDateString();
                $this->end_date = $now->copy()->endOfMonth()->toDateString();
                break;
            case 'custom':
            default:
                if (!$this->start_date) {
                    $this->start_date = $now->copy()->startOfMonth()->toDateString();
                }
                if (!$this->end_date) {
                    $this->end_date = $now->copy()->endOfMonth()->toDateString();
                }
                break;
        }
    }

    public static function fromArray(array $data): self
    {
        return new self(
            preset: $data['preset'] ?? 'this_month',
            start_date: $data['start_date'] ?? null,
            end_date: $data['end_date'] ?? null,
            marketing_id: $data['marketing_id'] ?? null,
            admin_id: $data['admin_id'] ?? null,
            entry_id: $data['entry_id'] ?? null,
            pendamping_id: $data['pendamping_id'] ?? null,
            auditor_id: $data['auditor_id'] ?? null,
            layanan_id: $data['layanan_id'] ?? null,
            client_type: $data['client_type'] ?? null,
            partner_id: $data['partner_id'] ?? null,
            provinsi_id: $data['provinsi_id'] ?? null,
            kota_id: $data['kota_id'] ?? null,
            status_project: $data['status_project'] ?? null,
            status_workflow: $data['status_workflow'] ?? null,
        );
    }
    
    public function getCacheHash(): string
    {
        return md5(json_encode([
            $this->start_date,
            $this->end_date,
            $this->marketing_id,
            $this->admin_id,
            $this->entry_id,
            $this->pendamping_id,
            $this->auditor_id,
            $this->layanan_id,
            $this->client_type,
            $this->partner_id,
            $this->provinsi_id,
            $this->kota_id,
            $this->status_project,
            $this->status_workflow,
        ]));
    }
}
