<?php

namespace App\Modules\Payments\Services;

use App\Modules\Payments\Models\Receipt;
use App\Modules\Settings\Services\FinancialSettingsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use App\Modules\Payments\Enums\ReceiptType;

class ReceiptPdfService
{
    public function generate(Receipt $receipt): string
    {
        $settings = app(FinancialSettingsService::class);
        
        if ($receipt->snapshot) {
            $snapshot = $receipt->snapshot;
        } else {
            $snapshot = [
                'template_version' => $settings->get('receipt_template_version', '1'),
                'company_profile' => [
                    'company_name' => $settings->get('company_name'),
                ],
                'issued_at' => $receipt->issued_at?->toIso8601String(),
                'footer_note' => $settings->get('receipt_footer'),
                'payments' => [] // untuk SETTLEMENT
            ];

            if ($receipt->receipt_type === ReceiptType::SETTLEMENT_RECEIPT) {
                $payments = $receipt->invoice->payments()->where('status', 'VERIFIED')->get();
                $snapshot['payments'] = $payments->map(function($pay) {
                    return [
                        'payment_date' => $pay->payment_date?->toIso8601String(),
                        'reference_number' => $pay->reference_number,
                        'amount' => (float)$pay->amount,
                    ];
                })->toArray();
            }
            
            $receipt->update(['snapshot' => $snapshot, 'template_version' => $snapshot['template_version']]);
        }

        $view = $receipt->receipt_type === ReceiptType::PAYMENT_RECEIPT ? 'pdf.receipts.payment' : 'pdf.receipts.settlement';
        $pdf = Pdf::loadView($view, compact('receipt', 'snapshot'));

        $filename = 'receipts/' . $receipt->id . '.pdf';
        Storage::disk('local')->put($filename, $pdf->output());
        
        return $filename;
    }
}
