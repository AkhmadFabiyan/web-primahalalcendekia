<?php

namespace App\Modules\Workflows\Services;

use App\Modules\Workflows\Enums\SlaCycleStatus;
use App\Modules\Workflows\Enums\SlaDurationUnit;
use App\Modules\Workflows\Enums\SlaEventType;
use App\Modules\Workflows\Models\SlaPolicy;
use App\Modules\Workflows\Models\Task;
use App\Modules\Workflows\Models\TaskSlaCycle;
use App\Modules\Workflows\Models\TaskSlaEvent;
use Carbon\Carbon;
use Exception;

class SlaManagerService
{
    protected BusinessCalendarService $calendarService;

    public function __construct(BusinessCalendarService $calendarService)
    {
        $this->calendarService = $calendarService;
    }

    public function getActivePolicy(string $taskType): ?array
    {
        $policy = SlaPolicy::where('task_type', $taskType)
            ->where('is_active', true)
            ->first();

        if ($policy) {
            return $policy->toArray();
        }

        // Fallback to config
        $default = config("sla.default_policies.{$taskType}");
        if ($default) {
            return $default;
        }

        return null;
    }

    public function startCycle(Task $task): void
    {
        // SLA only starts if task has assignee and entered_at
        if (!$task->assigned_to || !$task->entered_at) {
            return;
        }

        // Check if there is an active cycle
        $activeCycle = $task->slaCycles()->where('status', SlaCycleStatus::ACTIVE->value)->first();
        if ($activeCycle) {
            return; // Already started
        }

        $policy = $this->getActivePolicy($task->task_type);
        if (!$policy) {
            return; // No SLA for this task type
        }

        $dueAt = $this->calculateDueAt($task->entered_at, $policy, $task);

        $cycleNumber = $task->slaCycles()->max('cycle_number') ?? 0;

        $cycle = TaskSlaCycle::create([
            'task_id' => $task->id,
            'cycle_number' => $cycleNumber + 1,
            'sla_policy_id' => $policy['id'] ?? null,
            'duration_snapshot' => $policy,
            'started_at' => $task->entered_at,
            'due_at' => $dueAt,
            'status' => SlaCycleStatus::ACTIVE->value,
        ]);

        TaskSlaEvent::create([
            'task_sla_cycle_id' => $cycle->id,
            'event_type' => SlaEventType::STARTED->value,
            'occurred_at' => now(),
            'deduplication_key' => "STARTED_$cycle->id",
        ]);

        if ($dueAt) {
            $task->deadline = $dueAt;
            $task->save();
        }
    }

    public function completeCycle(Task $task): void
    {
        $cycle = $task->slaCycles()->where('status', SlaCycleStatus::ACTIVE->value)->first();
        if (!$cycle) {
            return;
        }

        $now = now();
        
        // If it was breached, it stays breached but completed.
        // If it's active and not passed due_at, it's MET.
        $status = SlaCycleStatus::MET->value;
        if ($cycle->due_at && $now->greaterThan($cycle->due_at)) {
            $status = SlaCycleStatus::BREACHED->value;
            if (!$cycle->breached_at) {
                $cycle->breached_at = $cycle->due_at; // assuming it breached at due date
            }
        } elseif ($cycle->breached_at) {
            $status = SlaCycleStatus::BREACHED->value;
        }

        $cycle->update([
            'completed_at' => $now,
            'status' => $status,
        ]);

        TaskSlaEvent::create([
            'task_sla_cycle_id' => $cycle->id,
            'event_type' => SlaEventType::COMPLETED->value,
            'occurred_at' => $now,
            'deduplication_key' => "COMPLETED_$cycle->id",
        ]);
    }

    public function newCycle(Task $task): void
    {
        $this->completeCycle($task);
        // Reset entered_at to now so new cycle starts from now
        $task->entered_at = now();
        $task->save();
        $this->startCycle($task);
    }

