<?php

namespace App\Http\Controllers;

use App\Http\Requests\AnalyzeStyleRequest;
use App\Jobs\AnalyzeStyleJob;
use App\Models\Document;
use App\Models\StyleProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class StyleAnalysisController extends Controller
{
    public function store(AnalyzeStyleRequest $request, Document $document): JsonResponse
    {
        Gate::authorize('trigger', [$document]);

        $profile = StyleProfile::findOrFail($request->validated('profile_id'));

        AnalyzeStyleJob::dispatch($document, $profile);

        return response()->json([
            'message' => 'Style analysis started',
            'profile_id' => $profile->id,
        ], 202);
    }

    public function index(Request $request, Document $document): JsonResponse
    {
        Gate::authorize('viewAny', [Document::class, $document->project]);

        $analysis = $document->latestAnalysis;

        if (! $analysis) {
            return response()->json(['message' => 'No analysis found'], 404);
        }

        $query = $analysis->violations();

        if ($request->has('severity')) {
            $query->forSeverity($request->input('severity'));
        }

        if ($request->has('category')) {
            $query->forCategory($request->input('category'));
        }

        return response()->json($query->get());
    }
}
