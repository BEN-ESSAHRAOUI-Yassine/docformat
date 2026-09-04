<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrivacyController extends Controller
{
    public function exportData(Request $request): JsonResponse
    {
        $user = $request->user();

        $projects = $user->projects()->with('documents')->get()->map(function (Project $project) {
            return [
                'id' => $project->id,
                'name' => $project->name,
                'description' => $project->description,
                'documents' => $project->documents->map(fn ($doc) => [
                    'id' => $doc->id,
                    'name' => $doc->name,
                    'original_filename' => $doc->original_filename,
                    'status' => $doc->status->value ?? $doc->status,
                    'created_at' => $doc->created_at,
                ]),
            ];
        });

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'projects' => $projects,
        ]);
    }

    public function deleteData(Request $request): JsonResponse
    {
        $user = $request->user();

        $projects = $user->projects()->with('documents')->get();

        foreach ($projects as $project) {
            foreach ($project->documents as $document) {
                $document->forceDelete();
            }
            $project->forceDelete();
        }

        return response()->json(['message' => 'Your data has been deleted.']);
    }
}
