<?php

namespace App\Filament\Widgets\Reports;

use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use App\Modules\Reports\Services\ManagementReportService;
use App\Modules\Reports\DataTransferObjects\ManagementReportFilterData;

class LeadConversionChartWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Lead Conversion (Cohort)';
    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && $user->can('report.view');
    }

    protected function getData(): array
    {
        $filterData = ManagementReportFilterData::fromArray($this->filters);
        $service = new ManagementReportService($filterData);
        $kpis = $service->getKpis();
        
        return [
            'datasets' => [
                [
                    'label' => 'Total',
                    'data' => [$kpis['total_lead'], $kpis['lead_deal']],
                    'backgroundColor' => ['#6366f1', '#22c55e'],
                ],
            ],
            'labels' => ['Total Lead Cohort', 'Lead Deal'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
