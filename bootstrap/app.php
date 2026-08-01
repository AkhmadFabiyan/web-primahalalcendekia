<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

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

        $exceptions->render(function (\Throwable $e, Request $request) {
            $isForbidden = false;

            if ($e instanceof \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException || $e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                $isForbidden = true;
            } elseif ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface && $e->getStatusCode() === 403) {
                $isForbidden = true;
            }

            if ($isForbidden) {
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json(['message' => 'Not Found.'], 404);
                }

                return new \Illuminate\Http\RedirectResponse('/admin');
            }
        });
    })->create();

