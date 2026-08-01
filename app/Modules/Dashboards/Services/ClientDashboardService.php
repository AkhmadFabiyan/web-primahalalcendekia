<?php

namespace App\Modules\Dashboards\Services;

use App\Models\User;
use App\Modules\Projects\Models\Project;
use Illuminate\Support\Facades\Cache;

class ClientDashboardService
{
    /**
     * Get the client project summary.
     */
    public function getProjectOverview(User $user): ?array
    {
        if (!$user->client_id) {
            return null;
        }

        return Cache::remember("dashboard:client:{$user->client_id}:overview", 60, function () use ($user) {
            $project = Project::with(['client'])
                ->where('client_id', $user->client_id)
                ->first();

            if (!$project) return null;

            return [
                'client_name' => $project->client->name,
                'client_type' => $project->client->client_type,
                'project_name' => $project->project_name, // This is basically PHC-HAL-... Client ID
                'status' => $project->status,
                'entry_progress' => $project->entry_progress ?? 0,
                'companion_progress' => $project->companion_progress ?? 0,
                'auditor_progress' => $project->auditor_progress ?? 0,
            ];
        });
    }

    /**
     * Clear the cache for a specific client dashboard.
     */
    public function clearClientCache(string $clientId): void
    {
        Cache::forget("dashboard:client:{$clientId}:overview");
    }
}
