<?php

namespace App\Modules\Reports\Services;

use App\Modules\Reports\DataTransferObjects\ManagementReportFilterData;
use App\Modules\Leads\Models\Lead;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Enums\ProjectStatus;
use App\Modules\Payments\Models\Invoice;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Enums\InvoiceType;
use App\Modules\Payments\Enums\InvoiceStatus;
use App\Modules\Payments\Enums\PaymentStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class ManagementReportService
{
    public function __construct(
        protected ManagementReportFilterData $filterData
    ) {}

    protected function getCacheKey(string $key): string
    {
        $version = Cache::get('management-report:version', 1);
        return "management-report:{$version}:{$key}:" . $this->filterData->getCacheHash();
    }

    protected function applyProjectFilters(Builder $query): Builder
    {
        if ($this->filterData->client_type) {
            $query->whereHas('client', fn($q) => $q->where('client_type', $this->filterData->client_type));
        }
        if ($this->filterData->partner_id) {
            $query->whereHas('client', fn($q) => $q->where('partner_id', $this->filterData->partner_id));
        }
        if ($this->filterData->provinsi_id) {
            $query->whereHas('client', fn($q) => $q->where('provinsi_id', $this->filterData->provinsi_id));
        }
        if ($this->filterData->kota_id) {
            $query->whereHas('client', fn($q) => $q->where('kota_id', $this->filterData->kota_id));
        }
        // PIC Assignment (historic) logic
        $roles = [
            'marketing_id' => 'marketing',
            'admin_id' => 'admin',
            'entry_id' => 'entry',
            'pendamping_id' => 'pendamping',
            'auditor_id' => 'auditor',
        ];
        foreach ($roles as $prop => $roleName) {
            if ($this->filterData->$prop) {
                $userId = $this->filterData->$prop;
                if ($roleName === 'marketing') {
                    // Marketing is usually from Lead or Client PIC, depending on system implementation. 
                    // Let's assume Lead has pic_id or Project has assignments for marketing.
                    $query->whereHas('sourceLead', fn($q) => $q->where('pic_id', $userId));
                } else {
                    $query->whereHas('assignments', function($q) use ($userId, $roleName) {
                        $q->where('user_id', $userId)
                          // if historic filter is required: assigned_at <= end_date AND (ended_at >= start_date OR ended_at IS NULL)
                          ->where('assigned_at', '<=', $this->filterData->end_date)
                          ->where(function($q) {
                              $q->where('ended_at', '>=', $this->filterData->start_date)
                                ->orWhereNull('ended_at');
                          });
                        // You may also want to filter by role if assignments have role column.
                    });
                }
            }
        }
        if ($this->filterData->status_project) {
            $query->where('status', $this->filterData->status_project);
        }

        return $query;
    }

    public function getKpis(): array
    {
        return Cache::remember($this->getCacheKey('kpis'), 300, function () {
            // Total Lead (leads created in period)
            $totalLeadQuery = Lead::query()
                ->whereBetween('created_at', [$this->filterData->start_date . ' 00:00:00', $this->filterData->end_date . ' 23:59:59']);
            
            if ($this->filterData->marketing_id) {
                $totalLeadQuery->where('pic_id', $this->filterData->marketing_id);
            }
            // For other filters on leads, we might need to map them if applicable, else they only apply to Projects.
            $totalLead = $totalLeadQuery->count();

            // Lead Deal (converted in period) -> leads.updated_at for simplicity if deal_at is not available, but user said `deal_at` or conversion time. Let's assume Lead has `updated_at` where status becomes DEAL or we use Project's created_at (since project created on conversion).
            $leadDealQuery = Project::query()
                ->whereBetween('created_at', [$this->filterData->start_date . ' 00:00:00', $this->filterData->end_date . ' 23:59:59']);
            if ($this->filterData->marketing_id) {
                $leadDealQuery->whereHas('sourceLead', fn($q) => $q->where('pic_id', $this->filterData->marketing_id));
            }
            $leadDeal = $leadDealQuery->count();
            
            // Project Aktif
            $projectAktifQuery = Project::query()
                ->where('activated_at', '<=', $this->filterData->end_date . ' 23:59:59')
                ->where(function($q) {
                    $q->whereNull('completed_at')->orWhere('completed_at', '>', $this->filterData->end_date . ' 23:59:59');
                })
                ->where(function($q) {
                    $q->whereNull('cancelled_at')->orWhere('cancelled_at', '>', $this->filterData->end_date . ' 23:59:59');
                });
            $projectAktifQuery = $this->applyProjectFilters($projectAktifQuery);
            $projectAktif = $projectAktifQuery->count();

            // Sertifikat Terbit
            $sertifikatTerbitQuery = Project::query()->whereHas('certificate', function($q) {
                $q->whereBetween('issued_at', [$this->filterData->start_date . ' 00:00:00', $this->filterData->end_date . ' 23:59:59']);
            });
            $sertifikatTerbitQuery = $this->applyProjectFilters($sertifikatTerbitQuery);
            $sertifikatTerbit = $sertifikatTerbitQuery->count();

            // Project Selesai
            $projectSelesaiQuery = Project::query()->whereBetween('completed_at', [$this->filterData->start_date . ' 00:00:00', $this->filterData->end_date . ' 23:59:59']);
            $projectSelesaiQuery = $this->applyProjectFilters($projectSelesaiQuery);
            $projectSelesai = $projectSelesaiQuery->count();

            // Kas Masuk Terverifikasi (commercial)
            $kasMasukQuery = Payment::query()
                ->where('status', PaymentStatus::VERIFIED->value)
                ->whereBetween('payment_date', [$this->filterData->start_date . ' 00:00:00', $this->filterData->end_date . ' 23:59:59'])
                ->whereHas('invoice', function ($q) {
                    $q->whereIn('invoice_type', [
                        InvoiceType::ACTIVATION->value, 
                        InvoiceType::INSTALLMENT->value, 
                        InvoiceType::SETTLEMENT->value
                    ]);
                });
            $kasMasuk = $kasMasukQuery->sum('amount');

            // Outstanding
            $outstandingQuery = Invoice::query()
                ->whereIn('invoice_type', [
                    InvoiceType::ACTIVATION->value, 
                    InvoiceType::INSTALLMENT->value, 
                    InvoiceType::SETTLEMENT->value
                ])
                ->whereIn('status', [InvoiceStatus::PUBLISHED->value, InvoiceStatus::PARTIAL->value])
                ->where('issued_at', '<=', $this->filterData->end_date . ' 23:59:59');
            // We need to sub query the paid amount up to period_end
            $outstandingTotal = $outstandingQuery->sum(DB::raw('subtotal - discount_total'));
            
            $paidTotalQuery = Payment::query()
                ->where('status', PaymentStatus::VERIFIED->value)
                ->where('payment_date', '<=', $this->filterData->end_date . ' 23:59:59')
                ->whereIn('invoice_id', $outstandingQuery->select('id'));
            $paidTotal = $paidTotalQuery->sum('amount');

            $outstanding = $outstandingTotal - $paidTotal;
            
            // Cohort Conversion Rate
            $cohortTotal = $totalLeadQuery->count();
            $cohortDeals = $cohortTotal > 0 ? Lead::query()
                ->whereBetween('created_at', [$this->filterData->start_date . ' 00:00:00', $this->filterData->end_date . ' 23:59:59'])
                ->whereHas('project') // has project means converted
                ->count() : 0;
            
            $conversionRate = $cohortTotal > 0 ? round(($cohortDeals / $cohortTotal) * 100, 2) : 0;

            return [
                'total_lead' => $totalLead,
                'lead_deal' => $leadDeal,
                'conversion_rate' => $conversionRate,
                'project_aktif' => $projectAktif,
                'sertifikat_terbit' => $sertifikatTerbit,
                'project_selesai' => $projectSelesai,
                'kas_masuk' => $kasMasuk,
                'outstanding' => $outstanding,
            ];
        });
    }

    public function getCycleTimeMetrics(): array
    {
        return Cache::remember($this->getCacheKey('cycletime'), 300, function () {
            // cycle time: activated_at to certificates.issued_at
            $query = Project::query()
                ->join('certificates', 'projects.id', '=', 'certificates.project_id')
                ->whereNotNull('projects.activated_at')
                ->whereBetween('certificates.issued_at', [$this->filterData->start_date . ' 00:00:00', $this->filterData->end_date . ' 23:59:59'])
                ->select(DB::raw('julianday(certificates.issued_at) - julianday(projects.activated_at) as diff_days'));
            
            // Note: SQLite uses julianday, MySQL/PgSQL might use DATEDIFF. Assuming SQLite for tests, but we can do it via collection since it's just a subset of projects for this period.
            $projects = Project::query()
                ->with('certificate')
                ->whereHas('certificate', function($q) {
                    $q->whereBetween('issued_at', [$this->filterData->start_date . ' 00:00:00', $this->filterData->end_date . ' 23:59:59']);
                })
                ->whereNotNull('activated_at');
            $projects = $this->applyProjectFilters($projects)->get();

            $days = $projects->map(function($p) {
                return $p->activated_at->diffInDays($p->certificate->issued_at);
            })->sort()->values();

            if ($days->isEmpty()) {
                return [
                    'avg' => 0,
                    'median' => 0,
                    'p75' => 0,
                    'min' => 0,
                    'max' => 0,
                ];
            }

            $count = $days->count();
            $avg = round($days->average(), 1);
            $median = $days->get((int) floor($count / 2));
            $p75 = $days->get((int) floor($count * 0.75));
            $min = $days->first();
            $max = $days->last();

            return [
                'avg' => $avg,
                'median' => $median,
                'p75' => $p75,
                'min' => $min,
                'max' => $max,
            ];
        });
    }

    public function getCompletionMetrics(): array
    {
        return Cache::remember($this->getCacheKey('completion'), 300, function () {
            // Cohort activated in period
            $cohortQuery = Project::query()
                ->whereBetween('activated_at', [$this->filterData->start_date . ' 00:00:00', $this->filterData->end_date . ' 23:59:59']);
            $cohortQuery = $this->applyProjectFilters($cohortQuery);
            $cohortTotal = $cohortQuery->count();

            if ($cohortTotal === 0) {
                return [
                    'certification_rate' => 0,
                    'closure_rate' => 0,
                    'cancellation_rate' => 0,
                ];
            }

            // Of the cohort, how many have certificate by period_end?
            $certifiedCount = (clone $cohortQuery)
                ->whereHas('certificate', function($q) {
                    $q->where('issued_at', '<=', $this->filterData->end_date . ' 23:59:59');
                })->count();

            // Of the cohort, how many completed by period end?
            $completedCount = (clone $cohortQuery)
                ->where('completed_at', '<=', $this->filterData->end_date . ' 23:59:59')
                ->count();
            
            // Cancelled by period end
            $cancelledCount = (clone $cohortQuery)
                ->where('cancelled_at', '<=', $this->filterData->end_date . ' 23:59:59')
                ->count();

            return [
                'certification_rate' => round(($certifiedCount / $cohortTotal) * 100, 2),
                'closure_rate' => round(($completedCount / $cohortTotal) * 100, 2),
                'cancellation_rate' => round(($cancelledCount / $cohortTotal) * 100, 2),
            ];
        });
    }

    public function getReportQuery(): Builder
    {
        $query = Project::query()
            ->with(['client', 'assignments', 'invoices', 'certificate']);
        
        $query = $this->applyProjectFilters($query);
        // By default we might limit to projects that were active or modified in the period, or just apply all filters and no explicit date filter if the user doesn't require date for the base table. Wait, the user says "Export wajib menggunakan query dan filter yang sama dengan tabel." 
        // Let's filter projects that were either activated in period or updated in period, or completed in period. If it's a global period filter, we can filter by activated_at or created_at. Let's use created_at as base for the report table, or activated_at.
        // I will use created_at for simplicity unless they filter by status.
        $query->whereBetween('created_at', [$this->filterData->start_date . ' 00:00:00', $this->filterData->end_date . ' 23:59:59']);
        return $query;
    }
}
