<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\LeadController;
use App\Http\Controllers\Api\V1\ClientController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\PaymentController;

Route::prefix('v1')
    ->middleware(['auth:sanctum', \App\Http\Middleware\LogApiActivity::class])
    ->group(function () {
        
        // Leads
        Route::post('/leads', [LeadController::class, 'store'])
            ->middleware(['throttle:api-write', \App\Http\Middleware\ApiIdempotencyMiddleware::class]);
            
        Route::get('/leads/{externalReference}', [LeadController::class, 'show'])
            ->middleware('throttle:api-read');

        // Clients
        Route::get('/clients/{clientId}', [ClientController::class, 'show'])
            ->middleware('throttle:api-read');
            
        Route::get('/clients/{clientId}/progress', [ClientController::class, 'progress'])
            ->middleware('throttle:api-read');

        // Invoices
        Route::get('/invoices', [InvoiceController::class, 'index'])
            ->middleware('throttle:api-read');
            
        Route::get('/invoices/{invoiceNumber}', [InvoiceController::class, 'show'])
            ->middleware('throttle:api-read');

        // Payments
        Route::get('/payments', [PaymentController::class, 'index'])
            ->middleware('throttle:api-read');
            
        Route::get('/payments/{paymentNumber}', [PaymentController::class, 'show'])
            ->middleware('throttle:api-read');
});
