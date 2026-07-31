<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/payments/invoices/{id}/print', \App\Http\Controllers\Payments\InvoicePrintController::class)->name('payments.invoices.print');
    Route::get('/payments/{payment}/proof', [\App\Http\Controllers\Payments\PaymentProofController::class, 'download'])->name('payments.proof.download');
    Route::get('/documents/{document}/download', [\App\Http\Controllers\DocumentDownloadController::class, 'download'])->name('documents.download');
});
