<?php

namespace App\Filament\Imports\Leads;

use App\Modules\Leads\Models\Lead;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use App\Modules\Leads\Enums\LeadStatus;
use App\Modules\Clients\Enums\ClientType;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class LeadImporter extends Importer
{
    protected static ?string $model = Lead::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('source_system')
                ->label('Sistem Sumber')
                ->rules(['required', 'string', 'max:255']),
            ImportColumn::make('external_reference')
                ->label('Referensi Unik')
                ->rules(['required', 'string', 'max:255']),
            ImportColumn::make('company_name')
                ->label('Nama Perusahaan')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('business_sector')
                ->label('Sektor Bisnis')
                ->rules(['max:255']),
            ImportColumn::make('address')
                ->label('Alamat')
                ->rules(['max:500']),
            ImportColumn::make('city')
                ->label('Kota')
                ->rules(['max:255']),
            ImportColumn::make('province')
                ->label('Provinsi')
                ->rules(['max:255']),
            ImportColumn::make('pic_name')
                ->label('Nama PIC')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('pic_phone')
                ->label('Telepon PIC')
                ->requiredMapping()
                ->rules(['required', 'max:50']),
            ImportColumn::make('pic_email')
                ->label('Email PIC')
                ->requiredMapping()
                ->rules(['required', 'email', 'max:255']),
            ImportColumn::make('type')
                ->label('Tipe Klien')
                ->requiredMapping()
                ->rules(['required', 'in:DIRECT,PARTNER']),
            ImportColumn::make('partner_id')
                ->label('Mitra (ID / Nama)')
                ->relationship('partner', 'name')
                ->rules(['nullable']),
            ImportColumn::make('service_type')
                ->label('Jenis Layanan')
                ->rules(['max:255']),
            ImportColumn::make('nominal_client')
                ->label('Nominal Klien')
                ->numeric()
                ->rules(['numeric', 'min:0']),
            ImportColumn::make('nominal_partner')
                ->label('Nominal Mitra')
                ->numeric()
                ->rules(['numeric', 'min:0']),
            ImportColumn::make('marketing_email')
                ->label('Email Marketing')
                ->rules(['required', 'email']),
            ImportColumn::make('payment_scheme')
                ->label('Skema Pembayaran')
                ->rules(['nullable', 'max:255']),
            ImportColumn::make('installment_count')
                ->label('Jumlah Cicilan')
                ->numeric()
                ->rules(['nullable', 'integer', 'min:1']),
            ImportColumn::make('lead_source')
                ->label('Sumber Lead')
                ->rules(['nullable', 'max:255']),
            ImportColumn::make('notes')
                ->label('Catatan')
                ->rules(['nullable']),
        ];
    }

    public function resolveRecord(): ?Lead
    {
        return Lead::firstOrNew([
            'source_system' => $this->data['source_system'],
            'external_reference' => $this->data['external_reference'],
        ]);
    }

    protected function beforeSave(): void
    {
        // Validasi lintas-field
        $type = $this->data['type'] ?? null;
        
        if ($type === 'DIRECT') {
            $this->record->partner_id = null;
            $this->record->nominal_partner = 0;
        } elseif ($type === 'PARTNER') {
            if (empty($this->data['partner_id'])) {
                throw ValidationException::withMessages([
                    'partner_id' => 'Mitra wajib diisi jika tipe klien adalah PARTNER.',
                ]);
            }
            if (!isset($this->data['nominal_partner']) || $this->data['nominal_partner'] <= 0) {
                throw ValidationException::withMessages([
                    'nominal_partner' => 'Nominal Mitra wajib lebih besar dari 0 jika tipe klien adalah PARTNER.',
                ]);
            }
        }

        // 1. Status awal selalu DRAFT
        $this->record->status = LeadStatus::DRAFT;

        // 2. Resolve Marketing by Email
        if (!empty($this->data['marketing_email'])) {
            $marketing = User::where('email', $this->data['marketing_email'])->first();
            if ($marketing) {
                $this->record->marketing_id = $marketing->id;
            } else {
                throw ValidationException::withMessages([
                    'marketing_email' => 'Marketing dengan email ' . $this->data['marketing_email'] . ' tidak ditemukan.',
                ]);
            }
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import lead selesai dan ' . number_format($import->successful_rows) . ' baris berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' baris gagal diimpor.';
        }

        return $body;
    }
}
