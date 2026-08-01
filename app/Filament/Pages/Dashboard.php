<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Livewire\Attributes\Url;
use Livewire\Attributes\On;

use Filament\Pages\Dashboard\Actions\FilterAction;
use Filament\Pages\Dashboard\Concerns\HasFiltersAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;

class Dashboard extends BaseDashboard
{
    use HasFiltersAction;

    /**
     * @var string
     */
    #[Url]
    public $section = 'overview';

    /**
     * The valid sections for Client Dashboard.
     */
    protected array $validClientSections = [
        'overview', 'progress', 'payments', 'documents', 'certificate', 'timeline'
    ];

    public function mount()
    {
        // Enforce safe section values for Client Dashboard
        if (auth()->user()->isClient()) {
            if (!in_array($this->section, $this->validClientSections)) {
                $this->section = 'overview';
            }
        }
    }

    #[On('open-drill-down')]
    public function openDrillDown(array $args)
    {
        $this->mountAction('drillDown', $args);
    }

    protected function getHeaderActions(): array
    {
        if (auth()->user()->isInternalStaff() && !auth()->user()->isSuperAdmin()) {
            return [
                FilterAction::make()
                    ->form([
                        DatePicker::make('period_start')
                            ->label('Dari Tanggal Aktivasi'),
                        DatePicker::make('period_end')
                            ->label('Sampai Tanggal Aktivasi'),
                        Select::make('service')
                            ->label('Layanan')
                            ->options([
                                // Populate from DB or enums
                                'Halal Certification' => 'Halal Certification',
                            ]),
                        Select::make('client_type')
                            ->label('Tipe Klien')
                            ->options([
                                'DIRECT' => 'Langsung',
                                'PARTNER' => 'Mitra',
                            ]),
                        Select::make('pic_id')
                            ->label('PIC')
                            ->options(\App\Models\User::where('status', 'ACTIVE')->pluck('name', 'id')),
                        Select::make('status')
                            ->label('Status Project')
                            ->options(\App\Modules\Projects\Enums\ProjectStatus::class),
                        Select::make('stage')
                            ->label('Tahap Operasional')
                            ->options([
                                'Belum/Proses Entry' => 'Belum/Proses Entry',
                                'Menunggu/Persiapan Audit' => 'Menunggu/Persiapan Audit',
                                'Audit/Revisi' => 'Audit/Revisi',
                                'Sidang Fatwa/BPJPH' => 'Sidang Fatwa/BPJPH',
                                'Sertifikat Terbit' => 'Sertifikat Terbit',
                            ]),
                        Select::make('audience')
                            ->label('Audience')
                            ->options([
                                'CLIENT' => 'Klien',
                                'PARTNER' => 'Mitra',
                            ])
                            ->visible(fn () => auth()->user()->can('dashboard.finance.view')),
                        Select::make('invoice_type')
                            ->label('Jenis Invoice')
                            ->options(\App\Modules\Payments\Enums\InvoiceType::class)
                            ->visible(fn () => auth()->user()->can('dashboard.finance.view')),
                        Select::make('invoice_status')
                            ->label('Status Invoice')
                            ->options(\App\Modules\Payments\Enums\InvoiceStatus::class)
                            ->visible(fn () => auth()->user()->can('dashboard.finance.view')),
                        Select::make('payment_method')
                            ->label('Metode Pembayaran')
                            ->options([
                                'TRANSFER' => 'Transfer',
                                'CASH' => 'Tunai',
                            ])
                            ->visible(fn () => auth()->user()->can('dashboard.finance.view')),
                    ]),
                \Filament\Actions\Action::make('drillDown')
                    ->hidden()
                    ->modalHeading(fn (array $arguments) => 'Drill-down: ' . ($arguments['key'] ?? ''))
                    ->modalContent(fn (array $arguments) => view('filament.pages.drill-down-modal', ['arguments' => $arguments]))
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false),
            ];
        }

        return [];
    }

    /**
     * Determine the widgets that should be available on the dashboard.
     *
     * @return array<class-string<\Filament\Widgets\Widget>>
     */
    public function getWidgets(): array
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return [
                \App\Filament\Widgets\SystemHealthWidget::class,
                \App\Filament\Widgets\UserStatsWidget::class,
                \App\Filament\Widgets\LatestActivityLogWidget::class,
            ];
        }

        if ($user->isClient()) {
            return [
                \App\Filament\Widgets\ClientOverviewWidget::class,
                \App\Filament\Widgets\ClientInvoicesWidget::class,
                \App\Filament\Widgets\ClientDocumentsWidget::class,
                \App\Filament\Widgets\ClientTimelineWidget::class,
            ];
        }

        if ($user->isInternalStaff()) {
            return [
                \App\Filament\Widgets\OperationalKpiWidget::class,
                \App\Filament\Widgets\OperationalDistributionChart::class,
                \App\Filament\Widgets\OperationalConditionChart::class,
                \App\Filament\Widgets\OperationalPriorityListWidget::class,
                \App\Filament\Widgets\OperationalStagesWidget::class,
                \App\Filament\Widgets\OperationalGuideWidget::class,
                \App\Filament\Widgets\FinanceKpiWidget::class,
                \App\Filament\Widgets\FinanceRevenueChart::class,
                \App\Filament\Widgets\FinanceAgingReceivablesWidget::class,
                \App\Filament\Widgets\FinancePendingPaymentsWidget::class,
                \App\Filament\Widgets\FinanceOverdueInvoicesWidget::class,
                \App\Filament\Widgets\PersonalWorkloadWidget::class,
                \App\Filament\Widgets\MyTasksWidget::class,
            ];
        }

        return [];
    }
}
