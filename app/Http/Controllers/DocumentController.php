<?php

namespace App\Http\Controllers;

use App\Enums\ActionOrigin;
use App\Enums\ActionType;
use App\Enums\Reversibility;
use App\Http\Requests\StoreDocumentRequest;
use App\Http\Resources\DocumentResource;
use App\Jobs\AnalyzeDocumentJob;
use App\Models\Document;
use App\Models\Project;
use App\Services\ActionLogger;
use App\Services\DocumentUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DocumentController extends Controller
{
    public function __construct(
        private DocumentUploadService $uploadService,
    ) {}

    public function index(Request $request, Project $project): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [Document::class, $project]);

        $documents = $project->documents()
            ->with('currentVersion')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return DocumentResource::collection($documents);
    }

    public function store(StoreDocumentRequest $request, Project $project): JsonResponse
    {
        $file = $request->file('file');

        if (! $this->uploadService->validateDocx($file)) {
            return response()->json([
                'message' => 'The file is not a valid DOCX document.',
            ], 422);
        }

        $hash = $this->uploadService->computeHash($file);

        $existing = $this->uploadService->findDuplicate($hash);
        if ($existing) {
            return response()->json([
                'message' => 'Duplicate document detected.',
                'data' => new DocumentResource($existing->load('currentVersion')),
            ], 200);
        }

        $filePath = $this->uploadService->storeOriginal($file, $hash);

        $document = $this->uploadService->createDocument([
            'name' => $request->string('name', $file->getClientOriginalName()),
            'original_filename' => $file->getClientOriginalName(),
            'project_id' => $project->id,
            'status' => 'uploaded',
            'file_hash' => $hash,
        ]);

        $this->uploadService->createVersion(
            $document,
            $filePath,
            $file,
            $request->user()->id,
        );

        $document->load('currentVersion');

        AnalyzeDocumentJob::dispatch($document);

        return (new DocumentResource($document))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Project $project, Document $document): DocumentResource
    {
        $this->authorize('view', [$document, $project]);

        return new DocumentResource($document->load('currentVersion', 'versions'));
    }

    public function destroy(Request $request, Project $project, Document $document): JsonResponse
    {
        $this->authorize('delete', [$document, $project]);

        app(ActionLogger::class)->record($document, [
            'action_type' => ActionType::SecurityEvent->value,
            'origin' => ActionOrigin::Manual,
            'reversibility' => Reversibility::None,
            'payload' => ['event' => 'document_deleted'],
        ]);

        $document->delete();

        return response()->json(['message' => 'Document deleted.']);
    }
}
