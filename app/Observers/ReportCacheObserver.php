<?php

namespace App\Observers;

use Illuminate\Support\Facades\Cache;

class ReportCacheObserver
{
    protected function bumpVersion()
    {
        Cache::increment('management-report:version');
        // also bump operational and finance dashboards to be safe if they use it, or they have their own keys
    }

    public function saved($model)
    {
        $dirty = $model->getDirty();
        
        if (empty($dirty)) {
            return;
        }

        $shouldBump = false;

        if ($model instanceof \App\Modules\Leads\Models\Lead) {
            // Lead converted or status changed
            if (array_key_exists('status', $dirty) || array_key_exists('project_id', $dirty)) {
                $shouldBump = true;
            }
        } elseif ($model instanceof \App\Modules\Projects\Models\Project) {
            // Project activated, completed, cancelled, reopened
            if (array_key_exists('status', $dirty) || array_key_exists('activated_at', $dirty) || array_key_exists('completed_at', $dirty) || array_key_exists('cancelled_at', $dirty)) {
                $shouldBump = true;
            }
        } elseif ($model instanceof \App\Modules\Payments\Models\Invoice) {
            // Invoice published
            if (array_key_exists('status', $dirty)) {
                $shouldBump = true;
            }
        } elseif ($model instanceof \App\Modules\Payments\Models\Payment) {
            // Payment verified
            if (array_key_exists('status', $dirty)) {
                $shouldBump = true;
            }
        } elseif ($model instanceof \App\Modules\Projects\Models\Certificate) {
            // Certificate issued
            if (array_key_exists('issued_at', $dirty)) {
                $shouldBump = true;
            }
        } elseif ($model instanceof \App\Modules\Projects\Models\ProjectAssignment) {
            // Assignment changed
            if (array_key_exists('ended_at', $dirty) || array_key_exists('user_id', $dirty)) {
                $shouldBump = true;
            }
        }

        if ($shouldBump) {
            $this->bumpVersion();
        }
    }
    
    public function created($model)
    {
        // For tasks/history
        if ($model instanceof \App\Modules\Workflows\Models\Task || $model instanceof \App\Modules\Projects\Models\ProjectAssignment) {
            $this->bumpVersion();
        }
    }
}
