<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Modules\Payments\Models\Invoice;
use App\Modules\Payments\Enums\InvoiceStatus;
use App\Modules\Settings\Services\FinancialSettingsService;
use Carbon\Carbon;

class CheckInvoiceDueCommand extends Command
{
    protected $signature = 'invoice:check-due';
    protected $description = 'Check for due/overdue invoices and send notifications (H-x, H, H+x)';

    public function handle(FinancialSettingsService $settingsService)
    {
        $reminderDays = (int) $settingsService->get('invoice_reminder_days', 3);
        
        $invoices = Invoice::whereIn('status', [InvoiceStatus::PUBLISHED, InvoiceStatus::PARTIAL])
            ->whereNotNull('due_date')
            ->get();

        $today = Carbon::today();

        foreach ($invoices as $invoice) {
            $dueDate = Carbon::parse($invoice->due_date)->startOfDay();
            $diffInDays = $today->diffInDays($dueDate, false); // if negative, overdue

            if ($diffInDays == $reminderDays) {
                // H - reminderDays
                $this->info("Sending reminder for Invoice {->invoice_number} (Due in {} days)");
                // $invoice->project->client->notify(new InvoiceDueReminderNotification($invoice, $reminderDays));
                // Simulasi notifikasi via log karena belum ada implementasi channel client
                \Illuminate\Support\Facades\Log::info("INVOICE_REMINDER: {->invoice_number} is due in {} days.");
            } elseif ($diffInDays == 0) {
                // H
                $this->info("Sending reminder for Invoice {->invoice_number} (Due today)");
                \Illuminate\Support\Facades\Log::info("INVOICE_REMINDER: {->invoice_number} is due TODAY.");
            } elseif ($diffInDays == -1) {
                // H + 1 (Overdue)
                $this->info("Sending overdue notification for Invoice {->invoice_number} (Overdue by 1 day)");
                \Illuminate\Support\Facades\Log::info("INVOICE_OVERDUE: {->invoice_number} is overdue by 1 day.");
            } elseif ($diffInDays < -1 && abs($diffInDays) % 7 == 0) {
                // H + x (Overdue weekly reminder)
                $overdueDays = abs($diffInDays);
                $this->info("Sending overdue notification for Invoice {->invoice_number} (Overdue by {} days)");
                \Illuminate\Support\Facades\Log::info("INVOICE_OVERDUE: {->invoice_number} is overdue by {} days.");
            }
        }
    }
}