    public function pauseCycle(Task $task, string $reason, string $actorId): void
    {
        $cycle = $task->slaCycles()->where('status', SlaCycleStatus::ACTIVE->value)->first();
        if (!$cycle) {
            throw new Exception("SLA is not active.");
        }

        $now = now();
        $cycle->update([
            'paused_at' => $now,
            'status' => SlaCycleStatus::PAUSED->value,
        ]);

        TaskSlaEvent::create([
            'task_sla_cycle_id' => $cycle->id,
            'event_type' => SlaEventType::PAUSED->value,
            'occurred_at' => $now,
            'recipient_id' => $actorId, // storing actor in recipient_id or metadata
            'deduplication_key' => "PAUSED_$cycle->id" . "_" . $now->timestamp,
            'metadata' => ['reason' => $reason, 'actor_id' => $actorId],
        ]);
    }

    public function resumeCycle(Task $task, string $reason, string $actorId): void
    {
        $cycle = $task->slaCycles()->where('status', SlaCycleStatus::PAUSED->value)->first();
        if (!$cycle) {
            throw new Exception("SLA is not paused.");
        }

        $now = now();
        $pausedMinutes = $cycle->paused_at ? $now->diffInMinutes($cycle->paused_at) : 0;

        // Shift due date by paused duration. If using business calendar, we should add business minutes
        if ($cycle->due_at) {
            if ($cycle->duration_snapshot['uses_business_calendar'] ?? true) {
                // Add business hours matching paused duration (approx)
                // For exact calculation, count business minutes between paused_at and now
                // This is a simplified fallback
                $cycle->due_at = $cycle->due_at->addMinutes($pausedMinutes);
            } else {
                $cycle->due_at = $cycle->due_at->addMinutes($pausedMinutes);
            }
        }

        $cycle->update([
            'paused_at' => null,
            'total_paused_minutes' => $cycle->total_paused_minutes + $pausedMinutes,
            'status' => SlaCycleStatus::ACTIVE->value,
        ]);

        TaskSlaEvent::create([
            'task_sla_cycle_id' => $cycle->id,
            'event_type' => SlaEventType::RESUMED->value,
            'occurred_at' => $now,
            'deduplication_key' => "RESUMED_$cycle->id" . "_" . $now->timestamp,
            'metadata' => ['reason' => $reason, 'actor_id' => $actorId, 'paused_minutes' => $pausedMinutes],
        ]);

        if ($cycle->due_at) {
            $task->deadline = $cycle->due_at;
            $task->save();
        }
    }

    public function adjustDeadline(Task $task, Carbon $newDeadline, string $reason, string $actorId): void
    {
        $cycle = $task->slaCycles()->whereIn('status', [SlaCycleStatus::ACTIVE->value, SlaCycleStatus::PAUSED->value])->first();
        if (!$cycle) {
            throw new Exception("SLA Cycle is not active.");
        }

        $oldDeadline = $cycle->due_at;
        $cycle->update(['due_at' => $newDeadline]);

        TaskSlaEvent::create([
            'task_sla_cycle_id' => $cycle->id,
            'event_type' => SlaEventType::DEADLINE_ADJUSTED->value,
            'occurred_at' => now(),
            'deduplication_key' => "ADJUST_$cycle->id" . "_" . now()->timestamp,
            'metadata' => [
                'reason' => $reason,
                'actor_id' => $actorId,
                'old_due_at' => $oldDeadline?->toIso8601String(),
                'new_due_at' => $newDeadline->toIso8601String(),
            ],
        ]);

        $task->deadline = $newDeadline;
        $task->save();
    }

    protected function calculateDueAt(Carbon $start, array $policy, Task $task): ?Carbon
    {
        $value = $policy['duration_value'] ?? 0;
        $unit = $policy['duration_unit'] ?? 'BUSINESS_DAYS';
        $useBusiness = $policy['uses_business_calendar'] ?? true;

        if ($unit === 'SCHEDULED_DATE') {
            // For Audit Execution, look up schedule
            // For now if deadline is set by external source, use it
            return $task->deadline;
        }

        if ($useBusiness) {
            if ($unit === 'BUSINESS_DAYS') {
                return $this->calendarService->addBusinessDays($start, $value);
            } elseif ($unit === 'HOURS') {
                return $this->calendarService->addBusinessHours($start, $value);
            }
        }

        // Calendar fallback
        if ($unit === 'BUSINESS_DAYS') {
            return $start->copy()->addDays($value);
        } elseif ($unit === 'HOURS') {
            return $start->copy()->addHours($value);
        } elseif ($unit === 'MINUTES') {
            return $start->copy()->addMinutes($value);
        }

        return null;
    }
}
