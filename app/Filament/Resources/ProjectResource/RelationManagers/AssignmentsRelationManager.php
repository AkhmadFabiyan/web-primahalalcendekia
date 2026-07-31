<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use App\Models\User;
use App\Modules\Projects\Enums\AssignmentRole;
use App\Modules\Projects\Services\AssignmentService;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\App;

class AssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'assignments';
    protected static ?string $title = 'PIC Internal';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('ended_at'))
            ->recordTitleAttribute('assignment_role')
            ->columns([
                Tables\Columns\TextColumn::make('assignment_role')
                    ->label('Peran')
                    ->sortable()
                    ->badge(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('PIC')
                    ->searchable(),
                Tables\Columns\TextColumn::make('assigner.name')
                    ->label('Ditugaskan Oleh'),
                Tables\Columns\TextColumn::make('assigned_at')
                    ->label('Tanggal Penugasan')
                    ->dateTime(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\Action::make('assign')
                    ->label('Tugaskan PIC')
                    ->icon('heroicon-o-plus')
                    ->form([
                        Forms\Components\Select::make('assignment_role')
                            ->label('Peran')
                            ->options(AssignmentRole::class)
                            ->required(),
                        Forms\Components\Select::make('user_id')
                            ->label('User')
                            ->options(User::whereHas('roles', function ($query) {
                                // Ideally filter out client users if any flag exists, but simple is fine for MVP
                            })->pluck('name', 'id'))
                            ->required(),
                        Forms\Components\Textarea::make('reason')
                            ->label('Alasan')
                            ->nullable(),
                    ])
                    ->action(function (array $data) {
                        $role = AssignmentRole::from($data['assignment_role']);
                        $user = User::find($data['user_id']);
                        $reason = $data['reason'] ?? null;
                        
                        $service = App::make(AssignmentService::class);
                        $service->reassign($this->getOwnerRecord(), $role, $user, $reason);
                    })
                    ->visible(fn () => auth()->user()->hasRole(['Super Admin', 'Manager Operasional'])),
            ])
            ->actions([
                Tables\Actions\Action::make('reassign')
                    ->label('Ganti PIC')
                    ->icon('heroicon-o-arrow-path')
                    ->form([
                        Forms\Components\Select::make('user_id')
                            ->label('User Baru')
                            ->options(User::pluck('name', 'id'))
                            ->required(),
                        Forms\Components\Textarea::make('reason')
                            ->label('Alasan')
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $role = AssignmentRole::from($record->assignment_role->value);
                        $user = User::find($data['user_id']);
                        $reason = $data['reason'];
                        
                        $service = App::make(AssignmentService::class);
                        $service->reassign($this->getOwnerRecord(), $role, $user, $reason);
                    })
                    ->visible(fn () => auth()->user()->hasRole(['Super Admin', 'Manager Operasional'])),
            ])
            ->bulkActions([
                //
            ]);
    }
}
