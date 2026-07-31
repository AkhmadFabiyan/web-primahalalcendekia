<?php

namespace App\Filament\Resources\Leads\Tables;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use App\Modules\Leads\Enums\LeadStatus;
use App\Modules\Leads\Models\Lead;

class LeadsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('index')
                    ->label('No.')
                    ->rowIndex(),
                TextColumn::make('company_name')
                    ->label('Nama Perusahaan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('pic_name')
                    ->label('PIC')
                    ->searchable(),
                TextColumn::make('pic_phone')
                    ->label('Kontak')
                    ->searchable(),
                TextColumn::make('marketing.name')
                    ->label('Marketing')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Tanggal Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(LeadStatus::class),
                SelectFilter::make('marketing_id')
                    ->label('Marketing')
                    ->relationship('marketing', 'name', fn ($query) => $query->role(\App\Enums\Role::MARKETING->value)),
                SelectFilter::make('lead_source')
                    ->label('Sumber Lead')
                    ->options(fn () => Lead::query()->whereNotNull('lead_source')->distinct()->pluck('lead_source', 'lead_source')->toArray()),
                SelectFilter::make('city')
                    ->label('Kota')
                    ->options(fn () => Lead::query()->whereNotNull('city')->distinct()->pluck('city', 'city')->toArray()),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('deal')
                    ->label('Deal')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Konversi Lead ke Project')
                    ->modalDescription(fn (\App\Modules\Leads\Models\Lead $record) => new \Illuminate\Support\HtmlString(
                        "Anda akan mengonversi Lead ini menjadi Project.<br><br>" .
                        "<strong>Perusahaan:</strong> {$record->company_name}<br>" .
                        "<strong>Tipe:</strong> " . ($record->client_type->getLabel()) . "<br>" .
                        "<strong>Layanan:</strong> {$record->service_type}<br>" .
                        "<strong>Skema Pembayaran:</strong> " . ($record->payment_scheme->getLabel()) . " ({$record->installment_count} Termin)<br><br>" .
                        "Tindakan ini akan membuat Klien, Project, dan Draft Invoice. Tindakan ini tidak dapat dibatalkan."
                    ))
                    ->form(function (\App\Modules\Leads\Models\Lead $record) {
                        $service = new \App\Modules\Leads\Services\LeadConversionService();
                        $candidates = $service->findClientCandidates($record);
                        
                        if ($candidates->isEmpty()) {
                            return [];
                        }

                        $options = $candidates->mapWithKeys(function ($c) {
                            return [$c->id => $c->business_id . ' — ' . $c->company_name];
                        })->toArray();
                        $options['NEW'] = 'Buat Klien Baru';

                        return [
                            \Filament\Forms\Components\Select::make('force_client_id')
                                ->label('Ditemukan Klien dengan nama serupa')
                                ->options($options)
                                ->required()
                                ->helperText('Silakan pilih apakah ingin menggunakan Klien yang sudah ada atau membuat baru.'),
                        ];
                    })
                    ->action(function (\App\Modules\Leads\Models\Lead $record, array $data) {
                        $service = new \App\Modules\Leads\Services\LeadConversionService();
                        $forceClientId = $data['force_client_id'] ?? null;
                        if ($forceClientId === 'NEW') {
                            $forceClientId = null;
                        }
                        
                        try {
                            $service->convert($record, $forceClientId);
                            \Filament\Notifications\Notification::make()
                                ->title('Konversi Berhasil')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Konversi Gagal')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(function (\App\Modules\Leads\Models\Lead $record) {
                        $user = auth()->user();
                        $isSuperAdmin = $user->hasRole(\App\Enums\Role::SUPER_ADMIN->value);
                        $isOwner = $record->marketing_id === $user->id;
                        $canChangeStatus = $user->can('leads.change_status');
                        
                        return $record->status === \App\Modules\Leads\Enums\LeadStatus::DRAFT && 
                               $canChangeStatus && 
                               ($isOwner || $isSuperAdmin);
                    }),
                Action::make('batal')
                    ->label('Batal')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('cancel_reason')
                            ->label('Alasan Pembatalan')
                            ->required()
                    ])
                    ->action(function (Lead $record, array $data) {
                        $record->update([
                            'status' => LeadStatus::CANCELLED,
                        ]);
                        // Activity log reason
                        activity()
                            ->performedOn($record)
                            ->event('cancelled')
                            ->log('Lead dibatalkan: ' . $data['cancel_reason']);
                    })
                    ->visible(fn (Lead $record) => $record->status === LeadStatus::DRAFT),
            ])
            ->bulkActions([
                // Disable bulk actions
            ]);
    }
}
