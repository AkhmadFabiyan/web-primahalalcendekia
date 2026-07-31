<?php

namespace App\Modules\Payments\Services;

use App\Modules\Payments\Models\Invoice;
use App\Modules\Payments\Enums\InvoiceAudience;
use InvalidArgumentException;

class InvoiceNumberService
{
    /**
     * Generate invoice number with format: INV/PHC/YYYY/XXXX-XX-A
     * 
     * YYYY: Tahun pendaftaran klien
     * XXXX: Nomor urut klien (4 digit)
     * XX: Sequence invoice klien (2 digit)
     * A: Audience identifier (C/P)
     */
    public function generate(Invoice $invoice): string
    {
        // Must have project and client
        $client = $invoice->project->client;
        if (!$client || !$client->business_id) {
            throw new InvalidArgumentException("Invoice tidak memiliki relasi Client atau ID Klien tidak valid.");
        }

        // Parse Client ID: PHC-HAL-YYYY-XXXX
        // We use strict regex as requested by user
        if (!preg_match('/^PHC-HAL-(\d{4})-(\d{4})$/', $client->business_id, $matches)) {
            throw new InvalidArgumentException("Format ID Klien tidak valid untuk diparsing: {$client->business_id}");
        }

        $year = $matches[1];
        $clientSequence = $matches[2];
        
        $invoiceSequence = str_pad($invoice->sequence, 2, '0', STR_PAD_LEFT);
        
        $audienceCode = match ($invoice->audience) {
            InvoiceAudience::CLIENT => 'C',
            InvoiceAudience::PARTNER => 'P',
            default => throw new InvalidArgumentException("Audience Invoice tidak didukung: {$invoice->audience->value}")
        };

        return sprintf('INV/PHC/%s/%s-%s-%s', $year, $clientSequence, $invoiceSequence, $audienceCode);
    }
}
