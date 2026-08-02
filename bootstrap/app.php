<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        api: __DIR__.'/../routes/api.php',
        health: '/up',
    )
    ->withEvents(discover: [
        __DIR__.'/../app/Modules/Projects/Listeners',
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (Throwable $e, Request $request) {
            $isForbidden = false;

            if ($e instanceof AccessDeniedHttpException || $e instanceof AuthorizationException) {
                $isForbidden = true;
            } elseif ($e instanceof HttpExceptionInterface && $e->getStatusCode() === 403) {
                $isForbidden = true;
            }

            if ($isForbidden) {
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json(['message' => 'Not Found.'], 404);
                }

                if ($request->route()?->getName() === 'filament.admin.pages.dashboard') {
                    return response('Forbidden', 403);
                }

                return new RedirectResponse(
                    route('filament.admin.pages.dashboard'),
                );
            }
        });
    })->create();
