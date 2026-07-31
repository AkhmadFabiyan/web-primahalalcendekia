<?php

namespace App\Modules\Workflows\Enums;

use Filament\Support\Contracts\HasLabel;

enum WorkflowStatus: string implements HasLabel
{
    // Entry Track
    case ENTRY_NOT_STARTED = 'ENTRY_NOT_STARTED';
    case WAITING_CLIENT_DOCUMENTS = 'WAITING_CLIENT_DOCUMENTS';
    case DOCUMENTS_INCOMPLETE = 'DOCUMENTS_INCOMPLETE';
    case CREATING_SIHALAL_ACCOUNT = 'CREATING_SIHALAL_ACCOUNT';
    case PREPARING_SJPH_MANUAL = 'PREPARING_SJPH_MANUAL';
    case INPUTTING_MATERIALS_PRODUCTS = 'INPUTTING_MATERIALS_PRODUCTS';
    case SUBMITTED_TO_LPH = 'SUBMITTED_TO_LPH';
    case DOCUMENT_REVISION = 'DOCUMENT_REVISION';
    case ENTRY_COMPLETED = 'ENTRY_COMPLETED';

    // Companion Track
    case COMPANION_NOT_PROCESSED = 'COMPANION_NOT_PROCESSED';
    case WAITING_AUDIT_SCHEDULE = 'WAITING_AUDIT_SCHEDULE';
    case AUDIT_PREPARATION = 'AUDIT_PREPARATION';
    case FIELD_EVIDENCE_INCOMPLETE = 'FIELD_EVIDENCE_INCOMPLETE';
    case AUDIT_SCHEDULED = 'AUDIT_SCHEDULED';
    case AUDIT_IN_PROGRESS = 'AUDIT_IN_PROGRESS';
    case AUDIT_COMPLETED = 'AUDIT_COMPLETED';
    case WAITING_CLIENT_CORRECTION = 'WAITING_CLIENT_CORRECTION';
    case ASSISTANCE_COMPLETED = 'ASSISTANCE_COMPLETED';

    // Auditor Track
    case AUDITOR_NOT_PROCESSED = 'AUDITOR_NOT_PROCESSED';
    case DOCUMENT_REVIEW = 'DOCUMENT_REVIEW';
    case WAITING_FIELD_AUDIT = 'WAITING_FIELD_AUDIT';
    case FIELD_AUDIT_COMPLETED = 'FIELD_AUDIT_COMPLETED';
    case NONCONFORMITY_FOUND = 'NONCONFORMITY_FOUND';
    case WAITING_CORRECTIVE_EVIDENCE = 'WAITING_CORRECTIVE_EVIDENCE';
    case CORRECTION_ACCEPTED = 'CORRECTION_ACCEPTED';
    case AUDIT_REPORT_COMPLETED = 'AUDIT_REPORT_COMPLETED';
    case WAITING_FATWA_SESSION = 'WAITING_FATWA_SESSION';
    case FATWA_SESSION_COMPLETED = 'FATWA_SESSION_COMPLETED';
    case WAITING_BPJPH_ISSUANCE = 'WAITING_BPJPH_ISSUANCE';
    case HALAL_CERTIFICATE_ISSUED = 'HALAL_CERTIFICATE_ISSUED';

    // Document Administration
    case NOT_STARTED = 'NOT_STARTED';
    case IN_PROGRESS = 'IN_PROGRESS';
    case REVISION = 'REVISION';
    case COMPLETE = 'COMPLETE';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::ENTRY_NOT_STARTED => 'Belum Dikerjakan',
            self::WAITING_CLIENT_DOCUMENTS => 'Menunggu Dokumen Klien',
            self::DOCUMENTS_INCOMPLETE => 'Dokumen Belum Lengkap',
            self::CREATING_SIHALAL_ACCOUNT => 'Pembuatan Akun SiHalal',
            self::PREPARING_SJPH_MANUAL => 'Penyusunan Manual SJPH',
            self::INPUTTING_MATERIALS_PRODUCTS => 'Input Bahan dan Produk',
            self::SUBMITTED_TO_LPH => 'Pengajuan ke LPH',
            self::DOCUMENT_REVISION => 'Revisi Dokumen',
            self::ENTRY_COMPLETED => 'Entry Selesai',

            self::COMPANION_NOT_PROCESSED => 'Belum Diproses',
            self::WAITING_AUDIT_SCHEDULE => 'Menunggu Jadwal Audit',
            self::AUDIT_PREPARATION => 'Persiapan Audit',
            self::FIELD_EVIDENCE_INCOMPLETE => 'Bukti Lapangan Belum Lengkap',
            self::AUDIT_SCHEDULED => 'Jadwal Audit Ditentukan',
            self::AUDIT_IN_PROGRESS => 'Audit Berlangsung',
            self::AUDIT_COMPLETED => 'Audit Selesai',
            self::WAITING_CLIENT_CORRECTION => 'Menunggu Perbaikan Klien',
            self::ASSISTANCE_COMPLETED => 'Pendampingan Selesai',

            self::AUDITOR_NOT_PROCESSED => 'Belum Diproses',
            self::DOCUMENT_REVIEW => 'Pemeriksaan Dokumen',
            self::WAITING_FIELD_AUDIT => 'Menunggu Audit Lapangan',
            self::FIELD_AUDIT_COMPLETED => 'Audit Lapangan Selesai',
            self::NONCONFORMITY_FOUND => 'Ada Ketidaksesuaian',
            self::WAITING_CORRECTIVE_EVIDENCE => 'Menunggu Bukti Perbaikan',
            self::CORRECTION_ACCEPTED => 'Perbaikan Diterima',
            self::AUDIT_REPORT_COMPLETED => 'Laporan Audit Selesai',
            self::WAITING_FATWA_SESSION => 'Menunggu Sidang Fatwa',
            self::FATWA_SESSION_COMPLETED => 'Sidang Fatwa Selesai',
            self::WAITING_BPJPH_ISSUANCE => 'Menunggu Penerbitan BPJPH',
            self::HALAL_CERTIFICATE_ISSUED => 'Sertifikat Halal Terbit',

            self::NOT_STARTED => 'Belum Mulai',
            self::IN_PROGRESS => 'Proses',
            self::REVISION => 'Revisi',
            self::COMPLETE => 'Lengkap',
        };
    }
}
