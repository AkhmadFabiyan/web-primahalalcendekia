<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\Payments\Models\Payment;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/payments",
     *     summary="List payments",
     *     tags={"Payments"}
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = Payment::query();
        $consumer = $request->user();

        if ($consumer->type === 'PARTNER') {
            $query->whereHas('invoice.client', function ($q) use ($consumer) {
                $q->where('partner_id', $consumer->partner_id);
            });
        } elseif ($consumer->type === 'CLIENT') {
            $query->whereHas('invoice', function ($q) use ($consumer) {
                $q->where('client_id', $consumer->client_id);
            });
        }

        return response()->json([
            'data' => $query->paginate(15)
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/payments/{paymentNumber}",
     *     summary="Get payment by number",
     *     tags={"Payments"}
     * )
     */
    public function show(string $paymentNumber, Request $request): JsonResponse
    {
        $payment = Payment::where('payment_number', $paymentNumber)->first();

        if (!$payment) {
            return response()->json(['message' => 'Payment not found'], 404);
        }

        $consumer = $request->user();
        if ($consumer->type === 'PARTNER' && $payment->invoice->client->partner_id !== $consumer->partner_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        if ($consumer->type === 'CLIENT' && $payment->invoice->client_id !== $consumer->client_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json([
            'data' => [
                'id' => $payment->id,
                'payment_number' => $payment->payment_number,
                'status' => $payment->status,
                'amount' => $payment->amount,
                'invoice_id' => $payment->invoice_id,
            ]
        ]);
    }
}
