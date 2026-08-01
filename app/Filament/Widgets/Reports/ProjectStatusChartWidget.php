<?php

namespace App\Filament\Widgets\Reports;

use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use App\Modules\Reports\Services\ManagementReportService;
use App\Modules\Reports\DataTransferObjects\ManagementReportFilterData;
use Illuminate\Support\Facades\DB;

class ProjectStatusChartWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Distribusi Status Project';
    protected static ?int $sort = 3;

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && $user->can('report.view');
    }

    protected function getData(): array
    {
        $filterData = ManagementReportFilterData::fromArray($this->filters);
        $service = new ManagementReportService($filterData);
        
        $query = clone $service->getReportQuery();
        
        // This is safe since we clone it
        $statuses = $query->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $labels = [];
        $data = [];
        foreach (\App\Modules\Projects\Enums\ProjectStatus::cases() as $case) {
            $labels[] = str_replace('_', ' ', $case->value);
            $data[] = $statuses[$case->value] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Project',
                    'data' => $data,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
