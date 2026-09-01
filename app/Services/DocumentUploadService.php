<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Http\UploadedFile;

class DocumentUploadService
{
    public function validateDocx(UploadedFile $file): bool
    {
        if ($file->getMimeType() !== 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
            return false;
        }

        $tmpPath = $file->getRealPath();
        if (! $tmpPath || ! @zip_open($tmpPath)) {
            return false;
        }

        return true;
    }

    public function computeHash(UploadedFile $file): string
    {
        return hash_file('sha256', $file->getRealPath());
    }

    public function findDuplicate(string $hash): ?Document
    {
        return Document::where('file_hash', $hash)->first();
    }

    public function storeOriginal(UploadedFile $file, string $hash): string
    {
        $datePath = now()->format('Y/m/d');
        $filename = $hash.'.'.$file->getClientOriginalExtension();

        return $file->storeAs("originals/{$datePath}", $filename, 'docformat');
    }

    public function createDocument(array $data): Document
    {
        return Document::create($data);
    }

    public function createVersion(Document $document, string $filePath, UploadedFile $file, int $userId): DocumentVersion
    {
        $versionNumber = $document->versions()->max('version_number') ?? 0;

        $version = $document->versions()->create([
            'version_number' => $versionNumber + 1,
            'file_path' => $filePath,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'uploaded_by' => $userId,
        ]);

        $document->update(['current_version_id' => $version->id]);

        return $version;
    }
}
