<?php

namespace App\Filament\Widgets;

use App\Modules\Dashboards\Services\OperationalDashboardService;
use App\Modules\Dashboards\DataTransferObjects\OperationalDashboardFilterData;
use App\Modules\Dashboards\Services\OperationalStageResolver;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class OperationalStagesWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 5;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Ringkasan Tahap';

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && $user->isInternalStaff() && !$user->isSuperAdmin();
    }

    public function table(Table $table): Table
    {
        $filterData = OperationalDashboardFilterData::fromArray($this->filters);
        $service = new OperationalDashboardService($filterData);
        $distribution = $service->getStageDistribution();

        $data = collect([
            ['stage' => OperationalStageResolver::STAGE_ENTRY, 'count' => $distribution[OperationalStageResolver::STAGE_ENTRY] ?? 0],
            ['stage' => OperationalStageResolver::STAGE_PREP_AUDIT, 'count' => $distribution[OperationalStageResolver::STAGE_PREP_AUDIT] ?? 0],
            ['stage' => OperationalStageResolver::STAGE_AUDIT, 'count' => $distribution[OperationalStageResolver::STAGE_AUDIT] ?? 0],
            ['stage' => OperationalStageResolver::STAGE_FATWA, 'count' => $distribution[OperationalStageResolver::STAGE_FATWA] ?? 0],
            ['stage' => OperationalStageResolver::STAGE_CERT_ISSUED, 'count' => $distribution[OperationalStageResolver::STAGE_CERT_ISSUED] ?? 0],
        ]);

        return $table
            ->query(
                \App\Models\User::query()->where('id', '<', 0) // Dummy query to satisfy Filament table requiring an eloquent query. We provide data using ->records() or we can just use simple array, but Filament Tables prefer Eloquent. 
            )
            // But since Filament v3, we can use `->query(...)` but we don't have Eloquent here.
            // Wait, Filament v3 Table Widget requires an Eloquent query unless we override `getTableRecords()` or use a different approach.
            // Actually, for static data, it's easier to use a custom view.
            ->view('filament.widgets.operational-stages-widget')
            ->emptyStateHeading('Data Ringkasan Tahap');
    }

    public function getDistributionData(): array
    {
        $filterData = OperationalDashboardFilterData::fromArray($this->filters);
        $service = new OperationalDashboardService($filterData);
        return $service->getStageDistribution();
    }
}
