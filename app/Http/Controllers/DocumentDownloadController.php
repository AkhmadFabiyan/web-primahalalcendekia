<?php

namespace App\Http\Controllers;

use App\Modules\Documents\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DocumentDownloadController extends Controller
{
    public function download(Request $request, Document $document)
    {
        if (! Gate::allows('view', $document)) {
            abort(403, 'Anda tidak memiliki akses ke dokumen ini.');
        }

        $media = $document->getFirstMedia('document-file');
        
        if (!$media) {
            abort(404, 'File tidak ditemukan.');
        }

        return response()->download($media->getPath(), $media->file_name);
    }
}
