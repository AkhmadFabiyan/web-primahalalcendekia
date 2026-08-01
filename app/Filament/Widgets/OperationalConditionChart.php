<?php

namespace App\Filament\Widgets;

use App\Modules\Dashboards\Services\OperationalDashboardService;
use App\Modules\Dashboards\DataTransferObjects\OperationalDashboardFilterData;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class OperationalConditionChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Kondisi Pembaruan Data';
    protected static ?int $sort = 3;

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && $user->isInternalStaff() && !$user->isSuperAdmin();
    }

    protected function getData(): array
    {
        $filterData = OperationalDashboardFilterData::fromArray($this->filters);
        $service = new OperationalDashboardService($filterData);
        $conditions = $service->getUpdateConditions();

        return [
            'datasets' => [
                [
                    'label' => 'Project',
                    'data' => array_values($conditions),
                    'backgroundColor' => [
                        '#22C55E', // Selesai - Green
                        '#EF4444', // Kritis - Red
                        '#F59E0B', // Perlu Follow Up - Amber
                        '#3B82F6', // Terkini - Blue
                    ],
                ],
            ],
            'labels' => array_keys($conditions),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
