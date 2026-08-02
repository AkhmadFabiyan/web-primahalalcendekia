<?php

use App\Http\Controllers\DocumentDownloadController;
use App\Http\Controllers\ExportDownloadController;
use App\Http\Controllers\Payments\InvoicePrintController;
use App\Http\Controllers\Payments\PaymentProofController;
use App\Http\Controllers\PublicSiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicSiteController::class, 'home'])->name('public.home');
Route::get('/klien', [PublicSiteController::class, 'clients'])->name('public.clients');
Route::get('/sitemap.xml', [PublicSiteController::class, 'sitemap'])->name('public.sitemap');

// Arahkan akses /login publik ke halaman login Filament
Route::redirect('/login', '/dashboard/login')->name('login');

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/payments/invoices/{id}/print', InvoicePrintController::class)->name('payments.invoices.print');
    Route::get('/payments/{payment}/proof', [PaymentProofController::class, 'download'])->name('payments.proof.download');
    Route::get('/documents/{document}/download', [DocumentDownloadController::class, 'download'])->name('documents.download');
    Route::get('/download/export/{path}', [ExportDownloadController::class, 'download'])->name('download.export');
});
