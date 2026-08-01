<?php

namespace App\Modules\Projects\Services;

use App\Modules\Projects\Enums\ArchiveVisibility;
use App\Modules\Projects\Enums\ProjectArchiveStatus;
use App\Modules\Projects\Models\ProjectArchive;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class ProjectArchiveZipService
{
    public function createZip(ProjectArchive $archive, ArchiveVisibility $visibility): void
    {
        // 1. Mark as processing
        $archive->update(['status' => ProjectArchiveStatus::PROCESSING]);

        try {
            $zip = new ZipArchive();
            $zipFileName = 'archive_' . Str::random(16) . '.zip';
            
            // Simpan di lokasi sementara/private
            $zipPath = Storage::disk('local')->path('archives/' . $zipFileName);
            
            if (!Storage::disk('local')->exists('archives')) {
                Storage::disk('local')->makeDirectory('archives');
            }

            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \Exception('Could not create ZIP file.');
            }

            $items = $archive->items();
            if ($visibility === ArchiveVisibility::CLIENT) {
                $items->where('visibility', ArchiveVisibility::CLIENT);
            }
            $items = $items->get();

            $manifestData = [
                'client_id' => $archive->project->client->client_id ?? $archive->project->client->id,
                'client_name' => $archive->project->client->name,
                'project_status' => $archive->project->status,
                'closed_at' => $archive->project->status->value === 'COMPLETED' ? $archive->project->completed_at : $archive->project->cancelled_at,
                'archive_version' => $archive->archive_version,
                'generated_at' => $archive->generated_at,
                'files' => []
            ];

            $usedNames = [];

            foreach ($items as $item) {
                if (!$item->media_id) continue;
                
                $media = \Spatie\MediaLibrary\MediaCollections\Models\Media::find($item->media_id);
                if (!$media || !file_exists($media->getPath())) continue;

                // Create safe name
                $baseName = Str::slug($item->document_name);
                if ($item->document_version) {
                    $baseName .= '-' . Str::slug($item->document_version);
                }
                
                $ext = pathinfo($media->file_name, PATHINFO_EXTENSION);
                if (!$ext) $ext = 'pdf'; // fallback

                $fileName = "{$baseName}.{$ext}";
                
                // Handle duplicates
                $counter = 1;
                $uniqueFileName = $fileName;
                while (isset($usedNames[$item->category][$uniqueFileName])) {
                    $uniqueFileName = "{$baseName}-{$counter}.{$ext}";
                    $counter++;
                }
                $usedNames[$item->category][$uniqueFileName] = true;

                $zipInternalPath = "{$item->category}/{$uniqueFileName}";

                $zip->addFile($media->getPath(), $zipInternalPath);

                $manifestData['files'][] = [
                    'path' => $zipInternalPath,
                    'category' => $item->category,
                    'document_version' => $item->document_version,
                    'file_size' => $item->file_size,
                    'checksum_sha256' => $item->checksum_sha256,
                ];
            }

            // Tambahkan manifest
            $zip->addFromString('manifest.json', json_encode($manifestData, JSON_PRETTY_PRINT));

            $zip->close();

            // Simpan zip ke media collection
            $collection = $visibility === ArchiveVisibility::CLIENT ? 'archive-client' : 'archive-internal';
            
            $archive->addMedia($zipPath)
                ->toMediaCollection($collection);

            $archive->update(['status' => ProjectArchiveStatus::READY]);

        } catch (\Exception $e) {
            $archive->update(['status' => ProjectArchiveStatus::FAILED]);
            throw $e;
        }
    }
}
