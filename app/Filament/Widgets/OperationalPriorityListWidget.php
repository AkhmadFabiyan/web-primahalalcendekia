<?php

namespace App\Filament\Widgets;

use App\Modules\Dashboards\Services\OperationalDashboardService;
use App\Modules\Dashboards\DataTransferObjects\OperationalDashboardFilterData;
use Filament\Widgets\Widget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Carbon\Carbon;

class OperationalPriorityListWidget extends Widget
{
    use InteractsWithPageFilters;

    protected string $view = 'filament.widgets.operational-priority-list-widget';
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && $user->isInternalStaff() && !$user->isSuperAdmin();
    }

    public function getPriorityData()
    {
        $filterData = OperationalDashboardFilterData::fromArray($this->filters);
        $service = new OperationalDashboardService($filterData);
        return $service->getPriorityList();
    }
}
