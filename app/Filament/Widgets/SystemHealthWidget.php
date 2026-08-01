<?php

namespace App\Filament\Widgets;

use App\Modules\Dashboards\Services\SystemDashboardService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SystemHealthWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()->isSuperAdmin();
    }

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $service = app(SystemDashboardService::class);
        $health = $service->getSystemHealth();

        return [
            Stat::make('Database', $health['database_status'])
                ->description('Koneksi Utama')
                ->icon('heroicon-o-circle-stack')
                ->color($health['database_status'] === 'Connected' ? 'success' : 'danger'),

            Stat::make('Cache', $health['cache_status'])
                ->description('Status Cache')
                ->icon('heroicon-o-bolt')
                ->color($health['cache_status'] === 'Operational' ? 'success' : 'danger'),

            Stat::make('Failed Jobs', $health['failed_jobs'])
                ->description('Antrean Gagal')
                ->icon('heroicon-o-exclamation-triangle')
                ->color($health['failed_jobs'] > 0 ? 'danger' : 'success'),

            Stat::make('Storage Usage', "{$health['storage_used_percent']}%")
                ->description("{$health['storage_free_gb']} GB Free / {$health['storage_total_gb']} GB Total")
                ->icon('heroicon-o-server')
                ->color($health['storage_used_percent'] > 90 ? 'danger' : 'success'),
            
            Stat::make('Runtime Environment', 'PHP ' . $health['php_version'])
                ->description('Laravel ' . $health['laravel_version'] . ' / Filament ' . $health['filament_version'])
                ->icon('heroicon-o-command-line')
                ->color('info')
                ->columnSpan(4),
        ];
    }
}
