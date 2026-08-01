<?php

namespace App\Modules\Reports\Services;

use App\Modules\Leads\Models\Lead;
use App\Modules\Projects\Models\Project;
use App\Modules\Payments\Models\Invoice;
use App\Modules\Payments\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use App\Modules\Reports\DataTransferObjects\ManagementReportFilterData;

class ReportingExportService
{
    public function __construct(
        protected ManagementReportFilterData $filterData
    ) {}

    public function applyFiltersToLeadQuery(Builder $query): Builder
    {
        $query->whereBetween('created_at', [$this->filterData->start_date . ' 00:00:00', $this->filterData->end_date . ' 23:59:59']);

        if ($this->filterData->marketing_id) {
            $query->where('marketing_id', $this->filterData->marketing_id);
        }

        if ($this->filterData->client_type) {
            $query->where('type', $this->filterData->client_type);
        }
        
        return $query;
    }

    public function applyFiltersToProjectQuery(Builder $query): Builder
    {
        $service = new ManagementReportService($this->filterData);
        return $service->applyProjectFilters($query)
            ->whereBetween('projects.created_at', [$this->filterData->start_date . ' 00:00:00', $this->filterData->end_date . ' 23:59:59']);
    }

    public function applyFiltersToInvoiceQuery(Builder $query): Builder
    {
        // For InvoiceBillingGroupExporter, we filter out government invoices
        // and select 1 invoice per billing_group_id
        $query->where('is_government', false)
              ->whereBetween('issued_at', [$this->filterData->start_date . ' 00:00:00', $this->filterData->end_date . ' 23:59:59']);

        // Only group if it's the specific Invoice export
        $subQuery = Invoice::query()
            ->where('is_government', false)
            ->selectRaw('MIN(id)')
            ->groupBy('billing_group_id');
            
        $query->whereIn('id', $subQuery);

        return $query;
    }

    public function applyFiltersToPaymentQuery(Builder $query): Builder
    {
        return $query->whereBetween('payment_date', [$this->filterData->start_date . ' 00:00:00', $this->filterData->end_date . ' 23:59:59']);
    }
}
