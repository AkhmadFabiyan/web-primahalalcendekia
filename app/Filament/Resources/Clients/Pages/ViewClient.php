<?php

namespace App\Filament\Resources\Clients\Pages;

use App\Filament\Resources\Clients\ClientResource;
use App\Modules\Clients\Services\ClientAccountService;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;
use App\Enums\Role;

class ViewClient extends ViewRecord
{
    protected static string $resource = ClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('createAccount')
                ->label('Buat Akun')
                ->icon('heroicon-o-user-plus')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Buat Akun Login Klien')
                ->modalDescription('Apakah Anda yakin ingin membuat akun login untuk klien ini? Akun dan password sementara akan dibuat secara otomatis dan dikirim melalui email/WhatsApp (jika integrasi aktif).')
                ->visible(fn () => auth()->user()->hasRole(Role::SUPER_ADMIN->value) && !$this->record->userAccount()->exists())
                ->action(function () {
                    try {
                        $service = app(ClientAccountService::class);
                        $result = $service->createAccount($this->record);
                        
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
            Actions\EditAction::make(),
        ];
    }
}
