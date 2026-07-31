<?php

namespace App\Filament\Resources\Clients\Infolists;

use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\ViewEntry;
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
                            Grid::make(2)->schema([
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
                            ]),
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
                        ->schema([
                            TextEntry::make('pembayaran_placeholder')
                                ->hiddenLabel()
                                ->default('Tab Pembayaran akan diimplementasikan pada milestone selanjutnya.'),
                        ]),

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
                        ->schema([
                            TextEntry::make('sertifikat_placeholder')
                                ->hiddenLabel()
                                ->default('Tab Sertifikat akan diimplementasikan pada milestone selanjutnya.'),
                        ]),
                ])
                ->columnSpanFull(),
        ];
    }
}
