<?php

namespace App\Filament\Widgets;

use App\Modules\Dashboards\Services\SystemDashboardService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserStatsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        return auth()->user()->isSuperAdmin();
    }

    protected function getStats(): array
    {
        $service = app(SystemDashboardService::class);
        $stats = $service->getUserStats();

        return [
            Stat::make('Total Pengguna Aktif', $stats['total_active'])
                ->description('Akun yang dapat login')
                ->icon('heroicon-o-users')
                ->color('success'),

            Stat::make('Total Pengguna Non-Aktif', $stats['total_inactive'])
                ->description('Akun yang ditangguhkan')
                ->icon('heroicon-o-user-minus')
                ->color('danger'),
        ];
    }
}
