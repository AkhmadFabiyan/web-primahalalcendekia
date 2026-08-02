<?php

namespace App\Filament\Pages;

use App\Filament\Exports\InvoiceBillingGroupExporter;
use App\Filament\Exports\LeadReportExporter;
use App\Filament\Exports\PaymentReportExporter;
use App\Filament\Exports\ProjectReportExporter;
use App\Filament\Support\RoleNavigation;
use App\Filament\Widgets\Reports\LeadConversionChartWidget;
use App\Filament\Widgets\Reports\ProjectStatusChartWidget;
use App\Filament\Widgets\Reports\ReportKpiWidget;
use App\Filament\Widgets\Reports\ReportTableWidget;
use App\Filament\Widgets\Reports\RevenueTrendChartWidget;
use App\Filament\Widgets\Reports\WorkflowPerformanceChartWidget;
use App\Jobs\GeneratePdfReportJob;
use App\Models\User;
use App\Modules\Projects\Enums\ProjectStatus;
use App\Modules\Reports\DataTransferObjects\ManagementReportFilterData;
use App\Modules\Reports\Services\ReportingExportService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard\Concerns\HasFiltersAction;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Pages\Page;
use Spatie\Activitylog\Facades\Activity;

class ManagementReport extends Page
{
    use HasFiltersAction, HasFiltersForm;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Laporan';

    public static function getNavigationGroup(): ?string
    {
        return RoleNavigation::forModule('reports');
    }

    protected static ?string $title = 'Management Reporting & Analytics';

    protected static ?string $slug = 'laporan';

