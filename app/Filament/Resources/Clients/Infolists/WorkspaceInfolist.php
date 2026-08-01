<?php

namespace App\Filament\Resources\Clients\Infolists;

use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Components\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use App\Modules\Projects\Services\ProjectHandoverService;
use App\Modules\Projects\Models\SihalalCredential;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class WorkspaceInfolist
{
    public static function schema(): array
    {
        return [
            Tabs::make('Workspace')
                ->tabs([
                    Tabs\Tab::make('Ringkasan')
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            Section::make('Progress Gabungan')
                                ->schema([
                                    \Filament\Infolists\Components\ViewEntry::make('progress_gabungan')
                                        ->hiddenLabel()
                                        ->view('filament.components.workflow-progress'),
                                ])
                                ->visible(fn ($record) => $record->project !== null)
                                ->columnSpanFull(),

                            Grid::make(3)->schema([
                                Section::make('Informasi Project')
                                    ->schema([
                                        TextEntry::make('business_id')->label('ID Klien'),
                                        TextEntry::make('project.service_type')->label('Layanan'),
                                        TextEntry::make('project.status')->label('Status')->badge(),
                                        TextEntry::make('project.assignments.user.name')
                                            ->label('Assignment')
                                            ->listWithLineBreaks(),
                                    ])->columnSpan(1),

                                Section::make('Informasi Klien')
                                    ->schema([
                                        TextEntry::make('company_name')->label('Perusahaan'),
                                        TextEntry::make('client_type')->label('Tipe Klien')->badge(),
                                        TextEntry::make('partner.name')->label('Mitra')->hidden(fn ($record) => !$record->partner_id),
                                        TextEntry::make('project.client_nominal')->label('Nominal Klien')->money('IDR'),
                                        TextEntry::make('project.partner_nominal')->label('Nominal Mitra')->money('IDR')->hidden(fn ($record) => !$record->project?->partner_nominal),
                                        TextEntry::make('pic_name')->label('PIC'),
                                        TextEntry::make('pic_phone')->label('Phone PIC'),
                                        TextEntry::make('pic_email')->label('Email PIC'),
                                    ])->columnSpan(1),
                                
                                Section::make('Persiapan Entry')
                                    ->schema([
                                        TextEntry::make('document_status')
                                            ->label('Status Dokumen')
                                            ->state(function ($record) {
                                                $step = \App\Modules\Workflows\Models\WorkflowStep::where('project_id', $record->project?->id)
                                                    ->where('step_code', 'DOCUMENT_ADMINISTRATION')->first();
                                                return $step ? \App\Modules\Workflows\Enums\WorkflowStatus::from($step->status)->getLabel() : 'Belum Mulai';
                                            })
                                            ->badge(),
                                            
                                        TextEntry::make('sihalal_status')
                                            ->label('Akun SIHALAL')
                                            ->state(fn ($record) => $record->project?->sihalalCredential ? 'Sudah tersedia' : 'Belum tersedia')
                                            ->badge()
                                            ->color(fn (string $state): string => $state === 'Sudah tersedia' ? 'success' : 'danger'),
                                            
                                        TextEntry::make('entry_pic')
                                            ->label('PIC Entry')
                                            ->state(function ($record) {
                                                $assignment = $record->project?->assignments()
                                                    ->where('assignment_role', \App\Modules\Projects\Enums\AssignmentRole::ENTRY->value)
                                                    ->whereNull('ended_at')->first();
                                                return $assignment?->user?->name ?? 'Belum ditentukan';
                                            }),
                                    ])
                                    ->headerActions([
                                        Action::make('kelola_akun')
                                            ->label('Kelola Akun SIHALAL')
                                            ->icon('heroicon-o-key')
                                            ->visible(fn () => Auth::user()->can('sihalal_credentials.manage'))
                                            ->form(fn ($record) => [
                                                TextInput::make('email')
                                                    ->label('Email SIHALAL')
                                                    ->email()
                                                    ->required()
                                                    ->default($record->project?->sihalalCredential?->email_encrypted),
                                                TextInput::make('password')
                                                    ->label('Password SIHALAL')
                                                    ->password()
                                                    ->autocomplete('new-password')
                                                    ->dehydrated(fn ($state) => filled($state))
                                                    ->required(fn ($record) => !$record->project?->sihalalCredential)
                                                    ->helperText('Kosongkan jika tidak ingin mengubah password lama.'),
                                            ])
                                            ->action(function (array $data, $record) {
                                                if (!$record->project) return;
                                                
                                                $credential = SihalalCredential::firstOrNew(['project_id' => $record->project->id]);
                                                $credential->email_encrypted = $data['email'];
                                                if (isset($data['password'])) {
                                                    $credential->password_encrypted = $data['password'];
                                                }
                                                
                                                if (!$credential->exists) {
                                                    $credential->created_by = Auth::id();
                                                }
                                                $credential->updated_by = Auth::id();
                                                $credential->save();
                                                
                                                Notification::make()
                                                    ->title('Kredensial disimpan')
                                                    ->success()
                                                    ->send();
                                            }),
                                            
                                        Action::make('lihat_kredensial')
                                            ->label('Lihat Kredensial')
                                            ->icon('heroicon-o-eye')
                                            ->visible(fn () => Auth::user()->can('sihalal_credentials.reveal'))
                                            ->requiresConfirmation()
                                            ->modalHeading('Kredensial SIHALAL')
                                            ->modalDescription('Akses ini akan dicatat di log aktivitas.')
                                            ->modalSubmitActionLabel('Tampilkan')
                                            ->modalIcon('heroicon-o-lock-closed')
                                            ->action(function ($record) {
                                                if (!$record->project || !$record->project->sihalalCredential) {
                                                    Notification::make()->title('Akun belum tersedia')->danger()->send();
                                                    return;
                                                }
                                                
                                                $cred = $record->project->sihalalCredential;
                                                $cred->update(['last_used_at' => now()]);
                                                
                                                activity()
                                                    ->performedOn($record->project)
                                                    ->causedBy(Auth::user())
                                                    ->event('sihalal_revealed')
                                                    ->log('Melihat kredensial SIHALAL');
                                                    
                                                // We can show notification with values, or dispatch browser event to show modal.
                                                // Because we don't want to store them, an ephemeral notification is fine.
                                                Notification::make()
                                                    ->title('Kredensial SIHALAL')
                                                    ->body("Email: {$cred->email_encrypted} \nPassword: {$cred->password_encrypted}")
                                                    ->success()
                                                    ->persistent()
                                                    ->send();
                                            }),
                                            
                                        Action::make('serahkan_ke_entry')
                                            ->label('Serahkan ke Entry')
                                            ->icon('heroicon-o-paper-airplane')
                                            ->color('primary')
                                            ->requiresConfirmation()
                                            ->modalDescription('Apakah Anda yakin dokumen sudah lengkap dan akun SIHALAL sudah siap? Pekerjaan akan diserahkan kepada tim Entry.')
                                            ->action(function ($record) {
                                                try {
                                                    app(ProjectHandoverService::class)->handoverToEntry($record->project, Auth::user());
                                                    Notification::make()->title('Berhasil diserahkan ke Entry')->success()->send();
                                                } catch (\Exception $e) {
                                                    Notification::make()->title('Gagal: ' . $e->getMessage())->danger()->send();
                                                }
                                            }),
                                    ])
                                    ->columnSpan(1),
                                
                                Section::make('Progress Entry SIHALAL')
                                    ->schema([
                                        TextEntry::make('entry_progress_status')
                                            ->label('Status Entry Saat Ini')
                                            ->state(function ($record) {
                                                $step = \App\Modules\Workflows\Models\WorkflowStep::where('project_id', $record->project?->id)
                                                    ->where('step_code', 'ENTRY_PROGRESS')->first();
                                                return $step ? \App\Modules\Workflows\Enums\WorkflowStatus::from($step->status)->getLabel() : 'Belum Tersedia';
                                            })
                                            ->badge(),
                                        TextEntry::make('entry_task_status')
                                            ->label('Status Tugas')
                                            ->state(function ($record) {
                                                $task = \App\Modules\Workflows\Models\Task::where('project_id', $record->project?->id)
                                                    ->where('task_type', \App\Modules\Workflows\Enums\TaskType::ENTRY_PROCESS->value)
                                                    ->first();
                                                return $task ? $task->status->name : '-';
                                            })
                                            ->badge(),
                                    ])
                                    ->headerActions([
                                        Action::make('perbarui_status')
                                            ->label('Perbarui Status')
                                            ->icon('heroicon-o-arrow-path')
                                            ->visible(function ($record) {
                                                if (!$record->project) return false;
                                                $task = \App\Modules\Workflows\Models\Task::where('project_id', $record->project->id)
                                                    ->where('task_type', \App\Modules\Workflows\Enums\TaskType::ENTRY_PROCESS->value)
                                                    ->where('assigned_to', Auth::id())
                                                    ->first();
                                                return $task && in_array($task->status, [\App\Modules\Workflows\Enums\TaskStatus::IN_PROGRESS, \App\Modules\Workflows\Enums\TaskStatus::REVISION]);
                                            })
                                            ->form([
                                                \Filament\Forms\Components\Select::make('new_status')
                                                    ->label('Status Baru')
                                                    ->options([
                                                        \App\Modules\Workflows\Enums\WorkflowStatus::ENTRY_NOT_STARTED->value => 'Belum Mulai',
                                                        \App\Modules\Workflows\Enums\WorkflowStatus::WAITING_CLIENT_DOCUMENTS->value => 'Menunggu Dokumen Klien',
                                                        \App\Modules\Workflows\Enums\WorkflowStatus::DOCUMENTS_INCOMPLETE->value => 'Dokumen Belum Lengkap',
                                                        \App\Modules\Workflows\Enums\WorkflowStatus::CREATING_SIHALAL_ACCOUNT->value => 'Pembuatan Akun SIHALAL',
                                                        \App\Modules\Workflows\Enums\WorkflowStatus::PREPARING_SJPH_MANUAL->value => 'Penyusunan Manual SJPH',
                                                        \App\Modules\Workflows\Enums\WorkflowStatus::INPUTTING_MATERIALS_PRODUCTS->value => 'Input Bahan dan Produk',
                                                    ])
                                                    ->required(),
                                                \Filament\Forms\Components\Textarea::make('reason')
                                                    ->label('Alasan (opsional)')
                                                    ->helperText('Isi jika ini merupakan penurunan status / mundur.'),
                                                \Filament\Forms\Components\Textarea::make('note_content')
                                                    ->label('Catatan Pekerjaan (opsional)'),
                                            ])
                                            ->action(function (array $data, $record) {
                                                $task = \App\Modules\Workflows\Models\Task::where('project_id', $record->project->id)
                                                    ->where('task_type', \App\Modules\Workflows\Enums\TaskType::ENTRY_PROCESS->value)
                                                    ->where('assigned_to', Auth::id())
                                                    ->first();
                                                
                                                try {
                                                    app(\App\Modules\Workflows\Services\EntryWorkflowService::class)->updateStatus(
                                                        $task,
                                                        Auth::user(),
                                                        $data['new_status'],
                                                        $data['reason'] ?? null,
                                                        $data['note_content'] ?? null
                                                    );
                                                    Notification::make()->title('Status berhasil diperbarui')->success()->send();
                                                } catch (\Exception $e) {
                                                    Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                                                }
                                            }),
                                            
                                        Action::make('tambah_catatan')
                                            ->label('Tambah Catatan')
                                            ->icon('heroicon-o-document-plus')
                                            ->visible(function ($record) {
                                                if (!$record->project) return false;
                                                $task = \App\Modules\Workflows\Models\Task::where('project_id', $record->project->id)
                                                    ->where('task_type', \App\Modules\Workflows\Enums\TaskType::ENTRY_PROCESS->value)
                                                    ->where('assigned_to', Auth::id())
                                                    ->first();
                                                return $task && in_array($task->status, [\App\Modules\Workflows\Enums\TaskStatus::IN_PROGRESS, \App\Modules\Workflows\Enums\TaskStatus::REVISION, \App\Modules\Workflows\Enums\TaskStatus::WAITING_REVIEW]);
                                            })
                                            ->form([
                                                \Filament\Forms\Components\Select::make('type')
                                                    ->label('Tipe Catatan')
                                                    ->options([
                                                        'WORK_NOTE' => 'Catatan Pekerjaan',
                                                        'OBSTACLE' => 'Kendala',
                                                    ])
                                                    ->required()
                                                    ->default('WORK_NOTE'),
                                                \Filament\Forms\Components\Textarea::make('content')
                                                    ->label('Isi Catatan')
                                                    ->required(),
                                            ])
                                            ->action(function (array $data, $record) {
                                                $task = \App\Modules\Workflows\Models\Task::where('project_id', $record->project->id)
                                                    ->where('task_type', \App\Modules\Workflows\Enums\TaskType::ENTRY_PROCESS->value)
                                                    ->where('assigned_to', Auth::id())
                                                    ->first();
                                                
                                                app(\App\Modules\Workflows\Services\EntryWorkflowService::class)->addNote(
                                                    $task,
                                                    Auth::user(),
                                                    $data['type'],
                                                    $data['content']
                                                );
                                                Notification::make()->title('Catatan ditambahkan')->success()->send();
                                            }),
                                            
                                        Action::make('kelola_checklist')
                                            ->label('Checklist Pekerjaan')
                                            ->icon('heroicon-o-check-circle')
                                            ->visible(function ($record) {
                                                if (!$record->project) return false;
                                                $task = \App\Modules\Workflows\Models\Task::where('project_id', $record->project->id)
                                                    ->where('task_type', \App\Modules\Workflows\Enums\TaskType::ENTRY_PROCESS->value)
                                                    ->where('assigned_to', Auth::id())
                                                    ->first();
                                                return $task && in_array($task->status, [\App\Modules\Workflows\Enums\TaskStatus::IN_PROGRESS, \App\Modules\Workflows\Enums\TaskStatus::REVISION]);
                                            })
                                            ->form(function ($record) {
                                                $task = \App\Modules\Workflows\Models\Task::where('project_id', $record->project->id)
                                                    ->where('task_type', \App\Modules\Workflows\Enums\TaskType::ENTRY_PROCESS->value)
                                                    ->where('assigned_to', Auth::id())
                                                    ->first();
                                                
                                                if (!$task) return [];

                                                $items = $task->checklistItems()->orderBy('sort_order')->get();
                                                
                                                $schema = [];
                                                foreach ($items as $item) {
                                                    $schema[] = \Filament\Forms\Components\Checkbox::make('checklist_' . $item->id)
                                                        ->label($item->label . ($item->is_required ? ' *' : ''))
                                                        ->default($item->is_completed);
                                                }
                                                return $schema;
                                            })
                                            ->action(function (array $data, $record) {
                                                $task = \App\Modules\Workflows\Models\Task::where('project_id', $record->project->id)
                                                    ->where('task_type', \App\Modules\Workflows\Enums\TaskType::ENTRY_PROCESS->value)
                                                    ->where('assigned_to', Auth::id())
                                                    ->first();
                                                
                                                if (!$task) return;

                                                $items = $task->checklistItems;
                                                foreach ($items as $item) {
                                                    $key = 'checklist_' . $item->id;
                                                    if (isset($data[$key])) {
                                                        $wasCompleted = $item->is_completed;
                                                        $isCompleted = (bool) $data[$key];
                                                        
                                                        if ($wasCompleted !== $isCompleted) {
                                                            $item->is_completed = $isCompleted;
                                                            $item->completed_by = $isCompleted ? Auth::id() : null;
                                                            $item->completed_at = $isCompleted ? now() : null;
                                                            $item->save();
                                                        }
                                                    }
                                                }
                                                Notification::make()->title('Checklist diperbarui')->success()->send();
                                            }),
                                            
                                        Action::make('submit_ke_spv')
                                            ->label('Submit ke SPV')
                                            ->icon('heroicon-o-paper-airplane')
                                            ->color('success')
                                            ->requiresConfirmation()
                                            ->modalDescription('Pastikan semua input di SIHALAL sudah sesuai. Aksi ini akan meneruskan ke SPV untuk direview.')
                                            ->visible(function ($record) {
                                                if (!$record->project) return false;
                                                $task = \App\Modules\Workflows\Models\Task::where('project_id', $record->project->id)
                                                    ->where('task_type', \App\Modules\Workflows\Enums\TaskType::ENTRY_PROCESS->value)
                                                    ->where('assigned_to', Auth::id())
                                                    ->first();
                                                return $task && in_array($task->status, [\App\Modules\Workflows\Enums\TaskStatus::IN_PROGRESS, \App\Modules\Workflows\Enums\TaskStatus::REVISION]);
                                            })
                                            ->action(function ($record) {
                                                $task = \App\Modules\Workflows\Models\Task::where('project_id', $record->project->id)
                                                    ->where('task_type', \App\Modules\Workflows\Enums\TaskType::ENTRY_PROCESS->value)
                                                    ->where('assigned_to', Auth::id())
                                                    ->first();
                                                
                                                try {
                                                    app(\App\Modules\Workflows\Services\EntryWorkflowService::class)->submitForReview($task, Auth::user());
                                                    Notification::make()->title('Berhasil disubmit ke SPV')->success()->send();
                                                } catch (\Exception $e) {
                                                    Notification::make()->title('Gagal submit')->body($e->getMessage())->danger()->send();
                                                }
                                            }),
                                            
                                        Action::make('mulai_review')
                                            ->label('Mulai Review')
                                            ->icon('heroicon-o-play')
                                            ->color('primary')
                                            ->visible(function ($record) {
                                                if (!$record->project) return false;
                                                $task = \App\Modules\Workflows\Models\Task::where('project_id', $record->project->id)
                                                    ->where('task_type', \App\Modules\Workflows\Enums\TaskType::SPV_ENTRY_REVIEW->value)
                                                    ->where('assigned_to', Auth::id())
                                                    ->first();
                                                return $task && $task->status === \App\Modules\Workflows\Enums\TaskStatus::TODO;
                                            })
                                            ->action(function ($record) {
                                                $task = \App\Modules\Workflows\Models\Task::where('project_id', $record->project->id)
                                                    ->where('task_type', \App\Modules\Workflows\Enums\TaskType::SPV_ENTRY_REVIEW->value)
                                                    ->where('assigned_to', Auth::id())
                                                    ->first();
                                                try {
                                                    app(\App\Modules\Workflows\Services\SpvEntryWorkflowService::class)->startReview($task, Auth::user());
                                                    Notification::make()->title('Review dimulai')->success()->send();
                                                } catch (\Exception $e) {
                                                    Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                                                }
                                            }),

                                        Action::make('approve_entry')
                                            ->label('Approve Entry')
                                            ->icon('heroicon-o-check-badge')
                                            ->color('success')
                                            ->requiresConfirmation()
                                            ->modalDescription('Apakah Anda yakin hasil Entry SIHALAL sudah benar dan lengkap? Tindakan ini akan menyetujui Entry.')
                                            ->visible(function ($record) {
                                                if (!$record->project) return false;
                                                $task = \App\Modules\Workflows\Models\Task::where('project_id', $record->project->id)
                                                    ->where('task_type', \App\Modules\Workflows\Enums\TaskType::SPV_ENTRY_REVIEW->value)
                                                    ->where('assigned_to', Auth::id())
                                                    ->first();
                                                return $task && $task->status === \App\Modules\Workflows\Enums\TaskStatus::IN_PROGRESS;
                                            })
                                            ->action(function ($record) {
                                                $task = \App\Modules\Workflows\Models\Task::where('project_id', $record->project->id)
                                                    ->where('task_type', \App\Modules\Workflows\Enums\TaskType::SPV_ENTRY_REVIEW->value)
                                                    ->where('assigned_to', Auth::id())
                                                    ->first();
                                                try {
                                                    app(\App\Modules\Workflows\Services\SpvEntryWorkflowService::class)->approve($task, Auth::user());
                                                    Notification::make()->title('Entry Disetujui')->success()->send();
                                                } catch (\Exception $e) {
                                                    Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                                                }
                                            }),

                                        Action::make('minta_revisi')
                                            ->label('Minta Revisi')
                                            ->icon('heroicon-o-arrow-uturn-left')
                                            ->color('danger')
                                            ->visible(function ($record) {
                                                if (!$record->project) return false;
                                                $task = \App\Modules\Workflows\Models\Task::where('project_id', $record->project->id)
                                                    ->where('task_type', \App\Modules\Workflows\Enums\TaskType::SPV_ENTRY_REVIEW->value)
                                                    ->where('assigned_to', Auth::id())
                                                    ->first();
                                                return $task && $task->status === \App\Modules\Workflows\Enums\TaskStatus::IN_PROGRESS;
                                            })
                                            ->form([
                                                \Filament\Forms\Components\Textarea::make('reason')
                                                    ->label('Alasan Revisi')
                                                    ->required()
                                                    ->helperText('Jelaskan bagian mana yang perlu diperbaiki oleh Entry.'),
                                            ])
                                            ->action(function (array $data, $record) {
                                                $task = \App\Modules\Workflows\Models\Task::where('project_id', $record->project->id)
                                                    ->where('task_type', \App\Modules\Workflows\Enums\TaskType::SPV_ENTRY_REVIEW->value)
                                                    ->where('assigned_to', Auth::id())
                                                    ->first();
                                                try {
                                                    app(\App\Modules\Workflows\Services\SpvEntryWorkflowService::class)->requestRevision($task, Auth::user(), $data['reason']);
                                                    Notification::make()->title('Revisi diminta')->success()->send();
                                                } catch (\Exception $e) {
                                                    Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                                                }
                                            }),
                                    ])
                                    ->columnSpan(1),
                                
                                Section::make('Perencanaan Audit')
                                    ->schema([
                                        TextEntry::make('audit_schedule')
                                            ->label('Jadwal Audit')
                                            ->state(function ($record) {
                                                $plan = \App\Modules\Workflows\Models\AuditPlan::where('project_id', $record->project?->id)->first();
                                                if (!$plan || !$plan->scheduled_start_at) return 'Belum dijadwalkan';
                                                
                                                $method = $plan->audit_method?->label() ?? '-';
                                                return $plan->scheduled_start_at->format('d M Y H:i') . ' s/d ' . ($plan->scheduled_end_at ? $plan->scheduled_end_at->format('d M Y H:i') : '-') . " ($method)";
                                            }),
                                        TextEntry::make('audit_status')
                                            ->label('Status Audit')
                                            ->state(function ($record) {
                                                $step = \App\Modules\Workflows\Models\WorkflowStep::where('project_id', $record->project?->id)
                                                    ->where('step_code', 'COMPANION_PROGRESS')->first();
                                                return $step ? \App\Modules\Workflows\Enums\WorkflowStatus::from($step->status)->getLabel() : 'Belum Mulai';
                                            })
                                            ->badge(),
                                        TextEntry::make('primary_auditor')
                                            ->label('Auditor Utama')
                                            ->state(function ($record) {
                                                $assignment = $record->project?->assignments()
                                                    ->where('assignment_role', \App\Modules\Projects\Enums\AssignmentRole::AUDITOR->value)
                                                    ->where('is_primary', true)
                                                    ->whereNull('ended_at')->first();
                                                return $assignment?->user?->name ?? 'Belum ditentukan';
                                            }),
                                    ])
                                    ->headerActions([
                                        Action::make('mulai_perencanaan')
                                            ->label('Mulai Perencanaan')
                                            ->icon('heroicon-o-play')
                                            ->color('primary')
                                            ->visible(function ($record) {
                                                if (!$record->project) return false;
                                                $task = \App\Modules\Workflows\Models\Task::where('project_id', $record->project->id)
                                                    ->where('task_type', \App\Modules\Workflows\Enums\TaskType::AUDIT_PLANNING->value)
                                                    ->where('assigned_to', Auth::id())
                                                    ->first();
                                                return $task && $task->status === \App\Modules\Workflows\Enums\TaskStatus::TODO;
                                            })
                                            ->action(function ($record) {
                                                $task = \App\Modules\Workflows\Models\Task::where('project_id', $record->project->id)
                                                    ->where('task_type', \App\Modules\Workflows\Enums\TaskType::AUDIT_PLANNING->value)
                                                    ->where('assigned_to', Auth::id())
                                                    ->first();
                                                try {
                                                    app(\App\Modules\Workflows\Services\AuditPlanningService::class)->startPlanning($task, Auth::user());
                                                    Notification::make()->title('Perencanaan dimulai')->success()->send();
                                                } catch (\Exception $e) {
                                                    Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                                                }
                                            }),

                                        Action::make('update_draft')
                                            ->label('Update Draft Plan')
                                            ->icon('heroicon-o-pencil-square')
                                            ->visible(function ($record) {
                                                if (!$record->project) return false;
                                                $task = \App\Modules\Workflows\Models\Task::where('project_id', $record->project->id)
                                                    ->where('task_type', \App\Modules\Workflows\Enums\TaskType::AUDIT_PLANNING->value)
                                                    ->where('assigned_to', Auth::id())
                                                    ->first();
                                                return $task && $task->status === \App\Modules\Workflows\Enums\TaskStatus::IN_PROGRESS;
                                            })
                                            ->form(function ($record) {
                                                $plan = \App\Modules\Workflows\Models\AuditPlan::where('project_id', $record->project->id)->first();
                                                return [
                                                    \Filament\Forms\Components\DateTimePicker::make('scheduled_start_at')
                                                        ->label('Mulai')
                                                        ->default($plan?->scheduled_start_at),
                                                    \Filament\Forms\Components\DateTimePicker::make('scheduled_end_at')
                                                        ->label('Selesai')
                                                        ->default($plan?->scheduled_end_at),
                                                    \Filament\Forms\Components\Select::make('timezone')
                                                        ->label('Zona Waktu')
                                                        ->options([
                                                            'Asia/Jakarta' => 'WIB (Asia/Jakarta)',
                                                            'Asia/Makassar' => 'WITA (Asia/Makassar)',
                                                            'Asia/Jayapura' => 'WIT (Asia/Jayapura)',
                                                        ])
                                                        ->default($plan?->timezone ?? 'Asia/Jakarta'),
                                                    \Filament\Forms\Components\Select::make('audit_method')
                                                        ->label('Metode Audit')
                                                        ->options([
                                                            'ONLINE' => 'Online',
                                                            'ONSITE' => 'On-site',
                                                        ])
                                                        ->default($plan?->audit_method?->value)
                                                        ->reactive(),
                                                    \Filament\Forms\Components\TextInput::make('meeting_url')
                                                        ->label('Link Pertemuan')
                                                        ->url()
                                                        ->visible(fn (\Filament\Forms\Get $get) => $get('audit_method') === 'ONLINE')
                                                        ->default($plan?->meeting_url),
                                                    \Filament\Forms\Components\Textarea::make('location')
                                                        ->label('Lokasi Fisik')
                                                        ->visible(fn (\Filament\Forms\Get $get) => $get('audit_method') === 'ONSITE')
                                                        ->default($plan?->location),
                                                    \Filament\Forms\Components\Textarea::make('notes')
                                                        ->label('Catatan Tambahan')
                                                        ->default($plan?->notes),
                                                ];
                                            })
                                            ->action(function (array $data, $record) {
                                                $task = \App\Modules\Workflows\Models\Task::where('project_id', $record->project->id)
                                                    ->where('task_type', \App\Modules\Workflows\Enums\TaskType::AUDIT_PLANNING->value)
                                                    ->where('assigned_to', Auth::id())
                                                    ->first();
                                                try {
                                                    app(\App\Modules\Workflows\Services\AuditPlanningService::class)->saveDraftPlan($task, Auth::user(), $data);
                                                    Notification::make()->title('Draft disimpan')->success()->send();
                                                } catch (\Exception $e) {
                                                    Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                                                }
                                            }),

                                        Action::make('kelola_checklist_audit')
                                            ->label('Persiapan Audit')
                                            ->icon('heroicon-o-clipboard-document-check')
                                            ->visible(function ($record) {
                                                if (!$record->project) return false;
                                                $task = \App\Modules\Workflows\Models\Task::where('project_id', $record->project->id)
                                                    ->where('task_type', \App\Modules\Workflows\Enums\TaskType::AUDIT_PLANNING->value)
                                                    ->where('assigned_to', Auth::id())
                                                    ->first();
                                                return $task && $task->status === \App\Modules\Workflows\Enums\TaskStatus::IN_PROGRESS;
                                            })
                                            ->form(function ($record) {
                                                $task = \App\Modules\Workflows\Models\Task::where('project_id', $record->project->id)
                                                    ->where('task_type', \App\Modules\Workflows\Enums\TaskType::AUDIT_PLANNING->value)
                                                    ->where('assigned_to', Auth::id())
                                                    ->first();
                                                
                                                if (!$task) return [];

                                                $items = $task->checklistItems()->orderBy('sort_order')->get();
                                                
                                                $schema = [];
                                                foreach ($items as $item) {
                                                    $schema[] = \Filament\Forms\Components\Checkbox::make('checklist_' . $item->id)
                                                        ->label($item->label . ($item->is_required ? ' *' : ''))
                                                        ->default($item->is_completed);
                                                }
                                                return empty($schema) ? [\Filament\Forms\Components\Placeholder::make('info')->content('Draft rencana belum diisi atau metode audit belum dipilih.')] : $schema;
                                            })
                                            ->action(function (array $data, $record) {
                                                $task = \App\Modules\Workflows\Models\Task::where('project_id', $record->project->id)
                                                    ->where('task_type', \App\Modules\Workflows\Enums\TaskType::AUDIT_PLANNING->value)
                                                    ->where('assigned_to', Auth::id())
                                                    ->first();
                                                
                                                if (!$task) return;

                                                $items = $task->checklistItems;
                                                foreach ($items as $item) {
                                                    $key = 'checklist_' . $item->id;
                                                    if (isset($data[$key])) {
                                                        $wasCompleted = $item->is_completed;
                                                        $isCompleted = (bool) $data[$key];
                                                        
                                                        if ($wasCompleted !== $isCompleted) {
                                                            $item->is_completed = $isCompleted;
                                                            $item->completed_by = $isCompleted ? Auth::id() : null;
                                                            $item->completed_at = $isCompleted ? now() : null;
                                                            $item->save();
                                                        }
                                                    }
                                                }
                                                Notification::make()->title('Persiapan diperbarui')->success()->send();
                                            }),

                                        Action::make('konfirmasi_jadwal')
                                            ->label('Konfirmasi Jadwal')
                                            ->icon('heroicon-o-check-badge')
                                            ->color('success')
                                            ->requiresConfirmation()
                                            ->modalDescription('Apakah Anda yakin jadwal dan persiapan audit sudah final? Tindakan ini akan mengunci rencana audit dan menginformasikan auditor.')
                                            ->visible(function ($record) {
                                                if (!$record->project) return false;
                                                $task = \App\Modules\Workflows\Models\Task::where('project_id', $record->project->id)
                                                    ->where('task_type', \App\Modules\Workflows\Enums\TaskType::AUDIT_PLANNING->value)
                                                    ->where('assigned_to', Auth::id())
                                                    ->first();
                                                return $task && $task->status === \App\Modules\Workflows\Enums\TaskStatus::IN_PROGRESS;
                                            })
                                            ->action(function ($record) {
                                                $task = \App\Modules\Workflows\Models\Task::where('project_id', $record->project->id)
                                                    ->where('task_type', \App\Modules\Workflows\Enums\TaskType::AUDIT_PLANNING->value)
                                                    ->where('assigned_to', Auth::id())
                                                    ->first();
                                                try {
                                                    app(\App\Modules\Workflows\Services\AuditPlanningService::class)->confirmSchedule($task, Auth::user());
                                                    Notification::make()->title('Jadwal Dikonfirmasi')->success()->send();
                                                } catch (\Exception $e) {
                                                    Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                                                }
                                            }),

                                        Action::make('reschedule')
                                            ->label('Reschedule')
                                            ->icon('heroicon-o-clock')
                                            ->color('warning')
                                            ->visible(function ($record) {
                                                if (!$record->project) return false;
                                                $tracker = \App\Modules\Workflows\Models\WorkflowStep::where('project_id', $record->project->id)
                                                    ->where('step_code', 'COMPANION_PROGRESS')->first();
                                                
                                                if (!$tracker || !in_array($tracker->status->value, [\App\Modules\Workflows\Enums\WorkflowStatus::AUDIT_SCHEDULED->value, \App\Modules\Workflows\Enums\WorkflowStatus::AUDIT_PREPARATION->value, \App\Modules\Workflows\Enums\WorkflowStatus::WAITING_AUDIT_SCHEDULE->value])) {
                                                    return false;
                                                }
                                                
                                                // Only Pendamping can reschedule
                                                $assignment = $record->project->assignments()
                                                    ->where('assignment_role', \App\Modules\Projects\Enums\AssignmentRole::PENDAMPING_AUDITOR->value)
                                                    ->where('user_id', Auth::id())
                                                    ->whereNull('ended_at')
                                                    ->first();
                                                    
                                                return $assignment !== null;
                                            })
                                            ->form(function ($record) {
                                                $plan = \App\Modules\Workflows\Models\AuditPlan::where('project_id', $record->project->id)->first();
                                                return [
                                                    \Filament\Forms\Components\DateTimePicker::make('scheduled_start_at')
                                                        ->label('Mulai')
                                                        ->required()
                                                        ->default($plan?->scheduled_start_at),
                                                    \Filament\Forms\Components\DateTimePicker::make('scheduled_end_at')
                                                        ->label('Selesai')
                                                        ->required()
                                                        ->default($plan?->scheduled_end_at),
                                                    \Filament\Forms\Components\Select::make('timezone')
                                                        ->label('Zona Waktu')
                                                        ->options([
                                                            'Asia/Jakarta' => 'WIB (Asia/Jakarta)',
                                                            'Asia/Makassar' => 'WITA (Asia/Makassar)',
                                                            'Asia/Jayapura' => 'WIT (Asia/Jayapura)',
                                                        ])
                                                        ->required()
                                                        ->default($plan?->timezone ?? 'Asia/Jakarta'),
                                                    \Filament\Forms\Components\Select::make('audit_method')
                                                        ->label('Metode Audit')
                                                        ->options([
                                                            'ONLINE' => 'Online',
                                                            'ONSITE' => 'On-site',
                                                        ])
                                                        ->required()
                                                        ->default($plan?->audit_method?->value)
                                                        ->reactive(),
                                                    \Filament\Forms\Components\TextInput::make('meeting_url')
                                                        ->label('Link Pertemuan')
                                                        ->url()
                                                        ->visible(fn (\Filament\Forms\Get $get) => $get('audit_method') === 'ONLINE')
                                                        ->default($plan?->meeting_url),
                                                    \Filament\Forms\Components\Textarea::make('location')
                                                        ->label('Lokasi Fisik')
                                                        ->visible(fn (\Filament\Forms\Get $get) => $get('audit_method') === 'ONSITE')
                                                        ->default($plan?->location),
                                                    \Filament\Forms\Components\Textarea::make('reason')
                                                        ->label('Alasan Perubahan')
                                                        ->required(),
                                                ];
                                            })
                                            ->action(function (array $data, $record) {
                                                try {
                                                    app(\App\Modules\Workflows\Services\AuditPlanningService::class)->reschedule($record->project, Auth::user(), $data, $data['reason']);
                                                    Notification::make()->title('Jadwal diubah')->success()->send();
                                                } catch (\Exception $e) {
                                                    Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                                                }
                                            }),
                                            
                                        Action::make('kelola_auditor')
                                            ->label('Kelola Auditor')
                                            ->icon('heroicon-o-users')
                                            ->visible(function ($record) {
                                                if (!$record->project) return false;
                                                $assignment = $record->project->assignments()
                                                    ->where('assignment_role', \App\Modules\Projects\Enums\AssignmentRole::PENDAMPING_AUDITOR->value)
                                                    ->where('user_id', Auth::id())
                                                    ->whereNull('ended_at')
                                                    ->first();
                                                return $assignment !== null;
                                            })
                                            ->form([
                                                \Filament\Forms\Components\Select::make('auditor_id')
                                                    ->label('Auditor')
                                                    ->options(\App\Models\User::where('status', 'ACTIVE')->get()->filter(fn($u) => $u->hasRole('Auditor'))->pluck('name', 'id'))
                                                    ->searchable()
                                                    ->required(),
                                                \Filament\Forms\Components\Toggle::make('is_primary')
                                                    ->label('Jadikan Auditor Utama'),
                                            ])
                                            ->action(function (array $data, $record) {
                                                try {
                                                    $auditorUser = \App\Models\User::find($data['auditor_id']);
                                                    app(\App\Modules\Projects\Services\AssignmentService::class)->assignAuditor($record->project, $auditorUser, $data['is_primary']);
                                                    Notification::make()->title('Auditor ditetapkan')->success()->send();
                                                } catch (\Exception $e) {
                                                    Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                                                }
                                            }),
                                    ])
                                    ->columnSpan(1),
                                    
                                Section::make('Pelaksanaan Audit')
                                    ->schema([
                                        TextEntry::make('execution_status')
                                            ->label('Status Pendampingan')
                                            ->state(function ($record) {
                                                $step = \App\Modules\Workflows\Models\WorkflowStep::where('project_id', $record->project?->id)
                                                    ->where('step_code', 'COMPANION_PROGRESS')->first();
                                                return $step ? \App\Modules\Workflows\Enums\WorkflowStatus::from($step->status)->getLabel() : 'Belum Mulai';
                                            })
                                            ->badge(),
                                        TextEntry::make('execution_summary')
                                            ->label('Ringkasan Pelaksanaan')
                                            ->state(function ($record) {
                                                $execution = \App\Modules\Workflows\Models\AuditExecution::where('project_id', $record->project?->id)->first();
                                                return $execution?->summary ?? 'Belum ada ringkasan';
                                            }),
                                        TextEntry::make('execution_findings')
                                            ->label('Status Temuan')
                                            ->state(function ($record) {
                                                $execution = \App\Modules\Workflows\Models\AuditExecution::where('project_id', $record->project?->id)->first();
                                                if (!$execution) return '-';
                                                if ($execution->has_findings === null) return 'Belum dikonfirmasi';
                                                return $execution->has_findings ? 'Ada Temuan' : 'Tidak Ada Temuan';
                                            })
                                            ->badge()
                                            ->color(fn (string $state): string => match ($state) {
                                                'Ada Temuan' => 'warning',
                                                'Tidak Ada Temuan' => 'success',
                                                default => 'gray',
                                            }),
                                    ])
                                    ->headerActions([
                                        Action::make('mulai_pelaksanaan')
                                            ->label('Mulai Pelaksanaan')
                                            ->icon('heroicon-o-play')
                                            ->color('primary')
                                            ->visible(function ($record) {
                                                if (!$record->project) return false;
                                                $task = \App\Modules\Workflows\Models\Task::where('project_id', $record->project->id)
                                                    ->where('task_type', \App\Modules\Workflows\Enums\TaskType::AUDIT_EXECUTION->value)
                                                    ->where('assigned_to', Auth::id())
                                                    ->first();
                                                return $task && $task->status === \App\Modules\Workflows\Enums\TaskStatus::TODO;
                                            })
                                            ->action(function ($record) {
                                                $task = \App\Modules\Workflows\Models\Task::where('project_id', $record->project->id)
                                                    ->where('task_type', \App\Modules\Workflows\Enums\TaskType::AUDIT_EXECUTION->value)
                                                    ->where('assigned_to', Auth::id())
                                                    ->first();
                                                try {
                                                    app(\App\Modules\Workflows\Services\AuditExecutionService::class)->startExecution($task, Auth::user());
                                                    Notification::make()->title('Pelaksanaan audit dimulai')->success()->send();
                                                } catch (\Exception $e) {
                                                    Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                                                }
                                            }),

                                        Action::make('perbarui_status_pendamping')
                                            ->label('Update Status Pendamping')
                                            ->icon('heroicon-o-arrow-path')
                                            ->visible(function ($record) {
                                                if (!$record->project) return false;
                                                $task = \App\Modules\Workflows\Models\Task::where('project_id', $record->project->id)
                                                    ->where('task_type', \App\Modules\Workflows\Enums\TaskType::AUDIT_EXECUTION->value)
                                                    ->where('assigned_to', Auth::id())
                                                    ->first();
                                                return $task && in_array($task->status, [\App\Modules\Workflows\Enums\TaskStatus::IN_PROGRESS, \App\Modules\Workflows\Enums\TaskStatus::REVISION]);
                                            })
                                            ->form([
                                                \Filament\Forms\Components\Select::make('new_status')
                                                    ->label('Status Baru')
                                                    ->options([
                                                        \App\Modules\Workflows\Enums\WorkflowStatus::AUDIT_IN_PROGRESS->value => 'Audit Sedang Berjalan',
                                                        \App\Modules\Workflows\Enums\WorkflowStatus::FIELD_EVIDENCE_INCOMPLETE->value => 'Bukti Lapangan Belum Lengkap',
                                                        \App\Modules\Workflows\Enums\WorkflowStatus::AUDIT_COMPLETED->value => 'Audit Selesai',
                                                        \App\Modules\Workflows\Enums\WorkflowStatus::WAITING_CLIENT_CORRECTION->value => 'Menunggu Perbaikan Klien',
                                                    ])
                                                    ->required(),
                                                \Filament\Forms\Components\Textarea::make('reason')
                                                    ->label('Alasan (opsional)')
                                                    ->helperText('Wajib diisi jika Anda menurunkan status (mundur).'),
                                            ])
                                            ->action(function (array $data, $record) {
                                                try {
                                                    $statusEnum = \App\Modules\Workflows\Enums\WorkflowStatus::from($data['new_status']);
                                                    app(\App\Modules\Workflows\Services\AuditExecutionService::class)->updateCompanionStatus(
                                                        $record->project,
                                                        Auth::user(),
                                                        $statusEnum,
                                                        $data['reason'] ?? null
                                                    );
                                                    Notification::make()->title('Status berhasil diperbarui')->success()->send();
                                                } catch (\Exception $e) {
                                                    Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                                                }
                                            }),

                                        Action::make('kelola_checklist_eksekusi')
                                            ->label('Checklist Pelaksanaan')
                                            ->icon('heroicon-o-check-circle')
                                            ->visible(function ($record) {
                                                if (!$record->project) return false;
                                                $task = \App\Modules\Workflows\Models\Task::where('project_id', $record->project->id)
                                                    ->where('task_type', \App\Modules\Workflows\Enums\TaskType::AUDIT_EXECUTION->value)
                                                    ->where('assigned_to', Auth::id())
                                                    ->first();
                                                return $task && in_array($task->status, [\App\Modules\Workflows\Enums\TaskStatus::IN_PROGRESS, \App\Modules\Workflows\Enums\TaskStatus::REVISION]);
                                            })
                                            ->form(function ($record) {
                                                $task = \App\Modules\Workflows\Models\Task::where('project_id', $record->project->id)
                                                    ->where('task_type', \App\Modules\Workflows\Enums\TaskType::AUDIT_EXECUTION->value)
                                                    ->where('assigned_to', Auth::id())
                                                    ->first();
                                                
                                                if (!$task) return [];
                                                $items = $task->checklistItems()->orderBy('sort_order')->get();
                                                $schema = [];
                                                foreach ($items as $item) {
                                                    $schema[] = \Filament\Forms\Components\Checkbox::make('checklist_' . $item->id)
                                                        ->label($item->label . ($item->is_required ? ' *' : ''))
                                                        ->default($item->is_completed);
                                                }
                                                return $schema;
                                            })
                                            ->action(function (array $data, $record) {
                                                $task = \App\Modules\Workflows\Models\Task::where('project_id', $record->project->id)
                                                    ->where('task_type', \App\Modules\Workflows\Enums\TaskType::AUDIT_EXECUTION->value)
                                                    ->where('assigned_to', Auth::id())
                                                    ->first();
                                                if (!$task) return;
                                                $items = $task->checklistItems;
                                                foreach ($items as $item) {
                                                    $key = 'checklist_' . $item->id;
                                                    if (isset($data[$key])) {
                                                        $wasCompleted = $item->is_completed;
                                                        $isCompleted = (bool) $data[$key];
                                                        if ($wasCompleted !== $isCompleted) {
                                                            $item->is_completed = $isCompleted;
                                                            $item->completed_by = $isCompleted ? Auth::id() : null;
                                                            $item->completed_at = $isCompleted ? now() : null;
                                                            $item->save();
                                                        }
                                                    }
                                                }
                                                Notification::make()->title('Checklist eksekusi diperbarui')->success()->send();
                                            }),

                                        Action::make('tambah_temuan')
                                            ->label('Tambah Temuan')
                                            ->icon('heroicon-o-plus-circle')
                                            ->color('warning')
                                            ->visible(function ($record) {
                                                if (!$record->project) return false;
                                                $task = \App\Modules\Workflows\Models\Task::where('project_id', $record->project->id)
                                                    ->where('task_type', \App\Modules\Workflows\Enums\TaskType::AUDIT_EXECUTION->value)
                                                    ->where('assigned_to', Auth::id())
                                                    ->first();
                                                return $task && in_array($task->status, [\App\Modules\Workflows\Enums\TaskStatus::IN_PROGRESS, \App\Modules\Workflows\Enums\TaskStatus::REVISION]);
                                            })
                                            ->form([
                                                \Filament\Forms\Components\Textarea::make('description')
                                                    ->label('Deskripsi Temuan')
                                                    ->required(),
                                                \Filament\Forms\Components\Toggle::make('evidence_required')
                                                    ->label('Wajib Melampirkan Bukti?')
                                                    ->default(true),
                                            ])
                                            ->action(function (array $data, $record) {
                                                $task = \App\Modules\Workflows\Models\Task::where('project_id', $record->project->id)
                                                    ->where('task_type', \App\Modules\Workflows\Enums\TaskType::AUDIT_EXECUTION->value)
                                                    ->where('assigned_to', Auth::id())
                                                    ->first();
                                                try {
                                                    app(\App\Modules\Workflows\Services\AuditExecutionService::class)->addFinding($task, Auth::user(), $data);
                                                    Notification::make()->title('Temuan ditambahkan')->success()->send();
                                                } catch (\Exception $e) {
                                                    Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                                                }
                                            }),

                                        Action::make('batalkan_temuan')
                                            ->label('Batalkan Temuan')
                                            ->icon('heroicon-o-trash')
                                            ->color('danger')
                                            ->visible(function ($record) {
                                                if (!$record->project) return false;
                                                $task = \App\Modules\Workflows\Models\Task::where('project_id', $record->project->id)
                                                    ->where('task_type', \App\Modules\Workflows\Enums\TaskType::AUDIT_EXECUTION->value)
                                                    ->where('assigned_to', Auth::id())
                                                    ->first();
                                                return $task && in_array($task->status, [\App\Modules\Workflows\Enums\TaskStatus::IN_PROGRESS, \App\Modules\Workflows\Enums\TaskStatus::REVISION]);
                                            })
                                            ->form(function ($record) {
                                                $execution = \App\Modules\Workflows\Models\AuditExecution::where('project_id', $record->project->id)->first();
                                                if (!$execution) return [];
                                                
                                                $findings = \App\Modules\Workflows\Models\AuditFinding::where('audit_execution_id', $execution->id)
                                                    ->where('status', \App\Modules\Workflows\Enums\AuditFindingStatus::OPEN->value)
                                                    ->get()
                                                    ->mapWithKeys(fn ($item) => [$item->id => $item->finding_number . ' - ' . \Illuminate\Support\Str::limit($item->description, 50)]);
                                                    
                                                return [
                                                    \Filament\Forms\Components\Select::make('finding_id')
                                                        ->label('Pilih Temuan')
                                                        ->options($findings)
                                                        ->required(),
                                                    \Filament\Forms\Components\Textarea::make('reason')
                                                        ->label('Alasan Pembatalan')
                                                        ->required(),
                                                ];
                                            })
                                            ->action(function (array $data, $record) {
                                                $finding = \App\Modules\Workflows\Models\AuditFinding::find($data['finding_id']);
                                                if (!$finding) return;
                                                
                                                try {
                                                    app(\App\Modules\Workflows\Services\AuditExecutionService::class)->voidFinding($finding, Auth::user(), $data['reason']);
                                                    Notification::make()->title('Temuan dibatalkan')->success()->send();
                                                } catch (\Exception $e) {
                                                    Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                                                }
                                            }),

                                        Action::make('upload_bukti_temuan')
                                            ->label('Upload Bukti Temuan')
                                            ->icon('heroicon-o-arrow-up-tray')
                                            ->color('info')
                                            ->visible(function ($record) {
                                                if (!$record->project) return false;
                                                $task = \App\Modules\Workflows\Models\Task::where('project_id', $record->project->id)
                                                    ->where('task_type', \App\Modules\Workflows\Enums\TaskType::AUDIT_EXECUTION->value)
                                                    ->where('assigned_to', Auth::id())
                                                    ->first();
                                                return $task && in_array($task->status, [\App\Modules\Workflows\Enums\TaskStatus::IN_PROGRESS, \App\Modules\Workflows\Enums\TaskStatus::REVISION]);
                                            })
                                            ->form(function ($record) {
                                                $execution = \App\Modules\Workflows\Models\AuditExecution::where('project_id', $record->project->id)->first();
                                                if (!$execution) return [];
                                                
                                                $findings = \App\Modules\Workflows\Models\AuditFinding::where('audit_execution_id', $execution->id)
                                                    ->where('status', \App\Modules\Workflows\Enums\AuditFindingStatus::OPEN->value)
                                                    ->where('evidence_required', true)
                                                    ->get()
                                                    ->mapWithKeys(fn ($item) => [$item->id => $item->finding_number . ' - ' . \Illuminate\Support\Str::limit($item->description, 50)]);
                                                    
                                                return [
                                                    \Filament\Forms\Components\Select::make('finding_id')
                                                        ->label('Pilih Temuan')
                                                        ->options($findings)
                                                        ->required(),
                                                    \Filament\Forms\Components\FileUpload::make('evidence')
                                                        ->label('Bukti Temuan')
                                                        ->required()
                                                        ->maxSize(10240)
                                                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png']),
                                                ];
                                            })
                                            ->action(function (array $data, $record) {
                                                $finding = \App\Modules\Workflows\Models\AuditFinding::find($data['finding_id']);
                                                if (!$finding) return;
                                                
                                                try {
                                                    $file = is_array($data['evidence']) ? array_values($data['evidence'])[0] : $data['evidence'];
                                                    $fileUrl = storage_path('app/public/' . $file); 
                                                    
                                                    app(\App\Modules\Workflows\Services\AuditExecutionService::class)->attachFindingEvidence($finding, Auth::user(), $fileUrl);
                                                    Notification::make()->title('Bukti temuan diunggah')->success()->send();
                                                } catch (\Exception $e) {
                                                    Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                                                }
                                            }),

                                        Action::make('serahkan_ke_auditor')
                                            ->label('Serahkan ke Auditor')
                                            ->icon('heroicon-o-paper-airplane')
                                            ->color('success')
                                            ->requiresConfirmation()
                                            ->modalDescription('Apakah Anda yakin eksekusi audit telah selesai? Hasil akan diserahkan ke Auditor untuk di-review.')
                                            ->visible(function ($record) {
                                                if (!$record->project) return false;
                                                $task = \App\Modules\Workflows\Models\Task::where('project_id', $record->project->id)
                                                    ->where('task_type', \App\Modules\Workflows\Enums\TaskType::AUDIT_EXECUTION->value)
                                                    ->where('assigned_to', Auth::id())
                                                    ->first();
                                                return $task && in_array($task->status, [\App\Modules\Workflows\Enums\TaskStatus::IN_PROGRESS, \App\Modules\Workflows\Enums\TaskStatus::REVISION]);
                                            })
                                            ->form(function ($record) {
                                                $execution = \App\Modules\Workflows\Models\AuditExecution::where('project_id', $record->project->id)->first();
                                                return [
                                                    \Filament\Forms\Components\Textarea::make('summary')
                                                        ->label('Ringkasan Audit')
                                                        ->required()
                                                        ->default($execution?->summary),
                                                    \Filament\Forms\Components\Toggle::make('has_findings')
                                                        ->label('Terdapat Temuan?')
                                                        ->required()
                                                        ->default($execution?->has_findings ?? false),
                                                ];
                                            })
                                            ->action(function (array $data, $record) {
                                                $task = \App\Modules\Workflows\Models\Task::where('project_id', $record->project->id)
                                                    ->where('task_type', \App\Modules\Workflows\Enums\TaskType::AUDIT_EXECUTION->value)
                                                    ->where('assigned_to', Auth::id())
                                                    ->first();
                                                try {
                                                    app(\App\Modules\Workflows\Services\AuditExecutionService::class)->submitToAuditor($task, Auth::user(), $data);
                                                } catch (\Exception $e) {
                                                    Notification::make()->title('Gagal submit')->body($e->getMessage())->danger()->send();
                                                }
                                            }),
                                    ])
                                    ->columnSpan(1),
                            ]),
                        ]),

                    \Filament\Infolists\Components\Section::make('Review Auditor')
                        ->description('Review hasil eksekusi audit dan berikan keputusan.')
                        ->schema([
                            \Filament\Infolists\Components\Actions::make([
                                \Filament\Infolists\Components\Actions\Action::make('mulai_review')
                                    ->label('Mulai Review')
                                    ->icon('heroicon-o-play')
                                    ->color('primary')
                                    ->visible(function ($record) {
                                        if (!$record->project) return false;
                                        $task = \App\Modules\Workflows\Models\Task::where('project_id', $record->project->id)
                                            ->where('task_type', \App\Modules\Workflows\Enums\TaskType::AUDITOR_REVIEW->value)
                                            ->where('assigned_to', \Illuminate\Support\Facades\Auth::id())
                                            ->first();
                                        return $task && $task->status === \App\Modules\Workflows\Enums\TaskStatus::TODO;
                                    })
                                    ->action(function ($record) {
                                        $task = \App\Modules\Workflows\Models\Task::where('project_id', $record->project->id)
                                            ->where('task_type', \App\Modules\Workflows\Enums\TaskType::AUDITOR_REVIEW->value)
                                            ->where('assigned_to', \Illuminate\Support\Facades\Auth::id())
                                            ->first();
                                        try {
                                            app(\App\Modules\Workflows\Services\AuditorReviewService::class)->startReview($task, \Illuminate\Support\Facades\Auth::user());
                                            \Filament\Notifications\Notification::make()->title('Review dimulai')->success()->send();
                                        } catch (\Exception $e) {
                                            \Filament\Notifications\Notification::make()->title('Gagal memulai')->body($e->getMessage())->danger()->send();
                                        }
                                    }),

                                \Filament\Infolists\Components\Actions\Action::make('update_status_auditor')
                                    ->label('Update Status Auditor')
                                    ->icon('heroicon-o-arrow-path')
                                    ->color('info')
                                    ->visible(function ($record) {
                                        if (!$record->project) return false;
                                        $tracker = \App\Modules\Workflows\Models\WorkflowStep::where('project_id', $record->project->id)
                                            ->where('step_code', 'AUDITOR_REVIEW')
                                            ->first();
                                        if (!$tracker) return false;
                                        
                                        $assignment = \App\Modules\Projects\Models\ProjectAssignment::where('project_id', $record->project->id)
                                            ->where('assignment_role', \App\Modules\Projects\Enums\AssignmentRole::AUDITOR->value)
                                            ->where('user_id', \Illuminate\Support\Facades\Auth::id())
                                            ->whereNull('ended_at')
                                            ->first();
                                            
                                        return $assignment && !in_array($tracker->status, [
                                            \App\Modules\Workflows\Enums\WorkflowStatus::AUDIT_REPORT_COMPLETED,
                                            \App\Modules\Workflows\Enums\WorkflowStatus::WAITING_FATWA_SESSION,
                                            \App\Modules\Workflows\Enums\WorkflowStatus::FATWA_SESSION_COMPLETED,
                                            \App\Modules\Workflows\Enums\WorkflowStatus::WAITING_BPJPH_ISSUANCE,
                                            \App\Modules\Workflows\Enums\WorkflowStatus::HALAL_CERTIFICATE_ISSUED,
                                        ]);
                                    })
                                    ->form(function ($record) {
                                        $options = [
                                            \App\Modules\Workflows\Enums\WorkflowStatus::AUDITOR_NOT_PROCESSED->value => \App\Modules\Workflows\Enums\WorkflowStatus::AUDITOR_NOT_PROCESSED->getLabel(),
                                            \App\Modules\Workflows\Enums\WorkflowStatus::DOCUMENT_REVIEW->value => \App\Modules\Workflows\Enums\WorkflowStatus::DOCUMENT_REVIEW->getLabel(),
                                            \App\Modules\Workflows\Enums\WorkflowStatus::WAITING_FIELD_AUDIT->value => \App\Modules\Workflows\Enums\WorkflowStatus::WAITING_FIELD_AUDIT->getLabel(),
                                            \App\Modules\Workflows\Enums\WorkflowStatus::FIELD_AUDIT_COMPLETED->value => \App\Modules\Workflows\Enums\WorkflowStatus::FIELD_AUDIT_COMPLETED->getLabel(),
                                            \App\Modules\Workflows\Enums\WorkflowStatus::NONCONFORMITY_FOUND->value => \App\Modules\Workflows\Enums\WorkflowStatus::NONCONFORMITY_FOUND->getLabel(),
                                            \App\Modules\Workflows\Enums\WorkflowStatus::WAITING_CORRECTIVE_EVIDENCE->value => \App\Modules\Workflows\Enums\WorkflowStatus::WAITING_CORRECTIVE_EVIDENCE->getLabel(),
                                            \App\Modules\Workflows\Enums\WorkflowStatus::CORRECTION_ACCEPTED->value => \App\Modules\Workflows\Enums\WorkflowStatus::CORRECTION_ACCEPTED->getLabel(),
                                        ];
                                        return [
                                            \Filament\Forms\Components\Select::make('status')
                                                ->label('Status Auditor')
                                                ->options($options)
                                                ->required(),
                                            \Filament\Forms\Components\Textarea::make('reason')
                                                ->label('Alasan (Wajib jika turun status)')
                                                ->nullable(),
                                        ];
                                    })
                                    ->action(function (array $data, $record) {
                                        try {
                                            $status = \App\Modules\Workflows\Enums\WorkflowStatus::from($data['status']);
                                            app(\App\Modules\Workflows\Services\AuditorReviewService::class)->updateAuditorStatus($record->project, \Illuminate\Support\Facades\Auth::user(), $status, $data['reason']);
                                            \Filament\Notifications\Notification::make()->title('Status diperbarui')->success()->send();
                                        } catch (\Exception $e) {
                                            \Filament\Notifications\Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                                        }
                                    }),

                                \Filament\Infolists\Components\Actions\Action::make('review_temuan')
                                    ->label('Review Temuan')
                                    ->icon('heroicon-o-magnifying-glass')
                                    ->color('warning')
                                    ->visible(function ($record) {
                                        if (!$record->project) return false;
                                        $task = \App\Modules\Workflows\Models\Task::where('project_id', $record->project->id)
                                            ->where('task_type', \App\Modules\Workflows\Enums\TaskType::AUDITOR_REVIEW->value)
                                            ->where('assigned_to', \Illuminate\Support\Facades\Auth::id())
                                            ->first();
                                        return $task && $task->status === \App\Modules\Workflows\Enums\TaskStatus::IN_PROGRESS;
                                    })
                                    ->form(function ($record) {
                                        $execution = \App\Modules\Workflows\Models\AuditExecution::where('project_id', $record->project->id)->first();
                                        if (!$execution) return [];
                                        
                                        $findings = \App\Modules\Workflows\Models\AuditFinding::where('audit_execution_id', $execution->id)
                                            ->whereIn('status', [
                                                \App\Modules\Workflows\Enums\AuditFindingStatus::OPEN->value,
                                                \App\Modules\Workflows\Enums\AuditFindingStatus::EVIDENCE_SUBMITTED->value,
                                            ])
                                            ->get()
                                            ->mapWithKeys(fn ($item) => [$item->id => $item->finding_number . ' - ' . \Illuminate\Support\Str::limit($item->description, 50) . ' (' . $item->status->getLabel() . ')']);
                                            
                                        return [
                                            \Filament\Forms\Components\Select::make('finding_id')
                                                ->label('Pilih Temuan')
                                                ->options($findings)
                                                ->required(),
                                            \Filament\Forms\Components\Select::make('status')
                                                ->label('Keputusan')
                                                ->options([
                                                    \App\Modules\Workflows\Enums\AuditFindingStatus::ACCEPTED->value => 'Terima Bukti/Temuan (ACCEPTED)',
                                                    \App\Modules\Workflows\Enums\AuditFindingStatus::CORRECTION_REQUIRED->value => 'Minta Koreksi (CORRECTION_REQUIRED)',
                                                ])
                                                ->required()
                                                ->reactive(),
                                            \Filament\Forms\Components\Textarea::make('resolution_notes')
                                                ->label('Instruksi Koreksi')
                                                ->required(fn (\Filament\Forms\Get $get) => $get('status') === \App\Modules\Workflows\Enums\AuditFindingStatus::CORRECTION_REQUIRED->value)
                                                ->visible(fn (\Filament\Forms\Get $get) => $get('status') === \App\Modules\Workflows\Enums\AuditFindingStatus::CORRECTION_REQUIRED->value),
                                            \Filament\Forms\Components\Select::make('correction_owner')
                                                ->label('Pihak Bertanggung Jawab')
                                                ->options([
                                                    \App\Modules\Workflows\Enums\AuditFindingCorrectionOwner::CLIENT->value => 'Klien',
                                                    \App\Modules\Workflows\Enums\AuditFindingCorrectionOwner::PHC_INTERNAL->value => 'PHC (Internal)',
                                                ])
                                                ->required(fn (\Filament\Forms\Get $get) => $get('status') === \App\Modules\Workflows\Enums\AuditFindingStatus::CORRECTION_REQUIRED->value)
                                                ->visible(fn (\Filament\Forms\Get $get) => $get('status') === \App\Modules\Workflows\Enums\AuditFindingStatus::CORRECTION_REQUIRED->value),
                                            \Filament\Forms\Components\Toggle::make('evidence_required')
                                                ->label('Bukti Perbaikan Diwajibkan?')
                                                ->visible(fn (\Filament\Forms\Get $get) => $get('status') === \App\Modules\Workflows\Enums\AuditFindingStatus::CORRECTION_REQUIRED->value)
                                                ->default(true),
                                        ];
                                    })
                                    ->action(function (array $data, $record) {
                                        $finding = \App\Modules\Workflows\Models\AuditFinding::find($data['finding_id']);
                                        if (!$finding) return;
                                        
                                        try {
                                            $status = \App\Modules\Workflows\Enums\AuditFindingStatus::from($data['status']);
                                            app(\App\Modules\Workflows\Services\AuditorReviewService::class)->reviewFinding($finding, \Illuminate\Support\Facades\Auth::user(), $status, $data);
                                            \Filament\Notifications\Notification::make()->title('Temuan di-review')->success()->send();
                                        } catch (\Exception $e) {
                                            \Filament\Notifications\Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                                        }
                                    }),

                                \Filament\Infolists\Components\Actions\Action::make('kembalikan_untuk_revisi')
                                    ->label('Kembalikan untuk Revisi')
                                    ->icon('heroicon-o-x-circle')
                                    ->color('danger')
                                    ->requiresConfirmation()
                                    ->visible(function ($record) {
                                        if (!$record->project) return false;
                                        $task = \App\Modules\Workflows\Models\Task::where('project_id', $record->project->id)
                                            ->where('task_type', \App\Modules\Workflows\Enums\TaskType::AUDITOR_REVIEW->value)
                                            ->where('assigned_to', \Illuminate\Support\Facades\Auth::id())
                                            ->first();
                                        return $task && $task->status === \App\Modules\Workflows\Enums\TaskStatus::IN_PROGRESS;
                                    })
                                    ->form([
                                        \Filament\Forms\Components\Textarea::make('reason')
                                            ->label('Alasan Revisi')
                                            ->nullable(),
                                    ])
                                    ->action(function (array $data, $record) {
                                        $task = \App\Modules\Workflows\Models\Task::where('project_id', $record->project->id)
                                            ->where('task_type', \App\Modules\Workflows\Enums\TaskType::AUDITOR_REVIEW->value)
                                            ->where('assigned_to', \Illuminate\Support\Facades\Auth::id())
                                            ->first();
                                        try {
                                            app(\App\Modules\Workflows\Services\AuditorReviewService::class)->requestRevision($task, \Illuminate\Support\Facades\Auth::user(), $data['reason'] ?? '');
                                            \Filament\Notifications\Notification::make()->title('Dikembalikan untuk revisi')->success()->send();
                                        } catch (\Exception $e) {
                                            \Filament\Notifications\Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                                        }
                                    }),

                                \Filament\Infolists\Components\Actions\Action::make('approve_hasil_audit')
                                    ->label('Approve Hasil Audit')
                                    ->icon('heroicon-o-check-circle')
                                    ->color('success')
                                    ->requiresConfirmation()
                                    ->visible(function ($record) {
                                        if (!$record->project) return false;
                                        $task = \App\Modules\Workflows\Models\Task::where('project_id', $record->project->id)
                                            ->where('task_type', \App\Modules\Workflows\Enums\TaskType::AUDITOR_REVIEW->value)
                                            ->where('assigned_to', \Illuminate\Support\Facades\Auth::id())
                                            ->first();
                                        return $task && $task->status === \App\Modules\Workflows\Enums\TaskStatus::IN_PROGRESS;
                                    })
                                    ->action(function ($record) {
                                        $task = \App\Modules\Workflows\Models\Task::where('project_id', $record->project->id)
                                            ->where('task_type', \App\Modules\Workflows\Enums\TaskType::AUDITOR_REVIEW->value)
                                            ->where('assigned_to', \Illuminate\Support\Facades\Auth::id())
                                            ->first();
                                        try {
                                            app(\App\Modules\Workflows\Services\AuditorReviewService::class)->approveExecution($task, \Illuminate\Support\Facades\Auth::user());
                                            \Filament\Notifications\Notification::make()->title('Hasil Audit di-Approve')->success()->send();
                                        } catch (\Exception $e) {
                                            \Filament\Notifications\Notification::make()->title('Gagal Approve')->body($e->getMessage())->danger()->send();
                                        }
                                    }),

                                \Filament\Infolists\Components\Actions\Action::make('buka_kembali_workflow')
                                    ->label('Buka Kembali Workflow')
                                    ->icon('heroicon-o-lock-open')
                                    ->color('danger')
                                    ->requiresConfirmation()
                                    ->modalHeading('Buka Kembali Workflow')
                                    ->modalDescription('Apakah Anda yakin ingin membuka kembali workflow yang sudah selesai? Pastikan Invoice Negara belum diterbitkan.')
                                    ->visible(function ($record) {
                                        if (!$record->project) return false;
                                        return \Illuminate\Support\Facades\Auth::user()->hasRole(['Super Admin', 'Manager Operasional']);
                                    })
                                    ->form([
                                        \Filament\Forms\Components\Select::make('workflow_track')
                                            ->label('Workflow Track')
                                            ->options([
                                                'ENTRY_PROGRESS' => 'Workflow A (Entry)',
                                                'AUDITOR_PROGRESS' => 'Workflow B (Audit)',
                                            ])
                                            ->required(),
                                        \Filament\Forms\Components\Select::make('reopened_status')
                                            ->label('Status Tujuan')
                                            ->options(function (\Filament\Forms\Get $get) {
                                                if ($get('workflow_track') === 'ENTRY_PROGRESS') {
                                                    return [
                                                        \App\Modules\Workflows\Enums\WorkflowStatus::ENTRY_NOT_STARTED->value => 'Belum Dimulai (ENTRY_NOT_STARTED)',
                                                        \App\Modules\Workflows\Enums\WorkflowStatus::WAITING_CLIENT_DOCUMENTS->value => 'Menunggu Dokumen (WAITING_CLIENT_DOCUMENTS)',
                                                    ];
                                                }
                                                return [
                                                    \App\Modules\Workflows\Enums\WorkflowStatus::AUDITOR_NOT_PROCESSED->value => 'Belum Diproses (AUDITOR_NOT_PROCESSED)',
                                                    \App\Modules\Workflows\Enums\WorkflowStatus::DOCUMENT_REVIEW->value => 'Review Dokumen (DOCUMENT_REVIEW)',
                                                ];
                                            })
                                            ->required(),
                                        \Filament\Forms\Components\Textarea::make('reason')
                                            ->label('Alasan')
                                            ->required(),
                                    ])
                                    ->action(function (array $data, $record) {
                                        try {
                                            app(\App\Modules\Workflows\Services\WorkflowReopeningService::class)->reopen(
                                                $record->project->id,
                                                $data['workflow_track'],
                                                $data['reopened_status'],
                                                \Illuminate\Support\Facades\Auth::user(),
                                                $data['reason']
                                            );
                                            \Filament\Notifications\Notification::make()->title('Workflow Berhasil Dibuka Kembali')->success()->send();
                                        } catch (\Exception $e) {
                                            \Filament\Notifications\Notification::make()->title('Gagal Membuka Kembali')->body($e->getMessage())->danger()->send();
                                        }
                                    }),
                            ])
                            ->columnSpan(1),

                        \Filament\Infolists\Components\Section::make('Invoice & Pembayaran Negara')
                            ->description('Unggah invoice dari BPJPH dan catat pembayarannya.')
                            ->schema([
                                TextEntry::make('gov_invoice_status')
                                    ->label('Status Invoice Negara')
                                    ->state(function ($record) {
                                        if (!$record->project) return '-';
                                        
                                        $invoice = \App\Modules\Payments\Models\Invoice::where('project_id', $record->project->id)
                                            ->where('invoice_type', \App\Modules\Payments\Enums\InvoiceType::GOVERNMENT->value)
                                            ->where('status', '!=', \App\Modules\Payments\Enums\InvoiceStatus::CANCELLED->value)
                                            ->first();
                                            
                                        if (!$invoice) {
                                            return $record->project->status === \App\Modules\Projects\Enums\ProjectStatus::WAITING_GOVERNMENT_INVOICE 
                                                ? 'Menunggu Diunggah' 
                                                : '-';
                                        }
                                        
                                        return $invoice->status->getLabel();
                                    })
                                    ->badge(),
                                    
                                TextEntry::make('gov_invoice_nominal')
                                    ->label('Tagihan')
                                    ->state(function ($record) {
                                        $invoice = \App\Modules\Payments\Models\Invoice::where('project_id', $record->project?->id)
                                            ->where('invoice_type', \App\Modules\Payments\Enums\InvoiceType::GOVERNMENT->value)
                                            ->where('status', '!=', \App\Modules\Payments\Enums\InvoiceStatus::CANCELLED->value)
                                            ->first();
                                        return $invoice ? 'Rp ' . number_format($invoice->total, 0, ',', '.') : '-';
                                    }),
                            ])
                            ->headerActions([
                                \Filament\Infolists\Components\Actions\Action::make('unggah_invoice_negara')
                                    ->label('Unggah Invoice Negara')
                                    ->icon('heroicon-o-arrow-up-tray')
                                    ->color('primary')
                                    ->visible(function ($record) {
                                        if (!$record->project) return false;
                                        if ($record->project->status !== \App\Modules\Projects\Enums\ProjectStatus::WAITING_GOVERNMENT_INVOICE) return false;
                                        if (!\Illuminate\Support\Facades\Auth::user()->hasRole(['Super Admin', 'Admin Perusahaan'])) return false;
                                        
                                        $hasInvoice = \App\Modules\Payments\Models\Invoice::where('project_id', $record->project->id)
                                            ->where('invoice_type', \App\Modules\Payments\Enums\InvoiceType::GOVERNMENT->value)
                                            ->where('status', '!=', \App\Modules\Payments\Enums\InvoiceStatus::CANCELLED->value)
                                            ->exists();
                                            
                                        return !$hasInvoice;
                                    })
                                    ->form([
                                        \Filament\Forms\Components\TextInput::make('invoice_number')
                                            ->label('Nomor Invoice (dari BPJPH)')
                                            ->required(),
                                        \Filament\Forms\Components\TextInput::make('nominal')
                                            ->label('Nominal Tagihan')
                                            ->numeric()
                                            ->required(),
                                        \Filament\Forms\Components\DatePicker::make('due_date')
                                            ->label('Jatuh Tempo')
                                            ->required(),
                                        \Filament\Forms\Components\FileUpload::make('file')
                                            ->label('File Invoice (PDF)')
                                            ->acceptedFileTypes(['application/pdf'])
                                            ->required()
                                            ->preserveFilenames(),
                                    ])
                                    ->action(function (array $data, $record) {
                                        try {
                                            app(\App\Modules\Payments\Services\GovernmentInvoiceService::class)->create(
                                                $record->project->id,
                                                \Illuminate\Support\Facades\Auth::user(),
                                                $data
                                            );
                                            \Filament\Notifications\Notification::make()->title('Invoice Negara berhasil diunggah')->success()->send();
                                        } catch (\Exception $e) {
                                            \Filament\Notifications\Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                                        }
                                    }),
                                    
                                \Filament\Infolists\Components\Actions\Action::make('catat_pembayaran_negara')
                                    ->label('Catat Pembayaran')
                                    ->icon('heroicon-o-currency-dollar')
                                    ->color('success')
                                    ->visible(function ($record) {
                                        if (!$record->project) return false;
                                        if (!\Illuminate\Support\Facades\Auth::user()->hasRole(['Super Admin', 'Admin Perusahaan'])) return false;
                                        
                                        $invoice = \App\Modules\Payments\Models\Invoice::where('project_id', $record->project->id)
                                            ->where('invoice_type', \App\Modules\Payments\Enums\InvoiceType::GOVERNMENT->value)
                                            ->whereIn('status', [
                                                \App\Modules\Payments\Enums\InvoiceStatus::PUBLISHED->value,
                                                \App\Modules\Payments\Enums\InvoiceStatus::PARTIAL->value,
                                            ])
                                            ->first();
                                            
                                        return $invoice !== null;
                                    })
                                    ->form([
                                        \Filament\Forms\Components\DatePicker::make('payment_date')
                                            ->label('Tanggal Pembayaran')
                                            ->default(now())
                                            ->required(),
                                        \Filament\Forms\Components\TextInput::make('amount')
                                            ->label('Nominal Pembayaran')
                                            ->numeric()
                                            ->required(),
                                        \Filament\Forms\Components\TextInput::make('payment_method')
                                            ->label('Metode Pembayaran (Contoh: Transfer Bank)')
                                            ->required(),
                                        \Filament\Forms\Components\TextInput::make('reference_number')
                                            ->label('No. Referensi')
                                            ->nullable(),
                                        \Filament\Forms\Components\Textarea::make('notes')
                                            ->label('Catatan')
                                            ->nullable(),
                                        \Filament\Forms\Components\FileUpload::make('proof_file')
                                            ->label('Bukti Pembayaran')
                                            ->required()
                                            ->preserveFilenames(),
                                    ])
                                    ->action(function (array $data, $record) {
                                        $invoice = \App\Modules\Payments\Models\Invoice::where('project_id', $record->project->id)
                                            ->where('invoice_type', \App\Modules\Payments\Enums\InvoiceType::GOVERNMENT->value)
                                            ->whereIn('status', [
                                                \App\Modules\Payments\Enums\InvoiceStatus::PUBLISHED->value,
                                                \App\Modules\Payments\Enums\InvoiceStatus::PARTIAL->value,
                                            ])
                                            ->first();
                                            
                                        if (!$invoice) return;
                                        
                                        try {
                                            app(\App\Modules\Payments\Services\PaymentService::class)->createPayment($invoice, $data, $data['proof_file'] ?? null);
                                            \Filament\Notifications\Notification::make()->title('Pembayaran berhasil dicatat dan menunggu verifikasi')->success()->send();
                                        } catch (\Exception $e) {
                                            \Filament\Notifications\Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                                        }
                                    }),
                                    
                                \Filament\Infolists\Components\Actions\Action::make('verifikasi_pembayaran_negara')
                                    ->label('Verifikasi Pembayaran')
                                    ->icon('heroicon-o-check-circle')
                                    ->color('success')
                                    ->visible(function ($record) {
                                        if (!$record->project) return false;
                                        if (!\Illuminate\Support\Facades\Auth::user()->hasRole(['Super Admin', 'Finance'])) return false;
                                        
                                        $invoice = \App\Modules\Payments\Models\Invoice::where('project_id', $record->project->id)
                                            ->where('invoice_type', \App\Modules\Payments\Enums\InvoiceType::GOVERNMENT->value)
                                            ->first();
                                            
                                        if (!$invoice) return false;
                                        
                                        return $invoice->payments()->where('status', \App\Modules\Payments\Enums\PaymentStatus::PENDING->value)->exists();
                                    })
                                    ->form(function ($record) {
                                        $invoice = \App\Modules\Payments\Models\Invoice::where('project_id', $record->project->id)
                                            ->where('invoice_type', \App\Modules\Payments\Enums\InvoiceType::GOVERNMENT->value)
                                            ->first();
                                            
                                        $pendingPayments = $invoice ? $invoice->payments()->where('status', \App\Modules\Payments\Enums\PaymentStatus::PENDING->value)->get() : collect();
                                        
                                        $options = $pendingPayments->mapWithKeys(function ($payment) {
                                            return [$payment->id => "Rp " . number_format($payment->amount, 0, ',', '.') . " (" . $payment->payment_date->format('d M Y') . ")"];
                                        });
                                        
                                        return [
                                            \Filament\Forms\Components\Select::make('payment_id')
                                                ->label('Pilih Pembayaran Pending')
                                                ->options($options)
                                                ->required(),
                                            \Filament\Forms\Components\Textarea::make('verification_notes')
                                                ->label('Catatan Verifikasi')
                                                ->nullable(),
                                        ];
                                    })
                                    ->action(function (array $data) {
                                        $payment = \App\Modules\Payments\Models\Payment::find($data['payment_id']);
                                        if (!$payment) return;
                                        
                                        try {
                                            app(\App\Modules\Payments\Services\PaymentService::class)->verifyPayment($payment, $data);
                                            \Filament\Notifications\Notification::make()->title('Pembayaran diverifikasi')->success()->send();
                                        } catch (\Exception $e) {
                                            \Filament\Notifications\Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                                        }
                                    }),
                                    
                                \Filament\Infolists\Components\Actions\Action::make('tolak_pembayaran_negara')
                                    ->label('Tolak Pembayaran')
                                    ->icon('heroicon-o-x-circle')
                                    ->color('danger')
                                    ->visible(function ($record) {
                                        if (!$record->project) return false;
                                        if (!\Illuminate\Support\Facades\Auth::user()->hasRole(['Super Admin', 'Finance'])) return false;
                                        
                                        $invoice = \App\Modules\Payments\Models\Invoice::where('project_id', $record->project->id)
                                            ->where('invoice_type', \App\Modules\Payments\Enums\InvoiceType::GOVERNMENT->value)
                                            ->first();
                                            
                                        if (!$invoice) return false;
                                        
                                        return $invoice->payments()->where('status', \App\Modules\Payments\Enums\PaymentStatus::PENDING->value)->exists();
                                    })
                                    ->form(function ($record) {
                                        $invoice = \App\Modules\Payments\Models\Invoice::where('project_id', $record->project->id)
                                            ->where('invoice_type', \App\Modules\Payments\Enums\InvoiceType::GOVERNMENT->value)
                                            ->first();
                                            
                                        $pendingPayments = $invoice ? $invoice->payments()->where('status', \App\Modules\Payments\Enums\PaymentStatus::PENDING->value)->get() : collect();
                                        
                                        $options = $pendingPayments->mapWithKeys(function ($payment) {
                                            return [$payment->id => "Rp " . number_format($payment->amount, 0, ',', '.') . " (" . $payment->payment_date->format('d M Y') . ")"];
                                        });
                                        
                                        return [
                                            \Filament\Forms\Components\Select::make('payment_id')
                                                ->label('Pilih Pembayaran Pending')
                                                ->options($options)
                                                ->required(),
                                            \Filament\Forms\Components\Textarea::make('rejection_reason')
                                                ->label('Alasan Penolakan')
                                                ->required(),
                                        ];
                                    })
                                    ->action(function (array $data) {
                                        $payment = \App\Modules\Payments\Models\Payment::find($data['payment_id']);
                                        if (!$payment) return;
                                        
                                        try {
                                            app(\App\Modules\Payments\Services\PaymentService::class)->rejectPayment($payment, $data);
                                            \Filament\Notifications\Notification::make()->title('Pembayaran ditolak')->success()->send();
                                        } catch (\Exception $e) {
                                            \Filament\Notifications\Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                                        }
                                    }),
                                    
                                \Filament\Infolists\Components\Actions\Action::make('lihat_invoice_negara')
                                    ->label('Lihat Invoice')
                                    ->icon('heroicon-o-document-text')
                                    ->color('gray')
                                    ->visible(function ($record) {
                                        if (!$record->project) return false;
                                        
                                        $invoice = \App\Modules\Payments\Models\Invoice::where('project_id', $record->project->id)
                                            ->where('invoice_type', \App\Modules\Payments\Enums\InvoiceType::GOVERNMENT->value)
                                            ->first();
                                            
                                        return $invoice && $invoice->getFirstMedia('government-invoice-document');
                                    })
                                    ->url(function ($record) {
                                        $invoice = \App\Modules\Payments\Models\Invoice::where('project_id', $record->project->id)
                                            ->where('invoice_type', \App\Modules\Payments\Enums\InvoiceType::GOVERNMENT->value)
                                            ->first();
                                        
                                        return $invoice ? $invoice->getFirstMediaUrl('government-invoice-document') : '#';
                                    })
                                    ->openUrlInNewTab(),
                                    
                                \Filament\Infolists\Components\Actions\Action::make('lihat_bukti_pembayaran_negara')
                                    ->label('Lihat Bukti Bayar Terakhir')
                                    ->icon('heroicon-o-photo')
                                    ->color('gray')
                                    ->visible(function ($record) {
                                        if (!$record->project) return false;
                                        
                                        $invoice = \App\Modules\Payments\Models\Invoice::where('project_id', $record->project->id)
                                            ->where('invoice_type', \App\Modules\Payments\Enums\InvoiceType::GOVERNMENT->value)
                                            ->first();
                                            
                                        if (!$invoice) return false;
                                        
                                        $latestPayment = $invoice->payments()->latest()->first();
                                        return $latestPayment && $latestPayment->getFirstMedia('payment-proofs');
                                    })
                                    ->url(function ($record) {
                                        $invoice = \App\Modules\Payments\Models\Invoice::where('project_id', $record->project->id)
                                            ->where('invoice_type', \App\Modules\Payments\Enums\InvoiceType::GOVERNMENT->value)
                                            ->first();
                                            
                                        $latestPayment = $invoice->payments()->latest()->first();
                                        return $latestPayment ? $latestPayment->getFirstMediaUrl('payment-proofs') : '#';
                                    })
                                    ->openUrlInNewTab(),
                            ])
                            ->columnSpanFull(),
                        ]),


                    Tabs\Tab::make('Workflow')
                        ->icon('heroicon-o-arrow-path')
                        ->schema([
                            TextEntry::make('workflow_placeholder')
                                ->hiddenLabel()
                                ->default('Tab Workflow akan diimplementasikan pada milestone selanjutnya.'),
                        ]),

                    Tabs\Tab::make('Dokumen')
                        ->icon('heroicon-o-document-text')
                        ->schema([
                            TextEntry::make('dokumen_placeholder')
                                ->hiddenLabel()
                                ->default('Tab Dokumen akan diimplementasikan pada milestone selanjutnya.'),
                        ]),

                    Tabs\Tab::make('Pembayaran')
                        ->icon('heroicon-o-currency-dollar')
                        ->schema(function ($record) {
                            if (!$record->project) {
                                return [
                                    TextEntry::make('pembayaran_placeholder')
                                        ->hiddenLabel()
                                        ->default('Belum ada project yang dikerjakan.'),
                                ];
                            }
                            
                            $summary = \App\Modules\Projects\Services\ProjectFinancialSummaryService::calculate($record->project);
                            $isPartner = $record->project->client->type === \App\Modules\Clients\Enums\ClientType::PARTNER->value;
                            
                            // Cek jika butuh Termin
                            $canIssueTermin = $record->project->paymentSchedules()
                                ->where('status', 'PENDING')
                                ->where('invoice_type', \App\Modules\Payments\Enums\InvoiceType::INSTALLMENT->value)
                                ->exists();
                                
                            // Cek jika butuh Pelunasan
                            $canIssueSettlement = false;
                            if (!$canIssueTermin) {
                                $clientRem = \Brick\Math\BigDecimal::of($summary['client_remaining_uninvoiced']);
                                $partnerRem = \Brick\Math\BigDecimal::of($summary['partner_remaining_uninvoiced']);
                                
                                $hasUnpaid = $record->project->invoices()
                                    ->whereIn('status', [
                                        \App\Modules\Payments\Enums\InvoiceStatus::DRAFT->value, 
                                        \App\Modules\Payments\Enums\InvoiceStatus::PUBLISHED->value, 
                                        \App\Modules\Payments\Enums\InvoiceStatus::PARTIAL->value
                                    ])
                                    ->where('invoice_type', '!=', \App\Modules\Payments\Enums\InvoiceType::GOVERNMENT->value)
                                    ->exists();
                                    
                                $hasSettlement = $record->project->invoices()
                                    ->where('invoice_type', \App\Modules\Payments\Enums\InvoiceType::SETTLEMENT->value)
                                    ->where('status', '!=', \App\Modules\Payments\Enums\InvoiceStatus::CANCELLED->value)
                                    ->exists();
                                
                                if (!$hasUnpaid && !$hasSettlement && ($clientRem->isGreaterThan(0) || $partnerRem->isGreaterThan(0))) {
                                    $canIssueSettlement = true;
                                }
                            }
                            
                            $user = \Illuminate\Support\Facades\Auth::user();
                            $canManage = $user->hasRole(['Super Admin', 'Finance']);

                            return [
                                \Filament\Infolists\Components\Section::make('Ringkasan Finansial Komersial')
                                    ->schema([
                                        TextEntry::make('financial.client_contract')
                                            ->label('Nilai Kontrak Klien')
                                            ->default(fn () => 'Rp ' . number_format((float) $summary['client_total_contract'], 0, ',', '.')),
                                        TextEntry::make('financial.client_invoiced')
                                            ->label('Total Ditagih (Klien)')
                                            ->default(fn () => 'Rp ' . number_format((float) $summary['client_total_invoiced'], 0, ',', '.')),
                                        TextEntry::make('financial.client_paid')
                                            ->label('Total Terbayar (Klien)')
                                            ->default(fn () => 'Rp ' . number_format((float) $summary['client_total_paid'], 0, ',', '.')),
                                        TextEntry::make('financial.client_rem_uninvoiced')
                                            ->label('Sisa Belum Ditagih (Klien)')
                                            ->default(fn () => 'Rp ' . number_format((float) $summary['client_remaining_uninvoiced'], 0, ',', '.')),
                                        TextEntry::make('financial.client_rem_unpaid')
                                            ->label('Sisa Belum Dibayar (Klien)')
                                            ->default(fn () => 'Rp ' . number_format((float) $summary['client_remaining_unpaid'], 0, ',', '.')),
                                            
                                        // Partner info
                                        TextEntry::make('financial.partner_contract')
                                            ->label('Nilai Kontrak Mitra')
                                            ->visible($isPartner)
                                            ->default(fn () => 'Rp ' . number_format((float) $summary['partner_total_contract'], 0, ',', '.')),
                                        TextEntry::make('financial.partner_invoiced')
                                            ->label('Total Ditagih (Mitra)')
                                            ->visible($isPartner)
                                            ->default(fn () => 'Rp ' . number_format((float) $summary['partner_total_invoiced'], 0, ',', '.')),
                                        TextEntry::make('financial.partner_paid')
                                            ->label('Total Terbayar (Mitra)')
                                            ->visible($isPartner)
                                            ->default(fn () => 'Rp ' . number_format((float) $summary['partner_total_paid'], 0, ',', '.')),
                                        TextEntry::make('financial.partner_rem_uninvoiced')
                                            ->label('Sisa Belum Ditagih (Mitra)')
                                            ->visible($isPartner)
                                            ->default(fn () => 'Rp ' . number_format((float) $summary['partner_remaining_uninvoiced'], 0, ',', '.')),
                                        TextEntry::make('financial.partner_rem_unpaid')
                                            ->label('Sisa Belum Dibayar (Mitra)')
                                            ->visible($isPartner)
                                            ->default(fn () => 'Rp ' . number_format((float) $summary['partner_remaining_unpaid'], 0, ',', '.')),
                                    ])
                                    ->columns(5)
                                    ->headerActions([
                                        \Filament\Infolists\Components\Actions\Action::make('issue_termin')
                                            ->label('Terbitkan Termin Berikutnya')
                                            ->color('primary')
                                            ->visible($canIssueTermin && $canManage)
                                            ->form([
                                                \Filament\Forms\Components\DatePicker::make('issued_at')
                                                    ->label('Tanggal Terbit')
                                                    ->default(now())
                                                    ->required(),
                                                \Filament\Forms\Components\DatePicker::make('due_date')
                                                    ->label('Jatuh Tempo')
                                                    ->default(now()->addDays(7))
                                                    ->required(),
                                                \Filament\Forms\Components\Textarea::make('notes')
                                                    ->label('Catatan Tambahan')
                                                    ->nullable(),
                                            ])
                                            ->action(function (array $data, $record) {
                                                try {
                                                    app(\App\Modules\Payments\Services\TerminService::class)->issueNextTermin(
                                                        $record->project, 
                                                        $data['issued_at'], 
                                                        $data['due_date'], 
                                                        $data['notes'], 
                                                        \Illuminate\Support\Facades\Auth::user()
                                                    );
                                                    \Filament\Notifications\Notification::make()->title('Termin diterbitkan')->success()->send();
                                                } catch (\Exception $e) {
                                                    \Filament\Notifications\Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                                                }
                                            }),
                                            
                                        \Filament\Infolists\Components\Actions\Action::make('issue_settlement')
                                            ->label('Terbitkan Invoice Pelunasan')
                                            ->color('success')
                                            ->visible($canIssueSettlement && $canManage)
                                            ->form([
                                                \Filament\Forms\Components\DatePicker::make('issued_at')
                                                    ->label('Tanggal Terbit')
                                                    ->default(now())
                                                    ->required(),
                                                \Filament\Forms\Components\DatePicker::make('due_date')
                                                    ->label('Jatuh Tempo')
                                                    ->default(now()->addDays(7))
                                                    ->required(),
                                                \Filament\Forms\Components\Textarea::make('notes')
                                                    ->label('Catatan Tambahan')
                                                    ->nullable(),
                                            ])
                                            ->action(function (array $data, $record) {
                                                try {
                                                    app(\App\Modules\Payments\Services\TerminService::class)->issueSettlement(
                                                        $record->project, 
                                                        $data['issued_at'], 
                                                        $data['due_date'], 
                                                        $data['notes'], 
                                                        \Illuminate\Support\Facades\Auth::user()
                                                    );
                                                    \Filament\Notifications\Notification::make()->title('Pelunasan diterbitkan')->success()->send();
                                                } catch (\Exception $e) {
                                                    \Filament\Notifications\Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                                                }
                                            }),
                                    ]),
                                    
                                \Filament\Infolists\Components\RepeatableEntry::make('project.invoices')
                                    ->label('Daftar Invoice')
                                    ->schema([
                                        TextEntry::make('invoice_number')->label('No. Invoice'),
                                        TextEntry::make('invoice_type')->label('Jenis'),
                                        TextEntry::make('audience')->label('Audience'),
                                        TextEntry::make('subtotal')
                                            ->label('Nominal')
                                            ->formatStateUsing(fn ($state) => 'Rp ' . number_format((float) $state, 0, ',', '.')),
                                        TextEntry::make('status')->label('Status')->badge(),
                                        TextEntry::make('due_date')->label('Jatuh Tempo')->date('d M Y'),
                                    ])
                                    ->columns(6),
                            ];
                        }),

                    Tabs\Tab::make('Timeline')
                        ->icon('heroicon-o-clock')
                        ->schema([
                            TextEntry::make('timeline_placeholder')
                                ->hiddenLabel()
                                ->default('Tab Timeline akan diimplementasikan pada milestone selanjutnya.'),
                        ]),

                    Tabs\Tab::make('Activity Log')
                        ->icon('heroicon-o-clipboard-document-list')
                        ->schema([
                            TextEntry::make('activity_placeholder')
                                ->hiddenLabel()
                                ->default('Tab Activity Log akan diimplementasikan pada milestone selanjutnya.'),
                        ]),

                    Tabs\Tab::make('Assignment')
                        ->icon('heroicon-o-users')
                        ->schema([
                            TextEntry::make('assignment_placeholder')
                                ->hiddenLabel()
                                ->default('Tab Assignment akan diimplementasikan pada milestone selanjutnya.'),
                        ]),

                    Tabs\Tab::make('Sertifikat')
                        ->icon('heroicon-o-academic-cap')
                        ->schema(function ($record) {
                            if (!$record->project) {
                                return [
                                    TextEntry::make('sertifikat_placeholder')
                                        ->hiddenLabel()
                                        ->default('Belum ada project yang dikerjakan.'),
                                ];
                            }
                            
                            $certificate = $record->project->certificate;
                            
                            if (!$certificate) {
                                return [
                                    TextEntry::make('sertifikat_placeholder')
                                        ->hiddenLabel()
                                        ->default('Sertifikat belum diterbitkan.'),
                                        
                                    \Filament\Infolists\Components\Actions::make([
                                        \Filament\Infolists\Components\Actions\Action::make('unggah_sertifikat')
                                            ->label('Unggah Sertifikat')
                                            ->icon('heroicon-o-arrow-up-tray')
                                            ->color('primary')
                                            ->visible(function ($record) {
                                                if (!$record->project) return false;
                                                
                                                // Hanya jika project WAITING_CERTIFICATE
                                                if ($record->project->status !== \App\Modules\Projects\Enums\ProjectStatus::WAITING_CERTIFICATE) {
                                                    return false;
                                                }
                                                
                                                // Hanya jika invoice PAID
                                                $invoice = \App\Modules\Payments\Models\Invoice::where('project_id', $record->project->id)
                                                    ->where('invoice_type', \App\Modules\Payments\Enums\InvoiceType::GOVERNMENT->value)
                                                    ->first();
                                                    
                                                if (!$invoice || $invoice->status !== \App\Modules\Payments\Enums\InvoiceStatus::PAID) {
                                                    return false;
                                                }
                                                
                                                // Authorization
                                                $user = \Illuminate\Support\Facades\Auth::user();
                                                if ($user->hasRole('Super Admin')) return true;
                                                
                                                if ($user->hasRole('Admin Perusahaan')) {
                                                    $assignment = \App\Modules\Projects\Models\ProjectAssignment::where('project_id', $record->project->id)
                                                        ->where('user_id', $user->id)
                                                        ->where('role', 'PIC')
                                                        ->first();
                                                        
                                                    return $assignment !== null;
                                                }
                                                
                                                return false;
                                            })
                                            ->form([
                                                \Filament\Forms\Components\TextInput::make('certificate_number')
                                                    ->label('Nomor Sertifikat')
                                                    ->required()
                                                    ->unique('certificates', 'certificate_number'),
                                                \Filament\Forms\Components\DatePicker::make('issued_at')
                                                    ->label('Tanggal Terbit')
                                                    ->required(),
                                                \Filament\Forms\Components\DatePicker::make('valid_until')
                                                    ->label('Masa Berlaku (Opsional)')
                                                    ->after('issued_at')
                                                    ->nullable(),
                                                \Filament\Forms\Components\FileUpload::make('file')
                                                    ->label('Dokumen Sertifikat (PDF)')
                                                    ->acceptedFileTypes(['application/pdf'])
                                                    ->maxSize(5120) // Maksimal 5MB
                                                    ->required()
                                                    ->storeFiles(false),
                                            ])
                                            ->action(function (array $data, $record, \Filament\Forms\Components\FileUpload $component) {
                                                try {
                                                    $file = collect($component->getState())->first();
                                                    
                                                    app(\App\Modules\Projects\Services\CertificateService::class)->issueCertificate(
                                                        $record->project, 
                                                        $data, 
                                                        $file, 
                                                        \Illuminate\Support\Facades\Auth::user()
                                                    );
                                                    
                                                    \Filament\Notifications\Notification::make()
                                                        ->title('Sertifikat Berhasil Diunggah')
                                                        ->success()
                                                        ->send();
                                                } catch (\Exception $e) {
                                                    \Filament\Notifications\Notification::make()
                                                        ->title('Gagal Mengunggah Sertifikat')
                                                        ->body($e->getMessage())
                                                        ->danger()
                                                        ->send();
                                                }
                                            })
                                    ])->columnSpanFull(),
                                ];
                            }
                            
                            return [
                                \Filament\Infolists\Components\Section::make('Informasi Sertifikat')
                                    ->schema([
                                        TextEntry::make('project.certificate.certificate_number')
                                            ->label('Nomor Sertifikat'),
                                        TextEntry::make('project.certificate.issued_at')
                                            ->label('Tanggal Terbit')
                                            ->date('d M Y'),
                                        TextEntry::make('project.certificate.valid_until')
                                            ->label('Masa Berlaku')
                                            ->date('d M Y')
                                            ->default('-'),
                                        TextEntry::make('project.certificate.uploader.name')
                                            ->label('Diterbitkan Oleh'),
                                        TextEntry::make('project.certificate.created_at')
                                            ->label('Waktu Penerbitan')
                                            ->dateTime('d M Y H:i'),
                                            
                                        \Filament\Infolists\Components\Actions::make([
                                            \Filament\Infolists\Components\Actions\Action::make('lihat_sertifikat')
                                                ->label('Lihat Sertifikat')
                                                ->icon('heroicon-o-eye')
                                                ->color('gray')
                                                ->visible(function ($record) {
                                                    return $record->project && $record->project->certificate && $record->project->certificate->getFirstMedia('certificate');
                                                })
                                                ->url(function ($record) {
                                                    return $record->project->certificate->getFirstMediaUrl('certificate');
                                                })
                                                ->openUrlInNewTab(),
                                                
                                            \Filament\Infolists\Components\Actions\Action::make('unduh_sertifikat')
                                                ->label('Unduh Sertifikat')
                                                ->icon('heroicon-o-arrow-down-tray')
                                                ->color('primary')
                                                ->visible(function ($record) {
                                                    return $record->project && $record->project->certificate && $record->project->certificate->getFirstMedia('certificate');
                                                })
                                                ->action(function ($record) {
                                                    $media = $record->project->certificate->getFirstMedia('certificate');
                                                    return response()->download($media->getPath(), $media->file_name);
                                                }),
                                        ])->columnSpanFull(),
                                    ])
                                    ->columns(2),
                            ];
                        }),

                    Tabs\Tab::make('Penyelesaian')
                        ->icon('heroicon-o-flag')
                        ->schema([
                            Section::make('Checklist Penyelesaian Project')
                                ->schema(function ($record) {
                                    if (!$record->project) return [];

                                    $readiness = \App\Modules\Projects\Services\ProjectClosureReadinessService::evaluate($record->project);
                                    
                                    $checklist = [
                                        'certificate_issued' => 'Sertifikat telah diterbitkan',
                                        'government_invoice_paid' => 'Invoice Negara telah dibayar',
                                        'no_draft_invoice' => 'Tidak ada Invoice aktif berstatus DRAFT',
                                        'no_published_invoice' => 'Tidak ada Invoice aktif berstatus PUBLISHED',
                                        'no_partial_invoice' => 'Tidak ada Invoice aktif berstatus PARTIAL',
                                        'no_pending_payment' => 'Tidak ada Payment berstatus PENDING',
                                        'all_termin_invoiced' => 'Seluruh jadwal Termin telah ditagihkan',
                                        'no_remaining_uninvoiced' => 'Sisa belum ditagihkan bernilai 0',
                                        'no_remaining_unpaid' => 'Sisa belum dibayar bernilai 0',
                                        'client_obligations_paid' => 'Seluruh kewajiban CLIENT telah lunas',
                                    ];

                                    if ($record->client_type === \App\Modules\Clients\Enums\ClientType::PARTNER) {
                                        $checklist['partner_obligations_paid'] = 'Seluruh kewajiban PARTNER telah lunas';
                                    }
                                    $checklist['no_open_tasks'] = 'Tidak ada Task wajib yang masih terbuka';

                                    $schema = [];
                                    foreach ($checklist as $key => $label) {
                                        $isReady = $readiness[$key] ?? false;
                                        $icon = $isReady ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle';
                                        $color = $isReady ? 'success' : 'danger';
                                        
                                        $schema[] = \Filament\Infolists\Components\TextEntry::make('chk_' . $key)
                                            ->label($label)
                                            ->default($isReady ? 'Terpenuhi' : 'Belum')
                                            ->badge()
                                            ->color($color)
                                            ->icon($icon);
                                    }

                                    return $schema;
                                })
                                ->columns(2)
                                ->headerActions([
                                    Action::make('batalkan_project')
                                        ->label('Batalkan Project')
                                        ->color('danger')
                                        ->icon('heroicon-o-archive-box-x-mark')
                                        ->requiresConfirmation()
                                        ->visible(function ($record) {
                                            if (!$record->project) return false;
                                            if ($record->project->isLocked()) return false;
                                            
                                            // Action only for Super Admin or specific roles
                                            return Auth::user()->hasAnyRole(['Super Admin', 'Admin Perusahaan']);
                                        })
                                        ->form([
                                            \Filament\Forms\Components\Textarea::make('reason')
                                                ->label('Alasan Pembatalan')
                                                ->required()
                                        ])
                                        ->action(function (array $data, $record) {
                                            try {
                                                app(\App\Modules\Projects\Services\ProjectCancellationService::class)
                                                    ->cancel($record->project, $data['reason'], Auth::user());
                                                Notification::make()->title('Project Dibatalkan')->success()->send();
                                            } catch (\Exception $e) {
                                                Notification::make()->title('Gagal Membatalkan')->body($e->getMessage())->danger()->send();
                                            }
                                        }),

                                    Action::make('buka_kembali_project')
                                        ->label('Buka Kembali Project')
                                        ->color('warning')
                                        ->icon('heroicon-o-arrow-path')
                                        ->requiresConfirmation()
                                        ->visible(function ($record) {
                                            if (!$record->project) return false;
                                            if (!$record->project->isLocked()) return false;
                                            
                                            // Khusus Super Admin
                                            return Auth::user()->hasRole('Super Admin');
                                        })
                                        ->form([
                                            \Filament\Forms\Components\Textarea::make('reason')
                                                ->label('Alasan Membuka Kembali')
                                                ->required()
                                        ])
                                        ->action(function (array $data, $record) {
                                            try {
                                                app(\App\Modules\Projects\Services\ProjectReopeningService::class)
                                                    ->reopen($record->project, $data['reason'], Auth::user());
                                                Notification::make()->title('Project Dibuka Kembali')->success()->send();
                                            } catch (\Exception $e) {
                                                Notification::make()->title('Gagal Membuka Kembali')->body($e->getMessage())->danger()->send();
                                            }
                                        }),
                                ]),
                        ]),
                ])
                ->columnSpanFull(),
        ];
    }
}
