<?php

namespace App\Modules\Payments\Services;

use App\Modules\Payments\Models\Invoice;
use App\Modules\Settings\Services\FinancialSettingsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class InvoicePdfService
{
    public function generate(Invoice $invoice): string
    {
        $settings = app(FinancialSettingsService::class);
        
        // Cek snapshot existing (jika sudah ada, gunakan ulang - immutable principle)
        if ($invoice->snapshot) {
            $snapshot = $invoice->snapshot;
        } else {
            // Generate snapshot baru
            $snapshot = [
                'template_version' => $settings->get('invoice_template_version', '1'),
                'company_profile' => [
                    'company_name' => $settings->get('company_name'),
                    'address' => $settings->get('company_address'),
                    'phone' => $settings->get('company_phone'),
                    'email' => $settings->get('company_email'),
                ],
                'bank_account' => [
                    'bank_name' => $settings->get('bank_name'),
                    'account_number' => $settings->get('bank_account_number'),
                    'account_holder' => $settings->get('bank_account_holder'),
                ],
                'invoice_lines' => $invoice->lines->map(function($line) {
                    return [
                        'description' => $line->description,
                        'amount' => (float)$line->amount,
                    ];
                })->toArray(),
                'subtotal' => (float)$invoice->subtotal,
                'tax' => 0, // Not stored directly as column
                'discount' => (float)$invoice->discount_total,
                'total' => (float)$invoice->total,
                'issued_at' => $invoice->issued_at?->toIso8601String(),
                'due_date' => $invoice->due_date?->toIso8601String(),
                'audience' => $invoice->audience->value,
                'footer_note' => $settings->get('invoice_footer'),
            ];

            // Hanya save snapshot jika Invoice PUBLISHED, PARTIAL, atau PAID (dokumen resmi)
            if (in_array($invoice->status->value, ['PUBLISHED', 'PARTIAL', 'PAID'])) {
                $invoice->update(['snapshot' => $snapshot]);
            }
        }

        $pdf = Pdf::loadView('pdf.invoices.commercial', compact('invoice', 'snapshot'));

        $filename = 'invoices/' . $invoice->id . '.pdf';
        
        // Simpan ke private disk (lokal) - menimpa yang lama karena ini PDF generation
        Storage::disk('local')->put($filename, $pdf->output());
        
        return $filename;
    }
}
