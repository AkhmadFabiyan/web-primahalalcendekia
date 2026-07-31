<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\TextInput::make('name')
                    ->required(),
                \Filament\Forms\Components\TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                \Filament\Forms\Components\Select::make('roles')
                    ->relationship('roles', 'name', modifyQueryUsing: fn (\Illuminate\Database\Eloquent\Builder $query) => $query->where('name', '!=', \App\Enums\Role::KLIEN->value))
                    ->preload()
                    ->required()
                    ->label('Role')
                    ->rules([
                        fn (\Filament\Schemas\Components\Utilities\Get $get, \Filament\Forms\Components\Select $component) => function (string $attribute, $value, \Closure $fail) use ($get, $component) {
                            $record = $component->getRecord();
                            if (!$record) return;
                            
                            $isRemovingSuperAdmin = $record->isSuperAdmin() && $value !== \App\Enums\Role::SUPER_ADMIN->value;
                            if ($isRemovingSuperAdmin) {
                                $superAdminCount = \App\Models\User::whereHas('roles', fn ($q) => $q->where('name', \App\Enums\Role::SUPER_ADMIN->value))
                                    ->where('status', 'ACTIVE')
                                    ->count();
                                if ($superAdminCount <= 1 && $record->status === 'ACTIVE') {
                                    $fail('Cannot downgrade the last active Super Admin.');
                                }
                            }
                        },
                    ]),
                \Filament\Forms\Components\Select::make('status')
                    ->options([
                        'ACTIVE' => 'Active',
                        'INACTIVE' => 'Inactive',
                    ])
                    ->required()
                    ->default('ACTIVE')
                    ->rules([
                        fn (\Filament\Schemas\Components\Utilities\Get $get, \Filament\Forms\Components\Select $component) => function (string $attribute, $value, \Closure $fail) use ($get, $component) {
                            $record = $component->getRecord();
                            if (!$record) return;
                            
                            if ($value === 'INACTIVE') {
                                if ($record->id === auth()->id()) {
                                    $fail('Super Admin cannot deactivate themselves.');
                                }
                                
                                if ($record->isSuperAdmin()) {
                                    $superAdminCount = \App\Models\User::whereHas('roles', fn ($q) => $q->where('name', \App\Enums\Role::SUPER_ADMIN->value))
                                        ->where('status', 'ACTIVE')
                                        ->count();
                                    if ($superAdminCount <= 1) {
                                        $fail('Cannot deactivate the last active Super Admin.');
                                    }
                                }
                            }
                        },
                    ]),
            ]);
    }
}
