<?php

namespace App\Http\Controllers;

use App\Jobs\ExportJob;
use App\Models\Batch;
use App\Models\Document;
use App\Models\Project;
use App\Services\BatchProcessingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class BatchController extends Controller
{
    public function __construct(
        private BatchProcessingService $batchService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $batches = Batch::with(['project', 'items'])
            ->whereHas('project', fn ($q) => $q->where('owner_id', $request->user()->id))
            ->orderByDesc('id')
            ->get();

        return response()->json(['batches' => $batches]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'project_id' => ['required', 'exists:projects,id'],
            'document_ids' => ['required', 'array', 'min:1'],
            'document_ids.*' => ['integer', 'exists:documents,id'],
            'style_profile_id' => ['nullable', 'exists:style_profiles,id'],
        ]);

        $project = Project::findOrFail($data['project_id']);

        $this->authorize('view', $project);

        $documents = Document::whereIn('id', $data['document_ids'])->get();

        foreach ($documents as $document) {
            if ($document->project_id !== $project->id) {
                return response()->json(['message' => 'Batch documents must belong to the project.'], 422);
            }
        }

        $batch = $this->batchService->create(
            $project->id,
            $data['name'],
            $data['document_ids'],
            $data['style_profile_id'] ?? null,
            $request->user()?->id,
        );

        return response()->json(['batch' => $batch->load('items')], 201);
    }

    public function show(Request $request, Batch $batch): JsonResponse
    {
        $this->authorize('view', $batch->project);

        return response()->json(['batch' => $batch->load(['items', 'project'])]);
    }

    public function items(Request $request, Batch $batch): JsonResponse
    {
        $this->authorize('view', $batch->project);

        return response()->json([
            'batch_id' => $batch->id,
            'items' => $batch->items()->with('document')->get(),
        ]);
    }

    public function export(Request $request, Batch $batch): JsonResponse
    {
        $this->authorize('view', $batch->project);

        foreach ($batch->items()->with('document')->get() as $item) {
            ExportJob::dispatch($item->document, $request->user()?->id);
        }

        return response()->json([
            'message' => 'Batch export started.',
            'batch_id' => $batch->id,
            'count' => $batch->items()->count(),
        ]);
    }

    public function exportDownload(Request $request, Batch $batch): BinaryFileResponse
    {
        $this->authorize('view', $batch->project);

        $zipPath = Storage::disk('docformat')->path('exports/batch-'.$batch->id.'.zip');

        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $count = 0;
        foreach ($batch->items()->with('document')->get() as $item) {
            $version = $item->document->currentVersion;
            if (! $version || $version->kind !== 'export') {
                continue;
            }

            $file = Storage::disk('docformat')->path($version->file_path);
            if (file_exists($file)) {
                $zip->addFile($file, $item->document->name.'.docx');
                $count++;
            }
        }

        $zip->close();

        abort_if($count === 0, 404, 'No exported documents available.');

        return response()->download($zipPath, 'batch-'.$batch->id.'.zip');
    }
}
