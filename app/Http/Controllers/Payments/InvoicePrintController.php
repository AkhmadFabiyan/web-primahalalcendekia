<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Modules\Payments\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class InvoicePrintController extends Controller
{
    public function __invoke(string $id)
    {
        $invoice = Invoice::findOrFail($id);
        
        // Cek permission jika diperlukan (misalnya abort(403) jika bukan Finance/SuperAdmin)
        // Di sini kita asumsikan middleware sudah membatasi akses

        // Load relasi jika diperlukan walau snapshot diutamakan
        $invoice->load(['project.client', 'partner']);
        
        $snapshot = $invoice->billing_snapshot;
        if (empty($snapshot)) {
            // Fallback to real time data if somehow snapshot is missing, though Publish should guarantee it
            $client = $invoice->project->client;
            $snapshot = [
                'company_name' => $client->company_name ?? 'N/A',
                'address' => $client->address ?? '',
                'city' => $client->city ?? '',
                'province' => $client->province ?? '',
                'pic_name' => $client->pic_name ?? '',
                'pic_phone' => $client->pic_phone ?? '',
                'pic_email' => $client->pic_email ?? '',
                'billing_target_name' => $invoice->audience === \App\Modules\Payments\Enums\InvoiceAudience::PARTNER && $invoice->partner 
                    ? $invoice->partner->name 
                    : ($client->company_name ?? 'N/A'),
                'project_service_type' => $invoice->project->service_type ?? 'N/A',
            ];
        }

        $pdf = Pdf::loadView('payments.invoice-print', compact('invoice', 'snapshot'));
        
        return $pdf->stream('Invoice-' . str_replace('/', '-', $invoice->invoice_number) . '.pdf');
    }
}
