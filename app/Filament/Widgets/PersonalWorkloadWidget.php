<?php

namespace App\Filament\Widgets;

use App\Modules\Workflows\Services\PersonalWorkloadService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PersonalWorkloadWidget extends BaseWidget
{
    protected static ?int $sort = 10;

    public static function canView(): bool
    {
        return auth()->user()->isInternalStaff() && !auth()->user()->isSuperAdmin();
    }

    protected function getStats(): array
    {
        $user = auth()->user();
        $service = app(PersonalWorkloadService::class);

        $activeTasks = $service->getActiveTasksCount($user);
        $overdueTasks = $service->getOverdueTasksCount($user);
        $completedToday = $service->getCompletedTodayCount($user);

        return [
            Stat::make('Tugas Saya', $activeTasks)
                ->description('Pekerjaan aktif yang belum selesai')
                ->icon('heroicon-o-clipboard-document-list')
                ->color('primary'),

            Stat::make('Tugas Terlambat', $overdueTasks)
                ->description('Melewati batas waktu')
                ->icon('heroicon-o-exclamation-circle')
                ->color($overdueTasks > 0 ? 'danger' : 'success'),

            Stat::make('Selesai Hari Ini', $completedToday)
                ->description('Produktivitas hari ini')
                ->icon('heroicon-o-check-circle')
                ->color('success'),
        ];
    }
}
