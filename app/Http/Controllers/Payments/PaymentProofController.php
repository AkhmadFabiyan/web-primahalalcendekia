<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Modules\Payments\Models\Payment;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Gate;

class PaymentProofController extends Controller
{
    public function download(Payment $payment): StreamedResponse
    {
        // Must have viewAny permission for Payment (Finance / Super Admin)
        if (!Gate::allows('viewAny', Payment::class)) {
            abort(403);
        }

        $media = $payment->getFirstMedia('payment-proofs');

        if (!$media) {
            abort(404, 'Bukti pembayaran tidak ditemukan.');
        }

        return $media->toResponse(request());
    }
}
