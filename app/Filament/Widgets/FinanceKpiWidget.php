<?php

namespace App\Filament\Widgets;

use App\Modules\Dashboards\DataTransferObjects\FinanceDashboardFilterData;
use App\Modules\Dashboards\Services\FinanceDashboardService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinanceKpiWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 10;

    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user && $user->can('dashboard.finance.view');
    }

    protected function getStats(): array
    {
        $filterData = FinanceDashboardFilterData::fromArray($this->filters);
        $service = new FinanceDashboardService($filterData);
        $kpis = $service->getKpis();

        return [
            // Baris 1: Kas Masuk & Project
            Stat::make('Total Kas Masuk Komersial', 'Rp '.number_format($kpis['totalKasMasuk'], 0, ',', '.'))
                ->description('Kas Masuk Klien: Rp '.number_format($kpis['kasMasukKlien'], 0, ',', '.').' | Mitra: Rp '.number_format($kpis['kasMasukMitra'], 0, ',', '.'))
                ->color('success')
                ->extraAttributes(['wire:click' => '$dispatch(\'open-drill-down\', { type: \'payment\', key: \'totalKasMasuk\' })', 'class' => 'cursor-pointer']),

            Stat::make('Project Bertagih', $kpis['projectBertagih'])
                ->description('Billing Group: '.$kpis['billingGroupCount'].' | Total Invoice: '.$kpis['jumlahInvoice'])
                ->extraAttributes(['wire:click' => '$dispatch(\'open-drill-down\', { type: \'invoice\', key: \'projectBertagih\' })', 'class' => 'cursor-pointer']),

            // Baris 2: Outstanding & Pending
            Stat::make('Total Outstanding Komersial', 'Rp '.number_format($kpis['totalOutstanding'], 0, ',', '.'))
                ->description('Outstanding Klien: Rp '.number_format($kpis['outstandingKlien'], 0, ',', '.').' | Mitra: Rp '.number_format($kpis['outstandingMitra'], 0, ',', '.'))
                ->color('warning')
                ->extraAttributes(['wire:click' => '$dispatch(\'open-drill-down\', { type: \'invoice\', key: \'totalOutstanding\' })', 'class' => 'cursor-pointer']),

            Stat::make('Pending Payment', $kpis['pendingPaymentCount'])
                ->description('Nominal Pending: Rp '.number_format($kpis['pendingPaymentAmount'], 0, ',', '.'))
                ->color('danger')
                ->extraAttributes(['wire:click' => '$dispatch(\'open-drill-down\', { type: \'payment\', key: \'pendingPaymentCount\' })', 'class' => 'cursor-pointer']),
        ];
    }
}
