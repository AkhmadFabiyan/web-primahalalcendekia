<?php

namespace App\Modules\Payments\Services;

use App\Models\User;
use App\Modules\Projects\Models\Project;
use App\Modules\Payments\Models\Invoice;
use App\Modules\Payments\Enums\InvoiceType;
use App\Modules\Payments\Enums\InvoiceAudience;
use App\Modules\Payments\Enums\InvoiceStatus;
use App\Modules\Projects\Services\ProjectFinancialSummaryService;
use App\Modules\Clients\Enums\ClientType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;
use Brick\Math\BigDecimal;

class TerminService
{
    public function __construct(
        private ProjectFinancialSummaryService $summaryService,
        private InvoiceNumberService $numberService
    ) {}

    public function issueNextTermin(Project $project, string $issuedAt, string $dueDate, ?string $notes, User $actor): array
    {
        return DB::transaction(function () use ($project, $issuedAt, $dueDate, $notes, $actor) {
            $project = Project::with(['client.partner'])->where('id', $project->id)->lockForUpdate()->first();
            
            // Dapatkan jadwal berikutnya
            $schedule = $project->paymentSchedules()
                ->where('status', 'PENDING')
                ->where('invoice_type', InvoiceType::INSTALLMENT->value)
                ->orderBy('sequence', 'asc')
                ->first();

            if (!$schedule) {
                throw new Exception("Tidak ada jadwal Termin yang pending.");
            }

            // Validasi sisa tagihan untuk menghindari over-invoicing
            $summary = ProjectFinancialSummaryService::calculate($project);
            $clientRemaining = BigDecimal::of($summary['client_remaining_uninvoiced']);
            $partnerRemaining = BigDecimal::of($summary['partner_remaining_uninvoiced']);

            $clientAmount = BigDecimal::of((string) $schedule->client_amount);
            $partnerAmount = BigDecimal::of((string) ($schedule->partner_amount ?? 0));

            if ($clientRemaining->isLessThan($clientAmount)) {
                throw new Exception("Nominal Termin Client melewati sisa tagihan.");
            }

            if ($project->client->type === ClientType::PARTNER->value && $partnerRemaining->isLessThan($partnerAmount)) {
                throw new Exception("Nominal Termin Partner melewati sisa tagihan.");
            }

            $billingGroupId = Str::uuid()->toString();
            $invoices = [];
            
            $clientSequence = \App\Modules\Payments\Models\Invoice::where('project_id', $project->id)
                ->where('audience', InvoiceAudience::CLIENT->value)
                ->count() + 1;
            $clientInvoice = new Invoice([
                'project_id' => $project->id,
                'invoice_type' => InvoiceType::INSTALLMENT->value,
                'billing_group_id' => $billingGroupId,
                'audience' => InvoiceAudience::CLIENT->value,
                'partner_id' => null,
                'billing_snapshot' => [
                    'client_id' => $project->client->business_id,
                    'company_name' => $project->client->company_name,
                    'address' => $project->client->address,
                    'city' => $project->client->city,
                    'province' => $project->client->province,
                    'pic_name' => $project->client->pic_name,
                    'pic_phone' => $project->client->pic_phone,
                    'pic_email' => $project->client->pic_email,
                    'audience' => InvoiceAudience::CLIENT->value,
                    'project_service_type' => $project->service_type,
                    'billing_target_name' => $project->client->company_name
                ],
                'sequence' => $clientSequence,
                'subtotal' => (string) $clientAmount,
                'discount_total' => 0,
                'status' => InvoiceStatus::PUBLISHED->value,
                'issued_at' => $issuedAt,
                'due_date' => $dueDate,
                'published_at' => now(),
            ]);
            $clientInvoice->invoice_number = $this->numberService->generate($clientInvoice);
            $clientInvoice->save();
            $invoices[] = $clientInvoice;

            if ($project->client->client_type === ClientType::PARTNER) {
                $partnerSequence = \App\Modules\Payments\Models\Invoice::where('project_id', $project->id)
                    ->where('audience', InvoiceAudience::PARTNER->value)
                    ->count() + 1;
                $partnerInvoice = new Invoice([
                    'project_id' => $project->id,
                    'invoice_type' => InvoiceType::INSTALLMENT->value,
                    'billing_group_id' => $billingGroupId,
                    'audience' => InvoiceAudience::PARTNER->value,
                    'partner_id' => $project->client->partner_id,
                    'billing_snapshot' => [
                        'client_id' => $project->client->business_id,
                        'company_name' => $project->client->company_name,
                        'address' => $project->client->address,
                        'city' => $project->client->city,
                        'province' => $project->client->province,
                        'pic_name' => $project->client->pic_name,
                        'pic_phone' => $project->client->pic_phone,
                        'pic_email' => $project->client->pic_email,
                        'audience' => InvoiceAudience::PARTNER->value,
                        'project_service_type' => $project->service_type,
                        'billing_target_name' => clone $project->client->partner ? $project->client->partner->name : $project->client->company_name
                    ],
                    'sequence' => $partnerSequence,
                    'subtotal' => (string) $partnerAmount,
                    'discount_total' => 0,
                    'status' => InvoiceStatus::PUBLISHED->value,
                    'issued_at' => $issuedAt,
                    'due_date' => $dueDate,
                    'published_at' => now(),
                ]);
                $partnerInvoice->invoice_number = $this->numberService->generate($partnerInvoice);
                $partnerInvoice->save();
                $invoices[] = $partnerInvoice;
            }

            $schedule->update(['status' => 'INVOICED']);

            activity()
                ->performedOn($project)
                ->causedBy($actor)
                ->event('invoice_published')
                ->log("Invoice Termin " . $schedule->sequence . " diterbitkan.");

            return $invoices;
        });
    }

