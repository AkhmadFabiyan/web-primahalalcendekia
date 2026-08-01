<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\ApiIdempotencyKey;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class ApiIdempotencyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only apply to POST/PUT/PATCH methods
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            return $next($request);
        }

        $idempotencyKey = $request->header('Idempotency-Key');

        if (!$idempotencyKey) {
            // Depending on strictness, we might require it or let it pass
            // For now, if no key, we don't enforce idempotency
            return $next($request);
        }

        $consumerId = $request->user()?->id;

        if (!$consumerId) {
            return $next($request);
        }

        $requestHash = md5(json_encode($request->all()));

        $existingKey = ApiIdempotencyKey::where('api_consumer_id', $consumerId)
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($existingKey) {
            // Check if request body changed
            if ($existingKey->request_hash !== $requestHash) {
                return response()->json([
                    'message' => 'Idempotency key already used with different payload.',
                    'code' => 'IDEMPOTENCY_CONFLICT',
                ], 409);
            }

            // Return cached response if processing completed
            if ($existingKey->response_status) {
                return response()->json($existingKey->response_body, $existingKey->response_status);
            }
            
            // If it's still processing (no response status yet)
            return response()->json([
                'message' => 'Request is still processing.',
                'code' => 'IDEMPOTENCY_PROCESSING',
            ], 409);
        }

        // Create new idempotency record
        $record = ApiIdempotencyKey::create([
            'api_consumer_id' => $consumerId,
            'method' => $request->method(),
            'endpoint' => $request->path(),
            'idempotency_key' => $idempotencyKey,
            'request_hash' => $requestHash,
            'expires_at' => Carbon::now()->addHours(24),
        ]);

        // Process request
        $response = $next($request);

        // Update record with response
        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $record->update([
                'response_status' => $response->status(),
                'response_body' => $response->getData(true),
            ]);
        }

        return $response;
    }
}