    protected string $view = 'filament.pages.management-report';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && $user->can('report.view');
    }

    public function mount()
    {
        $this->filters = [
            'preset' => 'this_month',
        ];
    }

    public function filtersForm(Form $form): Form
    {
        return $form->schema([
            Section::make('Filter Global')
                ->schema([
                    Grid::make(3)
                        ->schema([
                            Select::make('preset')
                                ->label('Periode Preset')
                                ->options([
                                    'this_month' => 'Bulan Ini',
                                    'last_month' => 'Bulan Lalu',
                                    'this_quarter' => 'Kuartal Ini',
                                    'this_year' => 'Tahun Ini',
                                    'last_12_months' => '12 Bulan Terakhir',
                                    'custom' => 'Custom',
                                ])
                                ->live()
                                ->default('this_month'),

                            DatePicker::make('start_date')
                                ->label('Tanggal Mulai')
                                ->visible(fn (callable $get) => $get('preset') === 'custom')
                                ->default(now()->startOfMonth()),

                            DatePicker::make('end_date')
                                ->label('Tanggal Akhir')
                                ->visible(fn (callable $get) => $get('preset') === 'custom')
                                ->default(now()->endOfMonth()),
                        ]),

                    Grid::make(4)
                        ->schema([
                            Select::make('client_type')
                                ->label('Tipe Klien')
                                ->options([
                                    'DIRECT' => 'Langsung',
                                    'PARTNER' => 'Mitra',
                                ]),

                            Select::make('marketing_id')
                                ->label('Marketing')
                                ->options(User::role('marketing')->pluck('name', 'id')),

                            Select::make('admin_id')
                                ->label('Admin')
                                ->options(User::role('admin')->pluck('name', 'id')),

                            Select::make('entry_id')
                                ->label('Entry')
                                ->options(User::role('entry')->pluck('name', 'id')),

                            Select::make('pendamping_id')
                                ->label('Pendamping')
                                ->options(User::role('pendamping_auditor')->pluck('name', 'id')),

                            Select::make('auditor_id')
                                ->label('Auditor')
                                ->options(User::role('auditor')->pluck('name', 'id')),

                            Select::make('status_project')
                                ->label('Status Project')
                                ->options(collect(ProjectStatus::cases())->mapWithKeys(fn ($c) => [$c->value => str_replace('_', ' ', $c->value)])->toArray()),
                        ]),
                ])->collapsible(),
        ])->statePath('filters');
    }

    protected function getHeaderActions(): array
    {
        $getExportService = function () {
            $filterData = ManagementReportFilterData::fromArray($this->filters ?? []);

            return new ReportingExportService($filterData);
        };

        return [
            Action::make('filter')
                ->label('Filter Laporan')
                ->icon('heroicon-m-funnel')
                ->form($this->filtersForm(Form::make($this))->getSchema())
                ->action(function (array $data) {
                    $this->filters = $data;
                }),

            ActionGroup::make([
                ExportAction::make('export_lead')
                    ->label('Laporan Lead')
                    ->exporter(LeadReportExporter::class)
                    ->formats([ExportFormat::Csv, ExportFormat::Xlsx])
                    ->modifyQueryUsing(fn ($query) => $getExportService()->applyFiltersToLeadQuery($query))
                    ->visible(fn () => auth()->user()?->can('report.export.csv') || auth()->user()?->can('report.export.xlsx'))
                    ->before(function () {
                        Activity::causedBy(auth()->user())
                            ->withProperties(['report_type' => 'Lead', 'status' => 'REQUESTED'])
                            ->log('REPORT_EXPORT_REQUESTED');
                    }),

                ExportAction::make('export_project')
                    ->label('Laporan Project dan Workflow')
                    ->exporter(ProjectReportExporter::class)
                    ->formats([ExportFormat::Csv, ExportFormat::Xlsx])
                    ->modifyQueryUsing(fn ($query) => $getExportService()->applyFiltersToProjectQuery($query))
                    ->visible(fn () => auth()->user()?->can('report.export.csv') || auth()->user()?->can('report.export.xlsx'))
                    ->before(function () {
                        Activity::causedBy(auth()->user())
                            ->withProperties(['report_type' => 'Project', 'status' => 'REQUESTED'])
                            ->log('REPORT_EXPORT_REQUESTED');
                    }),

                ExportAction::make('export_invoice')
                    ->label('Laporan Invoice/Billing Group')
                    ->exporter(InvoiceBillingGroupExporter::class)
                    ->formats([ExportFormat::Csv, ExportFormat::Xlsx])
                    ->modifyQueryUsing(fn ($query) => $getExportService()->applyFiltersToInvoiceQuery($query))
                    ->visible(fn () => auth()->user()?->can('report.export.csv') || auth()->user()?->can('report.export.xlsx'))
                    ->before(function () {
                        Activity::causedBy(auth()->user())
                            ->withProperties(['report_type' => 'Invoice', 'status' => 'REQUESTED'])
                            ->log('REPORT_EXPORT_REQUESTED');
                    }),

                ExportAction::make('export_payment')
                    ->label('Laporan Payment')
                    ->exporter(PaymentReportExporter::class)
                    ->formats([ExportFormat::Csv, ExportFormat::Xlsx])
                    ->modifyQueryUsing(fn ($query) => $getExportService()->applyFiltersToPaymentQuery($query))
                    ->visible(fn () => auth()->user()?->can('report.export.csv') || auth()->user()?->can('report.export.xlsx'))
                    ->before(function () {
                        Activity::causedBy(auth()->user())
                            ->withProperties(['report_type' => 'Payment', 'status' => 'REQUESTED'])
                            ->log('REPORT_EXPORT_REQUESTED');
                    }),

                Action::make('export_pdf')
                    ->label('PDF Ringkasan Manajemen')
                    ->icon('heroicon-m-document-arrow-down')
                    ->action(function () {
                        $filterData = ManagementReportFilterData::fromArray($this->filters ?? []);
                        // Dispatch job for PDF generation
                        GeneratePdfReportJob::dispatch(auth()->user(), $filterData);

                        Notification::make()
                            ->title('Permintaan Ekspor PDF Diterima')
                            ->body('Laporan Ringkasan Manajemen sedang diproses. Anda akan menerima notifikasi saat selesai.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn () => auth()->user()?->can('report.export.pdf')),
            ])
                ->label('Export Laporan')
                ->icon('heroicon-m-arrow-down-tray')
                ->button()
                ->color('success'),
        ];
    }

    public function getHeaderWidgets(): array
    {
        return [
            ReportKpiWidget::class,
            LeadConversionChartWidget::class,
            ProjectStatusChartWidget::class,
            RevenueTrendChartWidget::class,
            WorkflowPerformanceChartWidget::class,
        ];
    }

    public function getFooterWidgets(): array
    {
        return [
            ReportTableWidget::class,
        ];
    }
}
