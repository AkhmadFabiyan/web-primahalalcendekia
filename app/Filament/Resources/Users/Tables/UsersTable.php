<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge(),
                \Filament\Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ACTIVE' => 'success',
                        'INACTIVE' => 'danger',
                        default => 'secondary',
                    })
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('last_login_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                \Filament\Actions\ViewAction::make(),
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\Action::make('reset_password')
                    ->label('Reset Password')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (\App\Models\User $record) {
                        $token = app('auth.password.broker')->createToken($record);
                        $record->sendPasswordResetNotification($token);
                        
                        activity()
                            ->performedOn($record)
                            ->event('reset_password')
                            ->log('Password reset link sent');
                            
                        \Filament\Notifications\Notification::make()
                            ->title('Password reset link sent')
                            ->success()
                            ->send();
                    })
            ])
            ->bulkActions([
                // No bulk actions
            ]);
    }
}
