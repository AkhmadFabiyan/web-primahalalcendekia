<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportDownloadController extends Controller
{
    public function download($path)
    {
        $decodedPath = base64_decode($path);
        
        if (!Storage::disk('local')->exists($decodedPath)) {
            abort(404, 'File not found or expired.');
        }

        $user = auth()->user();
        if (!$user) {
            abort(403);
        }

        // Simplistic check for testing: only report viewers can download
        if (!$user->can('report.view') && !$user->hasRole('super_admin')) {
            abort(403, 'Unauthorized to download this file.');
        }

        return response()->download(Storage::disk('local')->path($decodedPath));
    }
}
