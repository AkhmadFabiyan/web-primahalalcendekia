<?php

namespace App\Modules\Dashboards\Services;

use App\Modules\Dashboards\DataTransferObjects\OperationalDashboardFilterData;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Enums\ProjectStatus;
use App\Modules\Workflows\Enums\WorkflowStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class OperationalDashboardService
{
    private const CACHE_TTL = 60; // 60 seconds
    private const FOLLOW_UP_DAYS = 3;
    private const CRITICAL_DAYS = 7;

    public function __construct(private readonly OperationalDashboardFilterData $filter)
    {
    }

    private function getCacheKey(string $type): string
    {
        $hash = md5(json_encode((array) $this->filter));
        $scope = auth()->id();
        return "operational-dashboard:{$type}:{$hash}:{$scope}";
    }

    private function getBaseQuery()
    {
        $query = Project::query()
            ->with(['client', 'assignments', 'tasks'])
            ->leftJoin('clients', 'projects.client_id', '=', 'clients.id')
            // Join workflow steps to get statuses for entry, companion, auditor
            ->leftJoin('workflow_steps as ws_entry', function ($join) {
                $join->on('projects.id', '=', 'ws_entry.project_id')
                     ->where('ws_entry.step_code', '=', 'ENTRY_PROGRESS');
            })
            ->leftJoin('workflow_steps as ws_companion', function ($join) {
                $join->on('projects.id', '=', 'ws_companion.project_id')
                     ->where('ws_companion.step_code', '=', 'COMPANION_PROGRESS');
            })
            ->leftJoin('workflow_steps as ws_auditor', function ($join) {
                $join->on('projects.id', '=', 'ws_auditor.project_id')
                     ->where('ws_auditor.step_code', '=', 'AUDITOR_PROGRESS');
            })
            ->select([
                'projects.*',
                'clients.business_id as client_business_id',
                'clients.company_name as client_name',
                'clients.client_type as client_type_enum',
                'ws_entry.status as entry_status',
                'ws_companion.status as companion_status',
                'ws_auditor.status as auditor_status',
            ]);

        // Exclude cancelled by default, unless status filter specifies it
        if ($this->filter->status) {
            $query->where('projects.status', $this->filter->status);
        } else {
            $query->where('projects.status', '!=', ProjectStatus::CANCELLED->value);
        }

        if ($this->filter->period_start) {
            $query->whereDate('projects.activated_at', '>=', $this->filter->period_start);
        }
        if ($this->filter->period_end) {
            $query->whereDate('projects.activated_at', '<=', $this->filter->period_end);
        }
        if ($this->filter->service) {
            $query->where('projects.service_type', $this->filter->service);
        }
        if ($this->filter->client_type) {
            $query->where('clients.client_type', $this->filter->client_type);
        }
        if ($this->filter->pic_id) {
            $query->whereHas('assignments', function ($q) {
                $q->where('user_id', $this->filter->pic_id)
                  ->whereNull('ended_at');
            });
        }
        
        return $query;
    }

    /**
     * Resolves metadata for each project, including freshness and stage.
     */
    public function getProjectsWithMetadataPublic()
    {
        return $this->getProjectsWithMetadata();
    }

    private function getProjectsWithMetadata()
    {
        return Cache::remember($this->getCacheKey('projects_metadata'), self::CACHE_TTL, function () {
            $projects = $this->getBaseQuery()->get();
            
            $now = Carbon::now();

            return $projects->map(function ($project) use ($now) {
                $lastProgressDate = ProjectProgressFreshnessService::getLastProgressDate($project) ?? Carbon::parse('2000-01-01');
                $daysSinceProgress = $lastProgressDate->diffInDays($now);

                $stage = OperationalStageResolver::resolve(
                    $project->status->value,
                    $project->companion_status,
                    $project->auditor_status
                );

                $isCompleted = in_array($project->status->value, [ProjectStatus::COMPLETED->value, ProjectStatus::CANCELLED->value, ProjectStatus::CERTIFICATE_ISSUED->value]);
                $isCritical = !$isCompleted && $daysSinceProgress > self::CRITICAL_DAYS;
                $isFollowUp = !$isCompleted && !$isCritical && $daysSinceProgress > self::FOLLOW_UP_DAYS;
                $isCurrent = !$isCompleted && !$isCritical && !$isFollowUp;

                $updateCondition = 'Terkini';
                if ($isCompleted) $updateCondition = 'Selesai';
                elseif ($isCritical) $updateCondition = 'Kritis';
                elseif ($isFollowUp) $updateCondition = 'Perlu Follow Up';

                return (object)[
                    'project' => $project,
                    'stage' => $stage,
                    'last_progress_date' => $lastProgressDate,
                    'days_since_progress' => $daysSinceProgress,
                    'update_condition' => $updateCondition,
                    'is_completed' => $isCompleted,
                    'is_critical' => $isCritical,
                    'is_follow_up' => $isFollowUp,
                    'is_current' => $isCurrent,
                ];
            });
        });
    }

    public function getKPIs(): array
    {
        $metadata = $this->getProjectsWithMetadata();

        // If stage filter is set, apply it across KPIs
        if ($this->filter->stage) {
            $metadata = $metadata->where('stage', $this->filter->stage);
        }

        $totalKlien = $metadata->count();

        $prosesEntry = $metadata->filter(function ($item) {
            $status = $item->project->entry_status;
            return $status && $status !== WorkflowStatus::ENTRY_NOT_STARTED->value && $status !== WorkflowStatus::ENTRY_COMPLETED->value;
        })->count();

        $menungguAudit = $metadata->filter(function ($item) {
            $status = $item->project->companion_status;
            return in_array($status, [
                WorkflowStatus::WAITING_AUDIT_SCHEDULE->value,
                WorkflowStatus::AUDIT_PREPARATION->value,
                WorkflowStatus::FIELD_EVIDENCE_INCOMPLETE->value,
                WorkflowStatus::AUDIT_SCHEDULED->value,
            ]);
        })->count();

        $sertifikatTerbit = $metadata->filter(function ($item) {
            return in_array($item->project->status->value, [
                ProjectStatus::CERTIFICATE_ISSUED->value,
                ProjectStatus::WAITING_SETTLEMENT->value,
                ProjectStatus::COMPLETED->value,
            ]);
        })->count();

        // Dummy logic for Audit 7 Hari for now unless we query task deadlines
        $audit7Hari = 0; 
        
        $prosesRevisi = $metadata->filter(function ($item) {
            // Revisi jika ada task REVISION atau auditor status = NONCONFORMITY_FOUND, dll.
            $hasRevisionTask = $item->project->tasks->where('status', 'REVISION')->isNotEmpty();
            $auditorStatus = $item->project->auditor_status;
            $hasCorrection = in_array($auditorStatus, [
                WorkflowStatus::NONCONFORMITY_FOUND->value,
                WorkflowStatus::WAITING_CORRECTIVE_EVIDENCE->value,
            ]);
            return $hasRevisionTask || $hasCorrection;
        })->count();

        $perluFollowUp = $metadata->filter(fn($item) => $item->is_follow_up)->count();
        $kritis = $metadata->filter(fn($item) => $item->is_critical)->count();

        return compact(
            'totalKlien', 'prosesEntry', 'menungguAudit', 'sertifikatTerbit',
            'audit7Hari', 'prosesRevisi', 'perluFollowUp', 'kritis'
        );
    }

    public function getStageDistribution(): array
    {
        $metadata = $this->getProjectsWithMetadata();

        $distribution = [
            OperationalStageResolver::STAGE_ENTRY => 0,
            OperationalStageResolver::STAGE_PREP_AUDIT => 0,
            OperationalStageResolver::STAGE_AUDIT => 0,
            OperationalStageResolver::STAGE_FATWA => 0,
            OperationalStageResolver::STAGE_CERT_ISSUED => 0,
        ];

        foreach ($metadata as $item) {
            if (isset($distribution[$item->stage])) {
                $distribution[$item->stage]++;
            }
        }

        return $distribution;
    }

    public function getUpdateConditions(): array
    {
        $metadata = $this->getProjectsWithMetadata();

        if ($this->filter->stage) {
            $metadata = $metadata->where('stage', $this->filter->stage);
        }

        $conditions = [
            'Selesai' => 0,
            'Kritis' => 0,
            'Perlu Follow Up' => 0,
            'Terkini' => 0,
        ];

        foreach ($metadata as $item) {
            $conditions[$item->update_condition]++;
        }

        return $conditions;
    }

    public function getPriorityList()
    {
        $metadata = $this->getProjectsWithMetadata();

        if ($this->filter->stage) {
            $metadata = $metadata->where('stage', $this->filter->stage);
        }

        // Return items that are critical or follow up
        return $metadata->filter(function ($item) {
            return $item->is_critical || $item->is_follow_up;
        })->sortByDesc('days_since_progress')->values();
    }
}
