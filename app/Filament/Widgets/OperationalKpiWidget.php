<?php

namespace App\Filament\Widgets;

use App\Modules\Dashboards\Services\OperationalDashboardService;
use App\Modules\Dashboards\DataTransferObjects\OperationalDashboardFilterData;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class OperationalKpiWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && $user->isInternalStaff() && !$user->isSuperAdmin();
    }

    protected function getStats(): array
    {
        $filterData = OperationalDashboardFilterData::fromArray($this->filters);
        $service = new OperationalDashboardService($filterData);
        $kpis = $service->getKPIs();

        return [
            // Baris Pertama
            Stat::make('Total Klien', $kpis['totalKlien'])
                ->extraAttributes(['wire:click' => '$dispatch(\'open-drill-down\', { type: \'kpi\', key: \'totalKlien\' })', 'class' => 'cursor-pointer']),
            Stat::make('Proses Entry', $kpis['prosesEntry'])
                ->extraAttributes(['wire:click' => '$dispatch(\'open-drill-down\', { type: \'kpi\', key: \'prosesEntry\' })', 'class' => 'cursor-pointer']),
            Stat::make('Menunggu Audit', $kpis['menungguAudit'])
                ->extraAttributes(['wire:click' => '$dispatch(\'open-drill-down\', { type: \'kpi\', key: \'menungguAudit\' })', 'class' => 'cursor-pointer']),
            Stat::make('Sertifikat Terbit', $kpis['sertifikatTerbit'])
                ->extraAttributes(['wire:click' => '$dispatch(\'open-drill-down\', { type: \'kpi\', key: \'sertifikatTerbit\' })', 'class' => 'cursor-pointer']),

            // Baris Kedua
            Stat::make('Audit 7 Hari', $kpis['audit7Hari'])
                ->extraAttributes(['wire:click' => '$dispatch(\'open-drill-down\', { type: \'kpi\', key: \'audit7Hari\' })', 'class' => 'cursor-pointer']),
            Stat::make('Proses Revisi', $kpis['prosesRevisi'])
                ->extraAttributes(['wire:click' => '$dispatch(\'open-drill-down\', { type: \'kpi\', key: \'prosesRevisi\' })', 'class' => 'cursor-pointer']),
            Stat::make('Perlu Follow Up', $kpis['perluFollowUp'])
                ->color('warning')
                ->extraAttributes(['wire:click' => '$dispatch(\'open-drill-down\', { type: \'kpi\', key: \'perluFollowUp\' })', 'class' => 'cursor-pointer']),
            Stat::make('Kritis > 7 Hari', $kpis['kritis'])
                ->color('danger')
                ->extraAttributes(['wire:click' => '$dispatch(\'open-drill-down\', { type: \'kpi\', key: \'kritis\' })', 'class' => 'cursor-pointer']),
        ];
    }
}
