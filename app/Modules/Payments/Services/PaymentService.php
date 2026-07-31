<?php

namespace App\Modules\Payments\Services;

use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\Invoice;
use App\Modules\Payments\Enums\PaymentStatus;
use App\Modules\Payments\Enums\InvoiceStatus;
use App\Modules\Payments\Enums\InvoiceAudience;
use App\Modules\Payments\Enums\InvoiceType;
use App\Modules\Payments\Events\ActivationBillingGroupPaid;
use Illuminate\Support\Facades\DB;
use Exception;

class PaymentService
{
    public function __construct(
        private readonly PaymentSequenceService $sequenceService
    ) {}

    /**
     * Create a new payment with media proof.
     */
    public function createPayment(Invoice $invoice, array $data, $proofFile): Payment
    {
        return DB::transaction(function () use ($invoice, $data, $proofFile) {
            // Lock the invoice
            $lockedInvoice = Invoice::where('id', $invoice->id)->lockForUpdate()->first();
            
            if (!in_array($lockedInvoice->status, [InvoiceStatus::PUBLISHED, InvoiceStatus::PARTIAL])) {
                throw new Exception("Pembayaran hanya dapat dicatat untuk Invoice yang diterbitkan atau dibayar sebagian.");
            }

            // Calculate pending + verified total
            $existingTotal = $lockedInvoice->payments()
                ->whereIn('status', [PaymentStatus::PENDING, PaymentStatus::VERIFIED])
                ->sum('amount');

            $availableBalance = $lockedInvoice->total - $existingTotal;

            if ($data['amount'] > $availableBalance) {
                throw new Exception("Nominal pembayaran melebihi sisa tagihan yang belum terbayar atau masih diproses.");
            }
            
            if ($data['amount'] <= 0) {
                throw new Exception("Nominal pembayaran harus lebih besar dari 0.");
            }

            $payment = new Payment([
                'invoice_id' => $lockedInvoice->id,
                'payment_number' => $this->sequenceService->generateNextNumber(),
                'payment_date' => $data['payment_date'],
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'],
                'reference_number' => $data['reference_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => PaymentStatus::PENDING,
            ]);

            $payment->save();
            
            if ($proofFile) {
                $payment->addMedia($proofFile)
                    ->toMediaCollection('payment-proofs', 'private');
            }

            return $payment;
        });
    }

    /**
     * Verify a payment atomically and update Invoice status.
     */
    public function verifyPayment(Payment $payment, array $data): Payment
    {
        return DB::transaction(function () use ($payment, $data) {
            $lockedPayment = Payment::where('id', $payment->id)->lockForUpdate()->first();
            $lockedInvoice = Invoice::where('id', $lockedPayment->invoice_id)->lockForUpdate()->first();

            if ($lockedPayment->status !== PaymentStatus::PENDING) {
                throw new Exception("Hanya pembayaran berstatus PENDING yang dapat diverifikasi.");
            }

            if (!in_array($lockedInvoice->status, [InvoiceStatus::PUBLISHED, InvoiceStatus::PARTIAL])) {
                throw new Exception("Invoice sudah tidak valid untuk menerima pembayaran (mungkin sudah lunas atau dibatalkan).");
            }
            
            // Recalculate verified only total to check overpayment again just in case
            $verifiedTotal = $lockedInvoice->payments()
                ->where('status', PaymentStatus::VERIFIED)
                ->sum('amount');
                
            if (($verifiedTotal + $lockedPayment->amount) > $lockedInvoice->total) {
                throw new Exception("Pembayaran ini tidak dapat diverifikasi karena akan menyebabkan overpayment.");
            }

            $lockedPayment->status = PaymentStatus::VERIFIED;
            $lockedPayment->verification_notes = $data['verification_notes'] ?? null;
            $lockedPayment->verified_by = auth()->id();
            $lockedPayment->verified_at = now();
            $lockedPayment->save();

            $newVerifiedTotal = $verifiedTotal + $lockedPayment->amount;
            
            $newInvoiceStatus = ($newVerifiedTotal >= $lockedInvoice->total) 
                ? InvoiceStatus::PAID 
                : InvoiceStatus::PARTIAL;

            $lockedInvoice->status = $newInvoiceStatus;
            $lockedInvoice->save();
            
            $this->checkAndEmitActivationEvent($lockedInvoice, $lockedPayment);

            return $lockedPayment;
        });
    }

    /**
     * Reject a payment.
     */
    public function rejectPayment(Payment $payment, array $data): Payment
    {
        return DB::transaction(function () use ($payment, $data) {
            $lockedPayment = Payment::where('id', $payment->id)->lockForUpdate()->first();

            if ($lockedPayment->status !== PaymentStatus::PENDING) {
                throw new Exception("Hanya pembayaran berstatus PENDING yang dapat ditolak.");
            }

            if (empty($data['rejection_reason'])) {
                throw new Exception("Alasan penolakan wajib diisi.");
            }

            $lockedPayment->status = PaymentStatus::REJECTED;
            $lockedPayment->rejection_reason = $data['rejection_reason'];
            $lockedPayment->rejected_by = auth()->id();
            $lockedPayment->rejected_at = now();
            $lockedPayment->save();

            return $lockedPayment;
        });
    }
    
    private function checkAndEmitActivationEvent(Invoice $invoice, Payment $payment): void
    {
        if ($invoice->invoice_type !== InvoiceType::ACTIVATION) {
            return;
        }
        
        if ($invoice->status !== InvoiceStatus::PAID) {
            return;
        }
        
        // Check for partner
        if ($invoice->project->client->partner_id) {
            // For partner, both invoices in the billing group must be PAID
            $invoicesInGroup = Invoice::where('billing_group_id', $invoice->billing_group_id)->get();
            $allPaid = $invoicesInGroup->every(fn ($inv) => $inv->status === InvoiceStatus::PAID);
            
            if ($allPaid) {
                event(new ActivationBillingGroupPaid(
                    $invoice->project_id,
                    $invoice->billing_group_id,
                    $payment->id,
                    $payment->verified_by
                ));
            }
        } else {
            // For direct client
            event(new ActivationBillingGroupPaid(
                $invoice->project_id,
                $invoice->billing_group_id,
                $payment->id,
                $payment->verified_by
            ));
        }
    }
}
