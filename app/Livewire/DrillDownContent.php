<?php

namespace App\Livewire;

use Livewire\Component;
use App\Modules\Dashboards\Services\OperationalDashboardService;
use App\Modules\Dashboards\DataTransferObjects\OperationalDashboardFilterData;
use App\Modules\Workflows\Models\WorkflowStep;
use App\Modules\Workflows\Enums\WorkflowStatus;
use Filament\Notifications\Notification;

class DrillDownContent extends Component
{
    public $type;
    public $key;
    public $filters = [];

    public function mount($type, $key, $filters)
    {
        $this->type = $type;
        $this->key = $key;
        $this->filters = $filters;
    }

    public function getProjectsProperty()
    {
        $filterData = OperationalDashboardFilterData::fromArray($this->filters);
        
        // We will mock the getProjectsForDrillDown method for now, or we can just fetch all and filter in memory since we are caching metadata.
        $service = new OperationalDashboardService($filterData);
        // We need a public method to get metadata
        $metadata = $service->getProjectsWithMetadataPublic();
        
        // Filter based on key
        if ($this->key === 'totalKlien') {
            return $metadata;
        } elseif ($this->key === 'prosesEntry') {
            return $metadata->filter(function ($item) {
                $status = $item->project->entry_status;
                return $status && $status !== WorkflowStatus::ENTRY_NOT_STARTED->value && $status !== WorkflowStatus::ENTRY_COMPLETED->value;
            });
        } elseif ($this->key === 'menungguAudit') {
            return $metadata->filter(function ($item) {
                $status = $item->project->companion_status;
                return in_array($status, [
                    WorkflowStatus::WAITING_AUDIT_SCHEDULE->value,
                    WorkflowStatus::AUDIT_PREPARATION->value,
                    WorkflowStatus::FIELD_EVIDENCE_INCOMPLETE->value,
                    WorkflowStatus::AUDIT_SCHEDULED->value,
                ]);
            });
        } elseif ($this->key === 'sertifikatTerbit') {
            return $metadata->filter(function ($item) {
                return in_array($item->project->status->value, [
                    \App\Modules\Projects\Enums\ProjectStatus::CERTIFICATE_ISSUED->value,
                    \App\Modules\Projects\Enums\ProjectStatus::WAITING_SETTLEMENT->value,
                    \App\Modules\Projects\Enums\ProjectStatus::COMPLETED->value,
                ]);
            });
        } elseif ($this->key === 'audit7Hari') {
            return collect(); // Mock for now
        } elseif ($this->key === 'prosesRevisi') {
            return $metadata->filter(function ($item) {
                $hasRevisionTask = $item->project->tasks->where('status', 'REVISION')->isNotEmpty();
                $auditorStatus = $item->project->auditor_status;
                $hasCorrection = in_array($auditorStatus, [
                    WorkflowStatus::NONCONFORMITY_FOUND->value,
                    WorkflowStatus::WAITING_CORRECTIVE_EVIDENCE->value,
                ]);
                return $hasRevisionTask || $hasCorrection;
            });
        } elseif ($this->key === 'perluFollowUp') {
            return $metadata->filter(fn($item) => $item->is_follow_up);
        } elseif ($this->key === 'kritis') {
            return $metadata->filter(fn($item) => $item->is_critical);
        }

        // if chart stage
        if ($this->type === 'stage') {
            return $metadata->where('stage', $this->key);
        }
        
        return collect();
    }

    public function updateProgress($projectId, $trackerType, $newStatus)
    {
        // Add actual progress update logic using a service here
        // Validate allowed_transitions and isLocked
        
        $project = \App\Modules\Projects\Models\Project::find($projectId);
        if (!$project) return;
        
        if ($project->isLocked()) {
            Notification::make()->title('Project terkunci')->danger()->send();
            return;
        }

        $workflowStep = WorkflowStep::where('project_id', $project->id)
            ->where('step_code', $trackerType)
            ->first();
            
        if ($workflowStep) {
            $workflowStep->update(['status' => $newStatus, 'last_changed_at' => now()]);
            Notification::make()->title('Progress berhasil diupdate')->success()->send();
            $this->dispatch('progressUpdated'); // to refresh data
        } else {
            Notification::make()->title('Tracker tidak ditemukan')->danger()->send();
        }
    }

    public function render()
    {
        return view('livewire.drill-down-content');
    }
}
