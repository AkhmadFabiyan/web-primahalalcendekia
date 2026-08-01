<?php

namespace App\Modules\Projects\Services;

use App\Modules\Projects\Models\Project;
use App\Modules\Payments\Enums\InvoiceType;
use App\Modules\Payments\Enums\InvoiceStatus;
use App\Modules\Payments\Enums\PaymentStatus;
use App\Modules\Payments\Enums\InvoiceAudience;
use Brick\Math\BigDecimal;

class ProjectFinancialSummaryService
{
    /**
     * Calculate financial summary for a project.
     *
     * @param Project $project
     * @return array
     */
    public static function calculate(Project $project): array
    {
        $clientTotalContract = BigDecimal::of((string) ($project->client_nominal ?? 0));
        $partnerTotalContract = BigDecimal::of((string) ($project->partner_nominal ?? 0));

        $clientTotalInvoiced = BigDecimal::of('0');
        $partnerTotalInvoiced = BigDecimal::of('0');
        $clientTotalPaid = BigDecimal::of('0');
        $partnerTotalPaid = BigDecimal::of('0');

        $activeInvoices = $project->invoices()
            ->where('status', '!=', InvoiceStatus::CANCELLED)
            ->where('invoice_type', '!=', InvoiceType::GOVERNMENT) // Tidak dihitung komersial
            ->with(['payments' => function ($query) {
                $query->where('status', PaymentStatus::VERIFIED);
            }])
            ->get();

        foreach ($activeInvoices as $invoice) {
            $invoiceSubtotal = BigDecimal::of((string) $invoice->subtotal);

            // Hitung total paid untuk invoice ini (hanya yang VERIFIED)
            $invoicePaid = BigDecimal::of('0');
            foreach ($invoice->payments as $payment) {
                $invoicePaid = $invoicePaid->plus(BigDecimal::of((string) $payment->amount));
            }

            if ($invoice->audience === InvoiceAudience::CLIENT) {
                $clientTotalInvoiced = $clientTotalInvoiced->plus($invoiceSubtotal);
                $clientTotalPaid = $clientTotalPaid->plus($invoicePaid);
            } else if ($invoice->audience === InvoiceAudience::PARTNER) {
                $partnerTotalInvoiced = $partnerTotalInvoiced->plus($invoiceSubtotal);
                $partnerTotalPaid = $partnerTotalPaid->plus($invoicePaid);
            }
        }

        $clientRemainingUninvoiced = $clientTotalContract->minus($clientTotalInvoiced);
        $partnerRemainingUninvoiced = $partnerTotalContract->minus($partnerTotalInvoiced);

        // Mencegah nilai negatif jika over-invoiced (seharusnya tidak terjadi, namun untuk keamanan)
        if ($clientRemainingUninvoiced->isNegative()) {
            $clientRemainingUninvoiced = BigDecimal::of('0');
        }
        if ($partnerRemainingUninvoiced->isNegative()) {
            $partnerRemainingUninvoiced = BigDecimal::of('0');
        }

        $clientRemainingUnpaid = $clientTotalInvoiced->minus($clientTotalPaid);
        $partnerRemainingUnpaid = $partnerTotalInvoiced->minus($partnerTotalPaid);

        if ($clientRemainingUnpaid->isNegative()) {
            $clientRemainingUnpaid = BigDecimal::of('0');
        }
        if ($partnerRemainingUnpaid->isNegative()) {
            $partnerRemainingUnpaid = BigDecimal::of('0');
        }

        // Tentukan apakah seluruh komersial sudah ditagih dan dibayar lunas
        $isFullyInvoiced = $clientRemainingUninvoiced->isZero() && $partnerRemainingUninvoiced->isZero();
        $isFullyPaid = $isFullyInvoiced && $clientRemainingUnpaid->isZero() && $partnerRemainingUnpaid->isZero();

        return [
            'client_total_contract' => (string) $clientTotalContract,
            'partner_total_contract' => (string) $partnerTotalContract,
            'client_total_invoiced' => (string) $clientTotalInvoiced,
            'partner_total_invoiced' => (string) $partnerTotalInvoiced,
            'client_remaining_uninvoiced' => (string) $clientRemainingUninvoiced,
            'partner_remaining_uninvoiced' => (string) $partnerRemainingUninvoiced,
            'client_total_paid' => (string) $clientTotalPaid,
            'partner_total_paid' => (string) $partnerTotalPaid,
            'client_remaining_unpaid' => (string) $clientRemainingUnpaid,
            'partner_remaining_unpaid' => (string) $partnerRemainingUnpaid,
            'is_fully_invoiced' => $isFullyInvoiced,
            'is_fully_paid' => $isFullyPaid,
        ];
    }
}
