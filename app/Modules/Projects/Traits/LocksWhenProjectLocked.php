<?php

namespace App\Modules\Projects\Traits;

use App\Modules\Projects\Models\Project;
use Exception;

trait LocksWhenProjectLocked
{
    public static function bootLocksWhenProjectLocked()
    {
        static::updating(function ($model) {
            $model->checkProjectLock();
        });

        static::deleting(function ($model) {
            $model->checkProjectLock();
        });
    }

    protected function checkProjectLock(): void
    {
        $project = null;

        // Jika model ini adalah Project itu sendiri
        if ($this instanceof Project) {
            $project = $this;
            
            // Bypass lock if only modifying cancellation or completion fields
            // Ini diperlukan saat Reopening atau Cancellation service mengubah status.
            $dirty = $this->getDirty();
            $allowedFields = ['status', 'cancelled_at', 'cancelled_by', 'cancellation_reason', 'cancelled_from_status', 'completed_at', 'updated_at'];
            $isOnlyAllowedFields = count(array_diff(array_keys($dirty), $allowedFields)) === 0;
            
            if ($isOnlyAllowedFields) {
                return; // Let the modification pass
            }
        } else {
            // Jika model memiliki implementasi spesifik
            if (method_exists($this, 'getProjectForLock')) {
                $project = $this->getProjectForLock();
            } elseif (method_exists($this, 'project')) {
                $project = $this->project;
            } elseif (property_exists($this, 'project_id')) {
                $project = Project::find($this->project_id);
            }
        }

        if ($project && $project->isLocked()) {
            // Check original status of project in case we are modifying the project itself from locked to unlocked
            if ($this instanceof Project && !in_array($this->getOriginal('status'), [\App\Modules\Projects\Enums\ProjectStatus::COMPLETED, \App\Modules\Projects\Enums\ProjectStatus::CANCELLED])) {
                // If it was not locked before, we are currently locking it. That's allowed.
                return;
            }
            
            throw new Exception("Data tidak dapat diubah karena Project telah berstatus Selesai atau Dibatalkan. Silakan Buka Kembali Project terlebih dahulu melalui action resmi.");
        }
    }
}
