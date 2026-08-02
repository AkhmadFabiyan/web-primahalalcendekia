<?php

namespace App\Filament\Resources\Clients\RelationManagers;

use App\Modules\Documents\Models\Document;
use App\Modules\Documents\Models\ProjectDocumentRequirement;
use App\Modules\Documents\Services\DocumentService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'projectDocumentRequirements';

    protected static ?string $title = 'Dokumen Persyaratan';

    protected static ?string $modelLabel = 'Dokumen';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\FileUpload::make('file')
                    ->label('File Dokumen')
                    ->required()
                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                    ->maxSize(5120) // 5MB
                    ->helperText('Hanya PDF/JPG/PNG maksimal 5MB.'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('documentType.name')
            ->columns([
                Tables\Columns\TextColumn::make('documentType.name')
                    ->label('Jenis Dokumen'),

                Tables\Columns\IconColumn::make('is_required')
                    ->label('Wajib')
                    ->boolean(),

                Tables\Columns\TextColumn::make('status_dokumen')
                    ->label('Status')
                    ->state(function (ProjectDocumentRequirement $record) {
                        if ($record->revision_requested_at && ! $record->revision_resolved_at) {
                            return 'Revisi Diminta';
                        }

                        return $record->latestDocument ? 'Diunggah' : 'Belum Ada';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Diunggah' => 'success',
                        'Revisi Diminta' => 'danger',
                        'Belum Ada' => 'gray',
                    }),

                Tables\Columns\TextColumn::make('revision_reason')
                    ->label('Catatan Revisi')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // No generic create. We use specific actions per row.
            ])
            ->actions([
                Action::make('upload')
                    ->label(fn (ProjectDocumentRequirement $record) => $record->latestDocument ? 'Ganti (Replace)' : 'Unggah')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->visible(fn (ProjectDocumentRequirement $record): bool => Auth::user()->can('upload', [Document::class, $record->project]))
                    ->form([
                        Forms\Components\FileUpload::make('file')
                            ->label('File')
                            ->required()
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                            ->maxSize(5120),
                        Forms\Components\Toggle::make('is_client_visible')
                            ->label('Bisa dilihat klien?')
                            ->default(true),
                    ])
                    ->action(function (array $data, ProjectDocumentRequirement $record) {
                        $service = app(DocumentService::class);
                        try {
                            // File upload component returns array or path, we pass path
                            $filePath = storage_path('app/public/'.$data['file']); // In filament 3, it saves to public by default temporarily or to defined disk.

                            if ($record->latestDocument) {
                                $service->replaceDocument(
                                    $record->project,
                                    $record->document_type_id,
                                    $filePath,
                                    Auth::user(),
                                    $data['is_client_visible']
                                );
                            } else {
                                $service->uploadDocument(
                                    $record->project,
                                    $record->document_type_id,
                                    $filePath,
                                    Auth::user(),
                                    $data['is_client_visible']
                                );
                            }

                            // If there was an open revision, resolve it
                            if ($record->revision_requested_at && ! $record->revision_resolved_at) {
                                $service->resolveRevision($record, Auth::user());
                            }

                            Notification::make()
                                ->title('Berhasil mengunggah dokumen')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('request_revision')
                    ->label('Minta Revisi')
                    ->icon('heroicon-o-exclamation-circle')
                    ->color('warning')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Alasan Revisi')
                            ->required(),
                    ])
                    ->visible(fn (ProjectDocumentRequirement $record): bool => Auth::user()->can('upload', [Document::class, $record->project])
                        && $record->latestDocument
                        && (! $record->revision_requested_at || $record->revision_resolved_at)
                    )
                    ->action(function (array $data, ProjectDocumentRequirement $record) {
                        app(DocumentService::class)->requestRevision($record, $data['reason'], Auth::user());
                        Notification::make()
                            ->title('Revisi diminta')
                            ->success()
                            ->send();
                    }),

                Action::make('download')
                    ->label('Unduh')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->visible(fn (ProjectDocumentRequirement $record): bool => $record->latestDocument !== null
                        && Auth::user()->can('view', $record->latestDocument)
                    )
                    ->url(fn (ProjectDocumentRequirement $record) => route('documents.download', $record->latestDocument))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                // None
            ]);
    }
}
