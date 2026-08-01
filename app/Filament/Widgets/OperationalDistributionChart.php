<?php

namespace App\Filament\Widgets;

use App\Modules\Dashboards\Services\OperationalDashboardService;
use App\Modules\Dashboards\DataTransferObjects\OperationalDashboardFilterData;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class OperationalDistributionChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 2;
    protected ?string $heading = 'Distribusi Tahap Sertifikasi';

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && $user->isInternalStaff() && !$user->isSuperAdmin();
    }

    protected function getData(): array
    {
        $filterData = OperationalDashboardFilterData::fromArray($this->filters);
        $service = new OperationalDashboardService($filterData);
        $distribution = $service->getStageDistribution();

        return [
            'datasets' => [
                [
                    'label' => 'Project',
                    'data' => array_values($distribution),
                    'backgroundColor' => '#3B82F6', // Tailwind blue-500
                ],
            ],
            'labels' => array_keys($distribution),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
