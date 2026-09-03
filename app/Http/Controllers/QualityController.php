<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\Quality\QualityEngine;
use Illuminate\Http\JsonResponse;

class QualityController extends Controller
{
    public function __construct(
        private QualityEngine $engine,
    ) {}

    public function show(Document $document): JsonResponse
    {
        $this->authorize('view', $document);

        return response()->json($this->engine->score($document));
    }
}
