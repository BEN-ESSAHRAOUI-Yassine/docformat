<?php

namespace App\Http\Controllers;

use App\Http\Resources\QualityReportResource;
use App\Models\Document;
use App\Services\QualityReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        private QualityReportService $reportService,
    ) {}

    public function show(Document $document): JsonResponse
    {
        $this->authorize('view', $document);

        $report = $this->reportService->latest($document);

        if (! $report) {
            $report = $this->reportService->generate($document);
        }

        return response()->json(new QualityReportResource($report));
    }

    public function generate(Request $request, Document $document): JsonResponse
    {
        $this->authorize('view', $document);

        $report = $this->reportService->generate($document);

        return response()->json(new QualityReportResource($report), 201);
    }
}
