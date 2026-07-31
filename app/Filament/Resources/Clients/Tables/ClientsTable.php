<?php

namespace App\Filament\Resources\Clients\Tables;

use App\Modules\Clients\Models\Client;
use App\Modules\Clients\Services\ClientAccountService;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use App\Enums\Role;

class ClientsTable
{
    public static function columns(): array
    {
        return [
            TextColumn::make('business_id')
                ->label('ID Klien')
                ->searchable()
                ->sortable(),
            TextColumn::make('company_name')
                ->label('Perusahaan')
                ->searchable()
                ->sortable(),
            TextColumn::make('client_type')
                ->label('Tipe')
                ->badge(),
            TextColumn::make('partner.name')
                ->label('Mitra')
                ->searchable()
                ->toggleable(),
            IconColumn::make('has_account')
                ->label('Akun Login')
                ->boolean()
                ->state(fn (Client $record): bool => $record->userAccount()->exists()),
            TextColumn::make('project.service_type')
                ->label('Jenis Layanan')
                ->searchable()
                ->toggleable(),
            TextColumn::make('project.status')
                ->label('Status')
                ->badge()
                ->toggleable(),
            TextColumn::make('project.assignments.user.name')
                ->label('Assigned To')
                ->listWithLineBreaks()
                ->limitList(2)
                ->expandableLimitedList()
                ->toggleable(),
            TextColumn::make('updated_at')
                ->label('Updated At')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    public static function filters(): array
    {
        return [];
    }

    public static function actions(): array
    {
        return [
            Action::make('createAccount')
                ->label('Buat Akun')
                ->icon('heroicon-o-user-plus')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Buat Akun Login Klien')
                ->modalDescription('Apakah Anda yakin ingin membuat akun login untuk klien ini? Akun dan password sementara akan dibuat secara otomatis dan dikirim melalui email/WhatsApp (jika integrasi aktif).')
                ->visible(fn (Client $record) => auth()->user()->hasRole(Role::SUPER_ADMIN->value) && !$record->userAccount()->exists())
                ->action(function (Client $record) {
                    try {
                        $service = app(ClientAccountService::class);
                        $result = $service->createAccount($record);
                        
                        Notification::make()
                            ->title('Akun Berhasil Dibuat')
                            ->body("Akun login untuk klien telah berhasil dibuat. Username/Email: {$result['user']->email} | Password: {$result['password']} (Harap simpan password ini, hanya ditampilkan sekali)")
                            ->success()
                            ->persistent()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Gagal Membuat Akun')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            ViewAction::make()->slideOver(),
            EditAction::make(),
        ];
    }

    public static function bulkActions(): array
    {
        // No bulk actions allowed
        return [];
    }
}
