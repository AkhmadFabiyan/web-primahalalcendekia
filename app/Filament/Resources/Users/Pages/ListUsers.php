<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('create_staff')
                ->label('Buat Akun Staf')
                ->icon('heroicon-o-user-plus')
                ->visible(fn () => auth()->user()?->hasRole(\App\Enums\Role::SUPER_ADMIN->value))
                ->form([
                    \Filament\Forms\Components\TextInput::make('name')
                        ->required(),
                    \Filament\Forms\Components\TextInput::make('email')
                        ->email()
                        ->required()
                        ->unique(table: \App\Models\User::class),
                    \Filament\Forms\Components\Select::make('roles')
                        ->label('Role')
                        ->required()
                        ->options(function () {
                            return \Spatie\Permission\Models\Role::where('name', '!=', \App\Enums\Role::KLIEN->value)->pluck('name', 'name');
                        }),
                ])
                ->action(function (array $data): void {
                    $user = \App\Models\User::create([
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(32)),
                        'status' => 'ACTIVE',
                    ]);

                    $user->syncRoles([$data['roles']]);

                    // Send password reset link
                    $token = app('auth.password.broker')->createToken($user);
                    $user->sendPasswordResetNotification($token);

                    activity()
                        ->performedOn($user)
                        ->event('create_staff')
                        ->log('Staff account created and reset link sent');

                    \Filament\Notifications\Notification::make()
                        ->title('Akun staf berhasil dibuat')
                        ->body('Tautan aktivasi telah dikirim ke email staf.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
