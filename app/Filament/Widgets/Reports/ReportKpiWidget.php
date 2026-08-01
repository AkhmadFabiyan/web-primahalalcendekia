<?php

namespace App\Filament\Widgets\Reports;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use App\Modules\Reports\Services\ManagementReportService;
use App\Modules\Reports\DataTransferObjects\ManagementReportFilterData;

class ReportKpiWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && $user->can('report.view');
    }

    protected function getStats(): array
    {
        $filterData = ManagementReportFilterData::fromArray($this->filters);
        $service = new ManagementReportService($filterData);
        $kpis = $service->getKpis();

        return [
            Stat::make('Total Lead', number_format($kpis['total_lead']))
                ->description('Cohort lead baru periode ini')
                ->icon('heroicon-m-user-group'),
            Stat::make('Lead Deal', number_format($kpis['lead_deal']))
                ->description('Lead terkonversi pada periode ini')
                ->icon('heroicon-m-check-circle'),
            Stat::make('Conversion Rate', $kpis['conversion_rate'] . '%')
                ->description('Dari cohort periode terpilih')
                ->icon('heroicon-m-chart-bar'),
            Stat::make('Project Aktif', number_format($kpis['project_aktif']))
                ->description('Project belum selesai di akhir periode')
                ->icon('heroicon-m-briefcase'),
            Stat::make('Sertifikat Terbit', number_format($kpis['sertifikat_terbit']))
                ->description('Sertifikat pada periode ini')
                ->icon('heroicon-m-document-check'),
            Stat::make('Project Selesai', number_format($kpis['project_selesai']))
                ->description('Selesai pada periode ini')
                ->icon('heroicon-m-flag'),
            Stat::make('Kas Terverifikasi', 'Rp ' . number_format($kpis['kas_masuk'], 0, ',', '.'))
                ->description('Payment komersial verifikasi')
                ->icon('heroicon-m-banknotes'),
            Stat::make('Outstanding', 'Rp ' . number_format($kpis['outstanding'], 0, ',', '.'))
                ->description('Sisa tagihan di akhir periode')
                ->icon('heroicon-m-calculator'),
        ];
    }
}
