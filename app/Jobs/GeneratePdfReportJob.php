<?php

namespace App\Jobs;

use App\Models\User;
use App\Modules\Reports\DataTransferObjects\ManagementReportFilterData;
use App\Modules\Reports\Services\ManagementReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Facades\Activity;

class GeneratePdfReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes

    public function __construct(
        protected User $user,
        protected ManagementReportFilterData $filterData
    ) {
        // Log requested event
        Activity::causedBy($this->user)
            ->withProperties([
                'format' => 'PDF',
                'report_type' => 'Management Summary',
                'filter' => (array) $this->filterData,
                'requested_at' => now()->toIso8601String(),
                'status' => 'REQUESTED',
            ])
            ->log('REPORT_EXPORT_REQUESTED');
    }

    public function handle(): void
    {
        try {
            $service = new ManagementReportService($this->filterData);
            
            // Gather data for PDF
            $kpi = $service->getKpis();
            $cycleMetrics = $service->getCycleTimeMetrics();
            $completionMetrics = $service->getCompletionMetrics();
            
            // Render PDF
            $pdf = Pdf::loadView('reports.pdf.management-summary', [
                'user' => $this->user,
                'filterData' => $this->filterData,
                'kpi' => $kpi,
                'cycleMetrics' => $cycleMetrics,
                'completionMetrics' => $completionMetrics,
                'generatedAt' => now(),
            ])
            ->setPaper('a4', 'portrait')
            ->setWarnings(false);

            $fileName = 'PHC-Laporan-Management-' . now()->format('Y-m-d-His') . '.pdf';
            $filePath = 'exports/' . $fileName;
            
            // Save to private disk
            Storage::disk('local')->put($filePath, $pdf->output());

            // Notification
            Notification::make()
                ->title('Laporan PDF Selesai')
                ->body('Laporan Ringkasan Manajemen telah berhasil dibuat.')
                ->success()
                ->actions([
                    Action::make('download')
                        ->label('Unduh File')
                        ->url(route('download.export', ['path' => base64_encode($filePath)])) // Needs a secure download route
                        ->button()
                        ->color('success'),
                ])
                ->sendToDatabase($this->user);

            // Log completion
            Activity::causedBy($this->user)
                ->withProperties([
                    'format' => 'PDF',
                    'report_type' => 'Management Summary',
                    'completed_at' => now()->toIso8601String(),
                    'status' => 'COMPLETED',
                ])
                ->log('REPORT_EXPORT_COMPLETED');

        } catch (\Throwable $e) {
            // Notification
            Notification::make()
                ->title('Laporan PDF Gagal')
                ->body('Terjadi kesalahan saat memproses laporan PDF.')
                ->danger()
                ->sendToDatabase($this->user);

            // Log failure
            Activity::causedBy($this->user)
                ->withProperties([
                    'format' => 'PDF',
                    'report_type' => 'Management Summary',
                    'error' => $e->getMessage(),
                    'status' => 'FAILED',
                ])
                ->log('REPORT_EXPORT_FAILED');
                
            throw $e;
        }
    }
}
