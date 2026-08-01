<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\Clients\Models\Client;
use Illuminate\Http\JsonResponse;

class ClientController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/clients/{clientId}",
     *     summary="Get client details",
     *     tags={"Clients"}
     * )
     */
    public function show(string $clientId, Request $request): JsonResponse
    {
        $client = Client::where('client_id', $clientId)->orWhere('id', $clientId)->first();

        if (!$client) {
            return response()->json(['message' => 'Client not found'], 404);
        }

        $consumer = $request->user();
        if ($consumer->type === 'PARTNER' && $client->partner_id !== $consumer->partner_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        if ($consumer->type === 'CLIENT' && $client->id !== $consumer->client_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json([
            'data' => [
                'id' => $client->id,
                'client_id' => $client->client_id,
                'company_name' => $client->company_name,
                'status' => $client->status,
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/clients/{clientId}/progress",
     *     summary="Get client progress",
     *     tags={"Clients"}
     * )
     */
    public function progress(string $clientId, Request $request): JsonResponse
    {
        $client = Client::where('client_id', $clientId)->orWhere('id', $clientId)->first();

        if (!$client) {
            return response()->json(['message' => 'Client not found'], 404);
        }

        $consumer = $request->user();
        if ($consumer->type === 'PARTNER' && $client->partner_id !== $consumer->partner_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        if ($consumer->type === 'CLIENT' && $client->id !== $consumer->client_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Logic for progress from domain service or relation
        return response()->json([
            'data' => [
                'client_id' => $client->client_id,
                'progress_percentage' => 50, // mock progress
                'current_stage' => 'Document Verification' // mock stage
            ]
        ]);
    }
}
