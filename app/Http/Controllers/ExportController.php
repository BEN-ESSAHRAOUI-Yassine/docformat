<?php

namespace App\Http\Controllers;

use App\Enums\ActionOrigin;
use App\Enums\ActionType;
use App\Enums\Reversibility;
use App\Jobs\ExportJob;
use App\Models\Document;
use App\Services\ActionLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends Controller
{
    public function __construct(
        private ActionLogger $actionLogger,
    ) {}

    public function store(Request $request, Document $document): JsonResponse
    {
        $this->authorize('view', $document);

        $this->actionLogger->record($document, [
            'action_type' => ActionType::SecurityEvent->value,
            'origin' => ActionOrigin::Manual,
            'reversibility' => Reversibility::None,
            'payload' => ['event' => 'export_triggered'],
        ]);

        ExportJob::dispatch($document, $request->user()?->id);

        return response()->json([
            'message' => 'Export started',
            'status' => 'exporting',
        ], 202);
    }

    public function download(Document $document): JsonResponse
    {
        $this->authorize('view', $document);

        $version = $document->currentVersion;

        if (! $version || $version->kind !== 'export') {
            return response()->json(['message' => 'No export available to download.'], 404);
        }

        $path = Storage::disk('docformat')->path($version->file_path);

        if (! file_exists($path)) {
            return response()->json(['message' => 'Export file not found.'], 404);
        }

        return response()->json(['url' => $version->file_path])->header('Content-Type', 'application/json');
    }

    public function stream(Document $document): BinaryFileResponse
    {
        $this->authorize('view', $document);

        $version = $document->currentVersion;

        abort_if(! $version || $version->kind !== 'export', 404);

        $path = Storage::disk('docformat')->path($version->file_path);

        abort_if(! file_exists($path), 404);

        return response()->download($path, $document->name.'.docx');
    }
}
