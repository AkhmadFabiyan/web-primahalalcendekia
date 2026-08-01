<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Filament\Facades\Filament;

class RedirectClientFromInternalRoutes
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if ($user && $user->isClient()) {
            // Check if the route is a Filament route
            $route = $request->route();
            if ($route && str_starts_with($route->getName(), 'filament.admin.')) {
                // Allow the dashboard route
                if ($route->getName() === 'filament.admin.pages.dashboard') {
                    return $next($request);
                }

                // Redirect clients trying to access other filament pages to the dashboard safely
                return redirect()->route('filament.admin.pages.dashboard');
            }
        }

        return $next($request);
    }
}
