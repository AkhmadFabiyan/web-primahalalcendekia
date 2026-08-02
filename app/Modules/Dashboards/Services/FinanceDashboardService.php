<?php

namespace App\Modules\Dashboards\Services;

use App\Modules\Dashboards\DataTransferObjects\FinanceDashboardFilterData;
use App\Modules\Payments\Enums\InvoiceAudience;
use App\Modules\Payments\Enums\InvoiceStatus;
use App\Modules\Payments\Enums\InvoiceType;
use App\Modules\Payments\Enums\PaymentStatus;
use App\Modules\Payments\Models\Invoice;
use App\Modules\Payments\Models\Payment;
use App\Modules\Projects\Models\Project;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class FinanceDashboardService
{
    private const CACHE_TTL = 60; // 60 seconds

    public function __construct(private readonly FinanceDashboardFilterData $filter) {}

    private function getCacheKey(string $type): string
    {
        $hash = md5(json_encode((array) $this->filter));
        $scope = auth()->id();

        return "finance-dashboard:{$type}:{$hash}:{$scope}";
    }

    /**
     * Helper to apply common project-level filters to invoice/payment queries
     */
    private function applyProjectFilters($query)
    {
        if ($this->filter->service) {
            $query->whereHas('project', function ($q) {
                $q->where('service_type', $this->filter->service);
            });
        }
        if ($this->filter->client_type) {
            $query->whereHas('project.client', function ($q) {
                $q->where('client_type', $this->filter->client_type);
            });
        }
        if ($this->filter->status) {
            $query->whereHas('project', function ($q) {
                $q->where('status', $this->filter->status);
            });
        }
        if ($this->filter->pic_id) {
            $query->whereHas('project.assignments', function ($q) {
                $q->where('user_id', $this->filter->pic_id)
                    ->whereNull('ended_at');
            });
        }

        return $query;
    }

    private function applyPaymentProjectFilters($query)
    {
        if ($this->filter->service) {
            $query->whereHas('invoice.project', fn ($q) => $q->where('service_type', $this->filter->service));
        }
        if ($this->filter->client_type) {
            $query->whereHas('invoice.project.client', fn ($q) => $q->where('client_type', $this->filter->client_type));
        }
        if ($this->filter->status) {
            $query->whereHas('invoice.project', fn ($q) => $q->where('status', $this->filter->status));
        }
        if ($this->filter->pic_id) {
            $query->whereHas('invoice.project.assignments', function ($q) {
                $q->where('user_id', $this->filter->pic_id)->whereNull('ended_at');
            });
        }

        return $query;
    }

    public function getKpis(): array
    {
        return Cache::remember($this->getCacheKey('kpis'), self::CACHE_TTL, function () {

            // 1. Kas Masuk (Verified Payments for Commercial Invoices)
            $paymentsBaseQuery = Payment::query()
                ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
                ->where('payments.status', PaymentStatus::VERIFIED->value)
                ->whereIn('invoices.invoice_type', [
                    InvoiceType::ACTIVATION->value,
                    InvoiceType::INSTALLMENT->value,
                    InvoiceType::SETTLEMENT->value,
                ]);

            if ($this->filter->period_start) {
                $paymentsBaseQuery->whereDate('payments.payment_date', '>=', $this->filter->period_start);
            }
            if ($this->filter->period_end) {
                $paymentsBaseQuery->whereDate('payments.payment_date', '<=', $this->filter->period_end);
            }
            if ($this->filter->audience) {
                $paymentsBaseQuery->where('invoices.audience', $this->filter->audience);
            }
            if ($this->filter->payment_method) {
                $paymentsBaseQuery->where('payments.payment_method', $this->filter->payment_method);
            }

            $paymentsQuery = $this->applyPaymentProjectFilters($paymentsBaseQuery);

            $kasMasukKlien = (clone $paymentsQuery)
                ->where('invoices.audience', InvoiceAudience::CLIENT->value)
                ->sum('payments.amount');

            $kasMasukMitra = (clone $paymentsQuery)
                ->where('invoices.audience', InvoiceAudience::PARTNER->value)
                ->sum('payments.amount');

            $totalKasMasuk = $kasMasukKlien + $kasMasukMitra;

            // 2. Project Bertagih & Billing Group
            $invoicesBaseQuery = Invoice::query()
                ->whereIn('invoice_type', [
                    InvoiceType::ACTIVATION->value,
                    InvoiceType::INSTALLMENT->value,
                    InvoiceType::SETTLEMENT->value,
                ])
                ->where('status', '!=', InvoiceStatus::CANCELLED->value)
                ->where('status', '!=', InvoiceStatus::DRAFT->value);

            if ($this->filter->period_start) {
                $invoicesBaseQuery->whereDate('issued_at', '>=', $this->filter->period_start);
            }
            if ($this->filter->period_end) {
                $invoicesBaseQuery->whereDate('issued_at', '<=', $this->filter->period_end);
            }
            if ($this->filter->audience) {
                $invoicesBaseQuery->where('audience', $this->filter->audience);
            }
            if ($this->filter->invoice_type) {
                $invoicesBaseQuery->where('invoice_type', $this->filter->invoice_type);
            }
            if ($this->filter->invoice_status) {
                $invoicesBaseQuery->where('status', $this->filter->invoice_status);
            }

            $this->applyProjectFilters($invoicesBaseQuery);

            $projectBertagih = (clone $invoicesBaseQuery)->distinct('project_id')->count('project_id');
            $billingGroupCount = (clone $invoicesBaseQuery)->distinct('billing_group_id')->count('billing_group_id');
            $jumlahInvoice = (clone $invoicesBaseQuery)->count();

            // 3. Outstanding Receivables (as of period_end)
            // Rumus: Total Invoice PUBLISHED/PARTIAL s.d as_of - Total Payment VERIFIED s.d as_of
            $asOfDate = $this->filter->period_end ?? Carbon::now();

            $outstandingQuery = Invoice::query()
                ->whereIn('invoice_type', [
                    InvoiceType::ACTIVATION->value,
                    InvoiceType::INSTALLMENT->value,
                    InvoiceType::SETTLEMENT->value,
                ])
                ->whereIn('status', [InvoiceStatus::PUBLISHED->value, InvoiceStatus::PARTIAL->value])
                ->whereDate('issued_at', '<=', $asOfDate);

            if ($this->filter->audience) {
                $outstandingQuery->where('audience', $this->filter->audience);
            }
            $this->applyProjectFilters($outstandingQuery);

            $outstandingKlien = 0;
            $outstandingMitra = 0;

            // Untuk performa lebih baik kita bisa fetch invoice dan total paid-nya,
            // atau menggunakan subquery
            $invoicesForOutstanding = $outstandingQuery->with(['payments' => function ($q) use ($asOfDate) {
                $q->where('status', PaymentStatus::VERIFIED->value)
                    ->whereDate('payment_date', '<=', $asOfDate);
            }])->get();

            foreach ($invoicesForOutstanding as $inv) {
                $paid = $inv->payments->sum('amount');
                $sisa = $inv->total - $paid;
                if ($sisa > 0) {
                    if ($inv->audience->value === InvoiceAudience::CLIENT->value) {
                        $outstandingKlien += $sisa;
                    } elseif ($inv->audience->value === InvoiceAudience::PARTNER->value) {
                        $outstandingMitra += $sisa;
                    }
                }
            }

            $totalOutstanding = $outstandingKlien + $outstandingMitra;

            // 4. Pending Payment
            // Snapshot pada saat Dashboard dibuka, tidak pakai rentang waktu,
            // tapi kita tetep apply filter project
            $pendingPaymentsQ = Payment::query()->where('status', PaymentStatus::PENDING->value);
            $this->applyProjectFilters($pendingPaymentsQ);

            $pendingPaymentCount = $pendingPaymentsQ->count();
            $pendingPaymentAmount = $pendingPaymentsQ->sum('amount');

            return compact(
                'kasMasukKlien', 'kasMasukMitra', 'totalKasMasuk',
                'projectBertagih', 'billingGroupCount', 'jumlahInvoice',
                'outstandingKlien', 'outstandingMitra', 'totalOutstanding',
                'pendingPaymentCount', 'pendingPaymentAmount'
            );
        });
    }

    public function getRevenueTrend(): array
    {
        return Cache::remember($this->getCacheKey('revenue_trend'), self::CACHE_TTL, function () {
            $start = $this->filter->period_start ?? Carbon::now()->startOfMonth();
            $end = $this->filter->period_end ?? Carbon::now()->endOfMonth();

            $daysDiff = $start->diffInDays($end);

            $groupBy = 'DATE(payments.payment_date)';
            if ($daysDiff > 180) {
                // Monthly
                $groupBy = "strftime('%Y-%m', payments.payment_date)";
                if (config('database.default') !== 'sqlite') {
                    $groupBy = "DATE_FORMAT(payments.payment_date, '%Y-%m')"; // Mysql
                }
            } elseif ($daysDiff > 31) {
                // Weekly
                $groupBy = "strftime('%Y-%W', payments.payment_date)";
                if (config('database.default') !== 'sqlite') {
                    $groupBy = 'YEARWEEK(payments.payment_date)'; // Mysql
                }
            }

            $paymentsQuery = Payment::query()
                ->select(DB::raw("{$groupBy} as date_group"), DB::raw('SUM(payments.amount) as total'))
                ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
                ->where('payments.status', PaymentStatus::VERIFIED->value)
                ->whereIn('invoices.invoice_type', [
                    InvoiceType::ACTIVATION->value,
                    InvoiceType::INSTALLMENT->value,
                    InvoiceType::SETTLEMENT->value,
                ])
                ->whereDate('payments.payment_date', '>=', $start)
                ->whereDate('payments.payment_date', '<=', $end)
                ->groupBy('date_group')
                ->orderBy('date_group');

            $this->applyProjectFilters($paymentsQuery);

            return $paymentsQuery->get()->pluck('total', 'date_group')->toArray();
        });
    }

    public function getAgingSummary(): array
    {
        return Cache::remember($this->getCacheKey('aging_summary'), self::CACHE_TTL, function () {
            $asOfDate = $this->filter->period_end ?? Carbon::now();

            $invoices = Invoice::query()
                ->whereIn('invoice_type', [
                    InvoiceType::ACTIVATION->value,
                    InvoiceType::INSTALLMENT->value,
                    InvoiceType::SETTLEMENT->value,
                ])
                ->whereIn('status', [InvoiceStatus::PUBLISHED->value, InvoiceStatus::PARTIAL->value])
                ->whereDate('issued_at', '<=', $asOfDate)
                ->with(['payments' => function ($q) use ($asOfDate) {
                    $q->where('status', PaymentStatus::VERIFIED->value)
                        ->whereDate('payment_date', '<=', $asOfDate);
                }])
                ->get();

            $aging = [
                'Belum Jatuh Tempo' => 0,
                '1-30 Hari' => 0,
                '31-60 Hari' => 0,
                '61-90 Hari' => 0,
                '> 90 Hari' => 0,
            ];

            foreach ($invoices as $inv) {
                $paid = $inv->payments->sum('amount');
                $sisa = $inv->total - $paid;

                if ($sisa > 0) {
                    $dueDate = Carbon::parse($inv->due_date);
                    if ($dueDate->greaterThanOrEqualTo($asOfDate)) {
                        $aging['Belum Jatuh Tempo'] += $sisa;
                    } else {
                        $daysOverdue = $dueDate->diffInDays($asOfDate);
                        if ($daysOverdue <= 30) {
                            $aging['1-30 Hari'] += $sisa;
                        } elseif ($daysOverdue <= 60) {
                            $aging['31-60 Hari'] += $sisa;
                        } elseif ($daysOverdue <= 90) {
                            $aging['61-90 Hari'] += $sisa;
                        } else {
                            $aging['> 90 Hari'] += $sisa;
                        }
                    }
                }
            }

            return $aging;
        });
    }

    public function getPendingPaymentsQuery()
    {
        $query = Payment::query()
            ->where('status', PaymentStatus::PENDING->value)
            ->with(['invoice.project.client']);

        return $this->applyProjectFilters($query);
    }

    public function getOverdueInvoicesQuery()
    {
        $asOfDate = $this->filter->period_end ?? Carbon::now();

        $query = Invoice::query()
            ->whereIn('invoice_type', [
                InvoiceType::ACTIVATION->value,
                InvoiceType::INSTALLMENT->value,
                InvoiceType::SETTLEMENT->value,
            ])
            ->whereIn('status', [InvoiceStatus::PUBLISHED->value, InvoiceStatus::PARTIAL->value])
            ->whereDate('due_date', '<', $asOfDate)
            ->with(['project.client', 'payments']);

        return $this->applyProjectFilters($query);
    }

    public function getInvoiceDrillDownQuery(string $kpiType)
    {
        $query = Invoice::query()
            ->whereIn('invoice_type', [
                InvoiceType::ACTIVATION->value,
                InvoiceType::INSTALLMENT->value,
                InvoiceType::SETTLEMENT->value,
            ]);

        if ($kpiType === 'outstandingKlien' || $kpiType === 'outstandingMitra') {
            $query->whereIn('status', [InvoiceStatus::PUBLISHED->value, InvoiceStatus::PARTIAL->value])
                ->whereDate('issued_at', '<=', $this->filter->period_end ?? Carbon::now());

            if ($kpiType === 'outstandingKlien') {
                $query->where('audience', InvoiceAudience::CLIENT->value);
            }
            if ($kpiType === 'outstandingMitra') {
                $query->where('audience', InvoiceAudience::PARTNER->value);
            }
        } elseif ($kpiType === 'projectBertagih' || $kpiType === 'jumlahInvoice') {
            $query->where('status', '!=', InvoiceStatus::CANCELLED->value)
                ->where('status', '!=', InvoiceStatus::DRAFT->value);
            if ($this->filter->period_start) {
                $query->whereDate('issued_at', '>=', $this->filter->period_start);
            }
            if ($this->filter->period_end) {
                $query->whereDate('issued_at', '<=', $this->filter->period_end);
            }
        }

        if ($this->filter->audience && ! in_array($kpiType, ['outstandingKlien', 'outstandingMitra'])) {
            $query->where('audience', $this->filter->audience);
        }

        $query->with(['project.client', 'payments']);

        return $this->applyProjectFilters($query);
    }

    public function getPaymentDrillDownQuery(string $kpiType)
    {
        $query = Payment::query()
            ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
            ->select('payments.*')
            ->whereIn('invoices.invoice_type', [
                InvoiceType::ACTIVATION->value,
                InvoiceType::INSTALLMENT->value,
                InvoiceType::SETTLEMENT->value,
            ]);

        if (in_array($kpiType, ['kasMasukKlien', 'kasMasukMitra', 'totalKasMasuk'])) {
            $query->where('payments.status', PaymentStatus::VERIFIED->value);
            if ($kpiType === 'kasMasukKlien') {
                $query->where('invoices.audience', InvoiceAudience::CLIENT->value);
            }
            if ($kpiType === 'kasMasukMitra') {
                $query->where('invoices.audience', InvoiceAudience::PARTNER->value);
            }

            if ($this->filter->period_start) {
                $query->whereDate('payments.payment_date', '>=', $this->filter->period_start);
            }
            if ($this->filter->period_end) {
                $query->whereDate('payments.payment_date', '<=', $this->filter->period_end);
            }
        } elseif ($kpiType === 'pendingPaymentCount') {
            $query->where('payments.status', PaymentStatus::PENDING->value);
        }

        if ($this->filter->audience && ! in_array($kpiType, ['kasMasukKlien', 'kasMasukMitra'])) {
            $query->where('invoices.audience', $this->filter->audience);
        }

        $query->with(['invoice.project.client']);

        return $this->applyProjectFilters($query);
    }
}
