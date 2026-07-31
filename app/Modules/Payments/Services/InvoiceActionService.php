<?php

namespace App\Modules\Payments\Services;

use App\Modules\Payments\Models\Invoice;
use App\Modules\Payments\Enums\InvoiceStatus;
use App\Modules\Payments\Enums\InvoiceAudience;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InvoiceActionService
{
    public function __construct(
        private InvoiceNumberService $numberService
    ) {}

    /**
     * Publish seluruh invoice dalam satu billing group secara atomik.
     * 
     * @param string $billingGroupId
     * @return array Invoices yang dipublish
     */
    public function publishGroup(string $billingGroupId): array
    {
        return DB::transaction(function () use ($billingGroupId) {
            $invoices = Invoice::where('billing_group_id', $billingGroupId)
                ->with(['project.client', 'partner'])
                ->lockForUpdate()
                ->get();

            if ($invoices->isEmpty()) {
                throw new InvalidArgumentException("Invoice tidak ditemukan untuk billing group ini.");
            }

            $publishedInvoices = [];

            foreach ($invoices as $invoice) {
                // Idempotency: Jika sudah terbit, lewati
                if ($invoice->status === InvoiceStatus::PUBLISHED || $invoice->status === InvoiceStatus::PARTIAL || $invoice->status === InvoiceStatus::PAID) {
                    $publishedInvoices[] = $invoice;
                    continue;
                }

                if ($invoice->status !== InvoiceStatus::DRAFT) {
                    throw new InvalidArgumentException("Hanya Invoice berstatus DRAFT yang dapat diterbitkan. (Ditemukan status: {$invoice->status->value})");
                }

                if ($invoice->total <= 0) {
                    throw new InvalidArgumentException("Nominal Invoice harus lebih dari 0.");
                }

                if (empty($invoice->due_date)) {
                    throw new InvalidArgumentException("Jatuh Tempo (Due Date) wajib diisi sebelum menerbitkan Invoice.");
                }

                if ($invoice->audience === InvoiceAudience::PARTNER && $invoice->discount_total > 0) {
                    throw new InvalidArgumentException("Invoice Mitra tidak boleh memiliki diskon.");
                }

                // Generate Snapshot
                $client = $invoice->project->client;
                $snapshot = [
                    'client_id' => $client->business_id,
                    'company_name' => $client->company_name,
                    'address' => $client->address,
                    'city' => $client->city,
                    'province' => $client->province,
                    'pic_name' => $client->pic_name,
                    'pic_phone' => $client->pic_phone,
                    'pic_email' => $client->pic_email,
                    'audience' => $invoice->audience->value,
                    'project_service_type' => $invoice->project->service_type,
                    'billing_target_name' => $invoice->audience === InvoiceAudience::PARTNER && $invoice->partner 
                        ? $invoice->partner->name 
                        : $client->company_name
                ];

                $invoice->billing_snapshot = $snapshot;
                $invoice->invoice_number = $this->numberService->generate($invoice);
                $invoice->issued_at = now();
                $invoice->status = InvoiceStatus::PUBLISHED;
                
                $invoice->save();

                // Log activity
                activity()
                    ->performedOn($invoice)
                    ->event('published')
                    ->log('Invoice berhasil diterbitkan');

                $publishedInvoices[] = $invoice;
            }

            return $publishedInvoices;
        });
    }

    /**
     * Membatalkan seluruh invoice dalam satu billing group secara atomik.
     * 
     * @param string $billingGroupId
     * @param string $reason
     * @return array Invoices yang dibatalkan
     */
    public function cancelGroup(string $billingGroupId, string $reason): array
    {
        if (empty(trim($reason))) {
            throw new InvalidArgumentException("Alasan pembatalan wajib diisi.");
        }

        return DB::transaction(function () use ($billingGroupId, $reason) {
            $invoices = Invoice::where('billing_group_id', $billingGroupId)
                ->lockForUpdate()
                ->get();

            if ($invoices->isEmpty()) {
                throw new InvalidArgumentException("Invoice tidak ditemukan.");
            }

            $cancelledInvoices = [];

            foreach ($invoices as $invoice) {
                // Idempotency
                if ($invoice->status === InvoiceStatus::CANCELLED) {
                    $cancelledInvoices[] = $invoice;
                    continue;
                }

                if (in_array($invoice->status, [InvoiceStatus::PARTIAL, InvoiceStatus::PAID])) {
                    throw new InvalidArgumentException("Invoice yang sudah dibayar sebagian (PARTIAL) atau lunas (PAID) tidak dapat dibatalkan.");
                }

                $invoice->status = InvoiceStatus::CANCELLED;
                $invoice->cancelled_at = now();
                $invoice->cancel_reason = $reason;
                $invoice->save();

                activity()
                    ->performedOn($invoice)
                    ->event('cancelled')
                    ->withProperties(['reason' => $reason])
                    ->log('Invoice dibatalkan: ' . $reason);

                $cancelledInvoices[] = $invoice;
            }

            return $cancelledInvoices;
        });
    }
}
