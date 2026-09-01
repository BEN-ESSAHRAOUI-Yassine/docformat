<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStyleProfileRequest;
use App\Http\Requests\UpdateStyleProfileRequest;
use App\Http\Resources\StyleProfileResource;
use App\Models\StyleProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class StyleProfileController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $profiles = StyleProfile::forUser($request->user()->id)
            ->with('user')
            ->orderByDesc('is_system')
            ->orderByDesc('updated_at')
            ->paginate(20);

        return StyleProfileResource::collection($profiles);
    }

    public function store(StoreStyleProfileRequest $request): JsonResponse
    {
        $profile = StyleProfile::create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
            'version' => 1,
        ]);

        return response()->json(new StyleProfileResource($profile->load('user')), 201);
    }

    public function show(string $id): StyleProfileResource
    {
        $profile = StyleProfile::findOrFail($id);

        Gate::authorize('view', [$profile]);

        return new StyleProfileResource($profile->load('user'));
    }

    public function update(UpdateStyleProfileRequest $request, string $id): JsonResponse
    {
        $profile = StyleProfile::findOrFail($id);
        Gate::authorize('update', [$profile]);

        $profile->update([
            ...$request->validated(),
            'version' => $profile->version + 1,
        ]);

        return response()->json(new StyleProfileResource($profile->fresh('user')));
    }

    public function destroy(string $id): JsonResponse
    {
        $profile = StyleProfile::findOrFail($id);
        Gate::authorize('delete', [$profile]);

        $profile->delete();

        return response()->json(null, 204);
    }

    public function export(string $id): JsonResponse
    {
        $profile = StyleProfile::findOrFail($id);
        Gate::authorize('view', [$profile]);

        return response()->json([
            'name' => $profile->name,
            'description' => $profile->description,
            'type' => $profile->type,
            'language' => $profile->language,
            'version' => $profile->version,
            'rules' => $profile->rules,
        ]);
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'profile' => 'required|json',
        ]);

        $data = json_decode($request->input('profile'), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json(['message' => 'Invalid JSON format'], 422);
        }

        $profile = StyleProfile::create([
            'user_id' => $request->user()->id,
            'name' => $data['name'] ?? 'Imported Profile',
            'description' => $data['description'] ?? null,
            'type' => $data['type'] ?? 'custom',
            'language' => $data['language'] ?? 'fr-FR',
            'version' => 1,
            'rules' => $data['rules'] ?? [],
        ]);

        return response()->json(new StyleProfileResource($profile->load('user')), 201);
    }
}