    public function issueSettlement(Project $project, string $issuedAt, string $dueDate, ?string $notes, User $actor): array
    {
        return DB::transaction(function () use ($project, $issuedAt, $dueDate, $notes, $actor) {
            $project = Project::with(['client.partner'])->where('id', $project->id)->lockForUpdate()->first();
            
            // Validasi apakah ada jadwal termin yang belum terbit
            $pendingTermin = $project->paymentSchedules()
                ->where('status', 'PENDING')
                ->where('invoice_type', InvoiceType::INSTALLMENT->value)
                ->exists();

            if ($pendingTermin) {
                throw new Exception("Seluruh jadwal Termin sebelumnya harus diterbitkan terlebih dahulu.");
            }

            // Validasi apakah sudah ada Settlement aktif
            $hasSettlement = $project->invoices()
                ->where('invoice_type', InvoiceType::SETTLEMENT->value)
                ->where('status', '!=', InvoiceStatus::CANCELLED->value)
                ->exists();
                
            if ($hasSettlement) {
                throw new Exception("Invoice Pelunasan aktif sudah ada.");
            }

            // Validasi apakah ada Invoice aktif yang masih DRAFT, PUBLISHED, atau PARTIAL
            $hasUnpaidInvoices = $project->invoices()
                ->whereIn('status', [InvoiceStatus::DRAFT->value, InvoiceStatus::PUBLISHED->value, InvoiceStatus::PARTIAL->value])
                ->where('invoice_type', '!=', InvoiceType::GOVERNMENT->value) // Kita bahas pelunasan komersial
                ->exists();
                
            if ($hasUnpaidInvoices) {
                throw new Exception("Terdapat Invoice yang belum lunas. Selesaikan terlebih dahulu sebelum Pelunasan.");
            }

            // Hitung sisa belum tertagih
            $summary = ProjectFinancialSummaryService::calculate($project);
            $clientRemaining = BigDecimal::of($summary['client_remaining_uninvoiced']);
            $partnerRemaining = BigDecimal::of($summary['partner_remaining_uninvoiced']);

            if ($clientRemaining->isZero() && $partnerRemaining->isZero()) {
                throw new Exception("Seluruh tagihan kontrak telah diterbitkan (sisa Rp 0). Tidak perlu Invoice Pelunasan.");
            }

            $billingGroupId = Str::uuid()->toString();
            $invoices = [];
            
            if ($clientRemaining->isGreaterThan(0)) {
                $clientSequence = \App\Modules\Payments\Models\Invoice::where('project_id', $project->id)
                    ->where('audience', InvoiceAudience::CLIENT->value)
                    ->count() + 1;
                $clientInvoice = new Invoice([
                    'project_id' => $project->id,
                    'invoice_type' => InvoiceType::SETTLEMENT->value,
                    'billing_group_id' => $billingGroupId,
                    'audience' => InvoiceAudience::CLIENT->value,
                    'partner_id' => null,
                    'billing_snapshot' => [
                        'client_id' => $project->client->business_id,
                        'company_name' => $project->client->company_name,
                        'address' => $project->client->address,
                        'city' => $project->client->city,
                        'province' => $project->client->province,
                        'pic_name' => $project->client->pic_name,
                        'pic_phone' => $project->client->pic_phone,
                        'pic_email' => $project->client->pic_email,
                        'audience' => InvoiceAudience::CLIENT->value,
                        'project_service_type' => $project->service_type,
                        'billing_target_name' => $project->client->company_name
                    ],
                    'sequence' => $clientSequence,
                    'subtotal' => (string) $clientRemaining,
                    'discount_total' => 0,
                    'status' => InvoiceStatus::PUBLISHED->value,
                    'issued_at' => $issuedAt,
                    'due_date' => $dueDate,
                    'published_at' => now(),
                ]);
                $clientInvoice->invoice_number = $this->numberService->generate($clientInvoice);
                $clientInvoice->save();
                $invoices[] = $clientInvoice;
            }

            if ($project->client->client_type === ClientType::PARTNER && $partnerRemaining->isGreaterThan(0)) {
                $partnerSequence = \App\Modules\Payments\Models\Invoice::where('project_id', $project->id)
                    ->where('audience', InvoiceAudience::PARTNER->value)
                    ->count() + 1;
                $partnerInvoice = new Invoice([
                    'project_id' => $project->id,
                    'invoice_type' => InvoiceType::SETTLEMENT->value,
                    'billing_group_id' => $billingGroupId,
                    'audience' => InvoiceAudience::PARTNER->value,
                    'partner_id' => $project->client->partner_id,
                    'billing_snapshot' => [
                        'client_id' => $project->client->business_id,
                        'company_name' => $project->client->company_name,
                        'address' => $project->client->address,
                        'city' => $project->client->city,
                        'province' => $project->client->province,
                        'pic_name' => $project->client->pic_name,
                        'pic_phone' => $project->client->pic_phone,
                        'pic_email' => $project->client->pic_email,
                        'audience' => InvoiceAudience::PARTNER->value,
                        'project_service_type' => $project->service_type,
                        'billing_target_name' => $project->client->partner ? clone $project->client->partner->name : clone $project->client->company_name
                    ],
                    'sequence' => $partnerSequence,
                    'subtotal' => (string) $partnerRemaining,
                    'discount_total' => 0,
                    'status' => InvoiceStatus::PUBLISHED->value,
                    'issued_at' => $issuedAt,
                    'due_date' => $dueDate,
                    'published_at' => now(),
                ]);
                $partnerInvoice->invoice_number = $this->numberService->generate($partnerInvoice);
                $partnerInvoice->save();
                $invoices[] = $partnerInvoice;
            }

            activity()
                ->performedOn($project)
                ->causedBy($actor)
                ->event('invoice_published')
                ->log("Invoice Pelunasan diterbitkan.");

            return $invoices;
        });
    }
}
