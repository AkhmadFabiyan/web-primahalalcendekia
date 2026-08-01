<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\Payments\Models\Invoice;
use Illuminate\Http\JsonResponse;

class InvoiceController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/invoices",
     *     summary="List invoices",
     *     tags={"Invoices"}
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = Invoice::query();
        $consumer = $request->user();

        // Apply scoped visibility
        if ($consumer->type === 'PARTNER') {
            $query->whereHas('client', function ($q) use ($consumer) {
                $q->where('partner_id', $consumer->partner_id);
            });
        } elseif ($consumer->type === 'CLIENT') {
            $query->where('client_id', $consumer->client_id);
        }

        return response()->json([
            'data' => $query->paginate(15)
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/invoices/{invoiceNumber}",
     *     summary="Get invoice by number",
     *     tags={"Invoices"}
     * )
     */
    public function show(string $invoiceNumber, Request $request): JsonResponse
    {
        $invoice = Invoice::where('invoice_number', $invoiceNumber)->first();

        if (!$invoice) {
            return response()->json(['message' => 'Invoice not found'], 404);
        }

        $consumer = $request->user();
        if ($consumer->type === 'PARTNER' && $invoice->client->partner_id !== $consumer->partner_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        if ($consumer->type === 'CLIENT' && $invoice->client_id !== $consumer->client_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json([
            'data' => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'status' => $invoice->status,
                'total_amount' => $invoice->total_amount,
                'client_id' => $invoice->client_id,
            ]
        ]);
    }
}
