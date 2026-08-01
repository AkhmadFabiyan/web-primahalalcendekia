<?php

namespace App\Modules\Dashboards\Services;

use App\Modules\Projects\Models\Project;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProjectProgressFreshnessService
{
    /**
     * Get the latest progress date for a specific project.
     */
    public static function getLastProgressDate(Project $project): ?Carbon
    {
        $lastChangedAt = DB::table('workflow_steps')
            ->where('project_id', $project->id)
            ->max('last_changed_at');

        $lastHistoryAt = DB::table('workflow_histories')
            ->where('project_id', $project->id)
            ->max('created_at');

        $lastTaskAt = DB::table('tasks')
            ->where('project_id', $project->id)
            ->whereNotNull('completed_at')
            ->max('completed_at');

        $lastTaskStartedAt = DB::table('tasks')
            ->where('project_id', $project->id)
            ->whereNotNull('started_at')
            ->max('started_at');

        $dates = array_filter([
            $lastChangedAt,
            $lastHistoryAt,
            $lastTaskAt,
            $lastTaskStartedAt,
        ]);

        if (empty($dates)) {
            return $project->activated_at; // Fallback to activated_at if no progress
        }

        return Carbon::parse(max($dates));
    }
}
