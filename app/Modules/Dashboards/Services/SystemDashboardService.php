<?php

namespace App\Modules\Dashboards\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class SystemDashboardService
{
    public function getSystemHealth(): array
    {
        return Cache::remember('dashboard:system:health', 60, function () {
            $dbConnected = false;
            try {
                DB::connection()->getPdo();
                $dbConnected = true;
            } catch (\Exception $e) {}

            $storageTotal = disk_total_space(base_path());
            $storageFree = disk_free_space(base_path());
            $storageUsedPercent = $storageTotal > 0 ? round((($storageTotal - $storageFree) / $storageTotal) * 100, 2) : 0;

            $cacheStatus = 'Operational';
            try {
                Cache::store()->get('system_health_test');
            } catch (\Exception $e) {
                $cacheStatus = 'Error';
            }

            return [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'filament_version' => '5.x',
                'database_status' => $dbConnected ? 'Connected' : 'Disconnected',
                'queue_status' => 'Operational',
                'cache_status' => $cacheStatus,
                'last_scheduler_run' => Cache::get('last_scheduler_run', 'Never'),
                'failed_jobs' => DB::table('failed_jobs')->count(),
                'storage_used_percent' => $storageUsedPercent,
                'storage_free_gb' => round($storageFree / 1073741824, 2),
                'storage_total_gb' => round($storageTotal / 1073741824, 2),
            ];
        });
    }

    public function getUserStats(): array
    {
        return Cache::remember('dashboard:system:user_stats', 60, function () {
            $totalActive = User::where('status', 'ACTIVE')->count();
            $totalInactive = User::where('status', 'INACTIVE')->count();

            $roles = DB::table('roles')->pluck('name');
            $roleCounts = [];
            foreach ($roles as $role) {
                $roleCounts[$role] = User::role($role)->count();
            }

            return [
                'total_active' => $totalActive,
                'total_inactive' => $totalInactive,
                'role_counts' => $roleCounts,
            ];
        });
    }
}
