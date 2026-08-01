<?php

namespace App\Modules\Workflows\Services;

use App\Models\User;
use App\Modules\Workflows\Models\Task;
use App\Modules\Workflows\Enums\TaskStatus;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class PersonalWorkloadService
{
    /**
     * Get the number of active tasks for a user (used in Navigation Badge and Dashboard).
     */
    public function getActiveTasksCount(User $user): int
    {
        return Cache::remember("dashboard:personal:{$user->id}:active_tasks_count", 60, function () use ($user) {
            return Task::query()
                ->where('assigned_to', $user->id)
                ->whereNull('ended_at')
                ->whereNotIn('status', [TaskStatus::COMPLETED->value, TaskStatus::CANCELLED->value])
                ->count();
        });
    }

    /**
     * Get the number of overdue tasks for a user.
     */
    public function getOverdueTasksCount(User $user): int
    {
        return Cache::remember("dashboard:personal:{$user->id}:overdue_tasks_count", 60, function () use ($user) {
            return Task::query()
                ->where('assigned_to', $user->id)
                ->whereNull('ended_at')
                ->whereNotIn('status', [TaskStatus::COMPLETED->value, TaskStatus::CANCELLED->value])
                ->whereNotNull('due_date')
                ->where('due_date', '<', Carbon::now())
                ->count();
        });
    }

    /**
     * Get the number of tasks completed today by a user.
     */
    public function getCompletedTodayCount(User $user): int
    {
        return Cache::remember("dashboard:personal:{$user->id}:completed_today_count", 60, function () use ($user) {
            return Task::query()
                ->where('assigned_to', $user->id)
                ->where('status', TaskStatus::COMPLETED->value)
                ->whereDate('completed_at', Carbon::today())
                ->count();
        });
    }

    /**
     * Clear the cache for a specific user's workload stats.
     */
    public function clearUserCache(User $user): void
    {
        Cache::forget("dashboard:personal:{$user->id}:active_tasks_count");
        Cache::forget("dashboard:personal:{$user->id}:overdue_tasks_count");
        Cache::forget("dashboard:personal:{$user->id}:completed_today_count");
    }
}
