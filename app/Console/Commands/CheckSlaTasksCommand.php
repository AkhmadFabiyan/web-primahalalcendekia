<?php

namespace App\Console\Commands;

use App\Modules\Workflows\Enums\SlaCycleStatus;
use App\Modules\Workflows\Enums\SlaEventType;
use App\Modules\Workflows\Models\TaskSlaCycle;
use App\Modules\Workflows\Models\TaskSlaEvent;
use Illuminate\Console\Command;
use Carbon\Carbon;
use Filament\Notifications\Notification;

class CheckSlaTasksCommand extends Command
{
    protected $signature = 'sla:check';
    protected $description = 'Check SLA tasks for reminders and escalations';

    public function handle()
    {
        $this->info('Starting SLA check...');
        $now = now();

        $activeCycles = TaskSlaCycle::with('task.assigneeUser', 'task.project')
            ->where('status', SlaCycleStatus::ACTIVE->value)
            ->whereNotNull('due_at')
            ->get();

        foreach ($activeCycles as $cycle) {
            $policy = $cycle->duration_snapshot;
            $task = $cycle->task;
            $dueAt = $cycle->due_at;

            if (!$task || !$dueAt || !$task->assigneeUser) continue;

            // 1. Check if breached
            if ($now->greaterThan($dueAt) && !$cycle->breached_at) {
                $cycle->update(['breached_at' => $now]);
                
                // Add event
                TaskSlaEvent::create([
                    'task_sla_cycle_id' => $cycle->id,
                    'event_type' => SlaEventType::BREACHED->value,
                    'occurred_at' => $now,
                    'deduplication_key' => "BREACHED_{$cycle->id}",
                ]);

                // Notify assignee
                Notification::make()
                    ->title("SLA Melewati Batas Waktu")
                    ->body("Tugas '{->title}' telah melewati batas waktu.")
                    ->danger()
                    ->sendToDatabase($task->assigneeUser);
            }

            // 2. Reminders
            $reminderMinutes = $policy['reminder_before_minutes'] ?? null;
            if ($reminderMinutes && $dueAt->isFuture() && !$cycle->breached_at) {
                $reminderTime = $dueAt->copy()->subMinutes($reminderMinutes);
                if ($now->greaterThanOrEqualTo($reminderTime)) {
                    $dedupKey = "REMINDER_{$cycle->id}";
                    if (!TaskSlaEvent::where('deduplication_key', $dedupKey)->exists()) {
                        TaskSlaEvent::create([
                            'task_sla_cycle_id' => $cycle->id,
                            'event_type' => SlaEventType::REMINDER_SENT->value,
                            'occurred_at' => $now,
                            'deduplication_key' => $dedupKey,
                            'recipient_id' => $task->assigned_to,
                        ]);

                        Notification::make()
                            ->title("Reminder SLA")
                            ->body("Tugas '{->title}' akan jatuh tempo pada " . $dueAt->format('d M Y H:i'))
                            ->warning()
                            ->sendToDatabase($task->assigneeUser);
                    }
                }
            }

            // 3. Escalations
            // Lvl 1 (Manager Operasional), Lvl 2 (Direktur)
            // Lvl 1
            $esc1Minutes = $policy['first_escalation_after_minutes'] ?? null;
            if ($cycle->breached_at && $esc1Minutes) {
                $esc1Time = $cycle->breached_at->copy()->addMinutes($esc1Minutes);
                if ($now->greaterThanOrEqualTo($esc1Time)) {
                    $dedupKey = "ESC1_{$cycle->id}";
                    if (!TaskSlaEvent::where('deduplication_key', $dedupKey)->exists()) {
                        TaskSlaEvent::create([
                            'task_sla_cycle_id' => $cycle->id,
                            'event_type' => SlaEventType::ESCALATED_LEVEL_1->value,
                            'occurred_at' => $now,
                            'deduplication_key' => $dedupKey,
                        ]);
                        $cycle->update(['last_escalated_at' => $now]);

                        // Notify Manager
                        $managers = \App\Models\User::role('Manager Operasional')->get();
                        foreach ($managers as $manager) {
                            Notification::make()
                                ->title("Eskalasi Level 1")
                                ->body("Tugas '{->title}' milik {->assigneeUser->name} telah melewati SLA lebih dari {} menit.")
                                ->danger()
                                ->sendToDatabase($manager);
                        }
                    }
                }
            }

            // Lvl 2
            $esc2Minutes = $policy['second_escalation_after_minutes'] ?? null;
            if ($cycle->breached_at && $esc2Minutes) {
                $esc2Time = $cycle->breached_at->copy()->addMinutes($esc2Minutes);
                if ($now->greaterThanOrEqualTo($esc2Time)) {
                    $dedupKey = "ESC2_{$cycle->id}";
                    if (!TaskSlaEvent::where('deduplication_key', $dedupKey)->exists()) {
                        TaskSlaEvent::create([
                            'task_sla_cycle_id' => $cycle->id,
                            'event_type' => SlaEventType::ESCALATED_LEVEL_2->value,
                            'occurred_at' => $now,
                            'deduplication_key' => $dedupKey,
                        ]);
                        $cycle->update(['last_escalated_at' => $now]);

                        // Notify Direktur
                        $direkturs = \App\Models\User::role('Direktur')->get();
                        foreach ($direkturs as $direktur) {
                            Notification::make()
                                ->title("Eskalasi Level 2")
                                ->body("Tugas '{->title}' milik {->assigneeUser->name} telah melewati SLA lebih dari {} menit.")
                                ->danger()
                                ->sendToDatabase($direktur);
                        }
                    }
                }
            }
        }

        $this->info('SLA check completed.');
    }
}
