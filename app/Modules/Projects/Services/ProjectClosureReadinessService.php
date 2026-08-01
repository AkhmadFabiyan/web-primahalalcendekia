<?php

namespace App\Modules\Projects\Services;

use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Enums\ProjectStatus;
use App\Modules\Payments\Enums\InvoiceStatus;
use App\Modules\Payments\Enums\PaymentStatus;
use App\Modules\Workflows\Enums\TaskStatus;

class ProjectClosureReadinessService
{
    /**
     * Mengevaluasi 12 kriteria checklist penyelesaian
     *
     * @param Project $project
     * @return array<string, bool> Array checklist
     */
    public static function evaluate(Project $project): array
    {
        $project->loadMissing(['invoices.payments', 'paymentSchedules', 'tasks', 'client']);
        $financialSummary = ProjectFinancialSummaryService::calculate($project);

        // 1. Sertifikat telah diterbitkan
        $certIssued = \App\Modules\Projects\Models\Certificate::where('project_id', $project->id)->exists();

        // 2. Invoice Negara telah dibayar
        $govInvoice = $project->invoices->where('invoice_type', \App\Modules\Payments\Enums\InvoiceType::GOVERNMENT)->first();
        $govInvoicePaid = $govInvoice ? $govInvoice->status === InvoiceStatus::PAID : true; // Jika tidak ada, diabaikan/true

        // 3. Tidak ada Invoice aktif berstatus DRAFT
        $noDraftInvoice = $project->invoices->where('status', InvoiceStatus::DRAFT)->isEmpty();

        // 4. Tidak ada Invoice aktif berstatus PUBLISHED
        $noPublishedInvoice = $project->invoices->where('status', InvoiceStatus::PUBLISHED)->isEmpty();

        // 5. Tidak ada Invoice aktif berstatus PARTIAL
        $noPartialInvoice = $project->invoices->where('status', InvoiceStatus::PARTIAL)->isEmpty();

        // 6. Tidak ada Payment berstatus PENDING
        $noPendingPayment = true;
        foreach ($project->invoices as $invoice) {
            if ($invoice->payments->where('status', PaymentStatus::PENDING)->isNotEmpty()) {
                $noPendingPayment = false;
                break;
            }
        }

        // 7. Seluruh jadwal Termin telah ditagihkan
        $allTerminInvoiced = $project->paymentSchedules->where('status', 'PENDING')->isEmpty();

        // 8. Sisa belum ditagihkan bernilai 0
        // (client_remaining_uninvoiced + partner_remaining_uninvoiced == 0)
        $noUninvoiced = bccomp((string)$financialSummary['client_remaining_uninvoiced'], '0', 2) <= 0
            && bccomp((string)$financialSummary['partner_remaining_uninvoiced'], '0', 2) <= 0;

        // 9. Sisa belum dibayar bernilai 0
        $noUnpaid = bccomp((string)$financialSummary['client_remaining_unpaid'], '0', 2) <= 0
            && bccomp((string)$financialSummary['partner_remaining_unpaid'], '0', 2) <= 0;

        // 10. Seluruh kewajiban CLIENT telah lunas
        $clientPaid = bccomp((string)$financialSummary['client_remaining_unpaid'], '0', 2) <= 0;

        // 11. Seluruh kewajiban PARTNER telah lunas, jika tipe Mitra
        // Jika tidak tipe mitra, otomatis true
        $partnerPaid = true;
        if ($project->client && $project->client->client_type === \App\Modules\Clients\Enums\ClientType::PARTNER) {
            $partnerPaid = bccomp((string)$financialSummary['partner_remaining_unpaid'], '0', 2) <= 0;
        }

        // 12. Tidak ada Task wajib yang masih terbuka
        $openTasks = [TaskStatus::TODO, TaskStatus::IN_PROGRESS, TaskStatus::WAITING_REVIEW, TaskStatus::REVISION];
        $noOpenTasks = $project->tasks->whereIn('status', $openTasks)->isEmpty();

        return [
            'certificate_issued' => $certIssued,
            'government_invoice_paid' => $govInvoicePaid,
            'no_draft_invoice' => $noDraftInvoice,
            'no_published_invoice' => $noPublishedInvoice,
            'no_partial_invoice' => $noPartialInvoice,
            'no_pending_payment' => $noPendingPayment,
            'all_termin_invoiced' => $allTerminInvoiced,
            'no_remaining_uninvoiced' => $noUninvoiced,
            'no_remaining_unpaid' => $noUnpaid,
            'client_obligations_paid' => $clientPaid,
            'partner_obligations_paid' => $partnerPaid,
            'no_open_tasks' => $noOpenTasks,
        ];
    }

    /**
     * Cek apakah seluruh checklist bernilai true
     * 
     * @param array $checklist
     * @return bool
     */
    public static function isReady(array $checklist): bool
    {
        return !in_array(false, $checklist, true);
    }
}
