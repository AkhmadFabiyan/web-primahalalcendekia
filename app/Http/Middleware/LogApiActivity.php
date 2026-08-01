<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Str;

class LogApiActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = (string) Str::uuid();
        $request->headers->set('X-Request-ID', $requestId);
        
        $startTime = microtime(true);

        $response = $next($request);

        $executionTime = round((microtime(true) - $startTime) * 1000); // in ms

        $token = $request->bearerToken();
        $tokenId = 'none';
        if ($token) {
            // Note: In Sanctum, the token is ID|HASH. We can extract ID.
            $parts = explode('|', $token);
            $tokenId = count($parts) === 2 ? $parts[0] : 'hashed';
        }

        // Add X-Request-ID to response if it's a JsonResponse
        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $data = $response->getData(true);
            if (is_array($data)) {
                $data['request_id'] = $requestId;
                $response->setData($data);
            }
        }

        $response->headers->set('X-Request-ID', $requestId);

        \Log::channel('single')->info('API Request', [
            'request_id' => $requestId,
            'consumer_id' => $request->user()?->id,
            'token_id' => $tokenId,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => $response->status(),
            'execution_time_ms' => $executionTime,
        ]);

        return $response;
    }
}
