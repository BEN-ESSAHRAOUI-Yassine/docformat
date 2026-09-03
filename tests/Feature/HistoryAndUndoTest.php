<?php

use App\Enums\ActionType;
use App\Models\DetectedElement;
use App\Models\Document;
use App\Models\DocumentAction;
use App\Models\Project;
use App\Models\User;
use App\Services\HistoryService;
use Illuminate\Support\Str;

it('undoes a heading assignment action', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);
    $element = DetectedElement::factory()->create([
        'document_id' => $document->id,
        'type' => 'paragraph',
        'heading_level' => null,
    ]);

    $element->update(['type' => 'heading', 'heading_level' => 2]);

    DocumentAction::create([
        'document_id' => $document->id,
        'action_type' => ActionType::HeadingAssigned->value,
        'element_type' => 'DetectedElement',
        'element_id' => $element->id,
        'old_value' => [
            'model' => 'DetectedElement',
            'id' => $element->id,
            'attributes' => ['type' => 'paragraph', 'heading_level' => null],
        ],
        'reversibility' => 'full',
    ]);

    $history = app(HistoryService::class);
    $action = $history->undo($document);

    expect($action)->not->toBeNull();
    $element->refresh();
    expect($element->type)->toBe('paragraph');
    expect($element->heading_level)->toBeNull();
    expect($action->undone_at)->not->toBeNull();
});

it('redoes an undone action', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);
    $element = DetectedElement::factory()->create([
        'document_id' => $document->id,
        'type' => 'heading',
        'heading_level' => 2,
    ]);

    DocumentAction::create([
        'document_id' => $document->id,
        'action_type' => ActionType::HeadingAssigned->value,
        'element_type' => 'DetectedElement',
        'element_id' => $element->id,
        'old_value' => [
            'model' => 'DetectedElement',
            'id' => $element->id,
            'attributes' => ['type' => 'paragraph', 'heading_level' => null],
        ],
        'new_value' => [
            'model' => 'DetectedElement',
            'id' => $element->id,
            'attributes' => ['type' => 'heading', 'heading_level' => 2],
        ],
        'reversibility' => 'full',
    ]);

    $history = app(HistoryService::class);
    $history->undo($document);

    $action = $history->redo($document);

    expect($action)->not->toBeNull();
    $element->refresh();
    expect($element->type)->toBe('heading');
    expect($element->heading_level)->toBe(2);
});

it('does not undo a non-reversible action', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);

    DocumentAction::factory()->nonReversible()->create([
        'document_id' => $document->id,
        'action_type' => 'similarity_analyzed',
    ]);

    $history = app(HistoryService::class);
    $action = $history->undo($document);

    expect($action)->toBeNull();
});

it('undoes a whole bulk via undoBulk', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);
    $bulkId = (string) Str::uuid();

    $one = DetectedElement::factory()->create(['document_id' => $document->id, 'type' => 'heading', 'heading_level' => 1]);
    $two = DetectedElement::factory()->create(['document_id' => $document->id, 'type' => 'heading', 'heading_level' => 1]);

    foreach ([$one, $two] as $element) {
        DocumentAction::create([
            'document_id' => $document->id,
            'action_type' => ActionType::HeadingAssigned->value,
            'element_type' => 'DetectedElement',
            'element_id' => $element->id,
            'old_value' => [
                'model' => 'DetectedElement',
                'id' => $element->id,
                'attributes' => ['heading_level' => null],
            ],
            'reversibility' => 'full',
            'bulk_id' => $bulkId,
        ]);
    }

    $history = app(HistoryService::class);
    $actions = $history->undoBulk($document, $bulkId);

    expect($actions)->toHaveCount(2);
    expect($one->fresh()->heading_level)->toBeNull();
    expect($two->fresh()->heading_level)->toBeNull();
});

it('returns actions and undo/redo endpoints for a document', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);
    $token = $user->createToken('test-token')->plainTextToken;

    DocumentAction::factory()->nonReversible()->count(3)->create(['document_id' => $document->id]);

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson("/api/v1/documents/{$document->id}/actions")
        ->assertOk()
        ->assertJsonStructure(['document_id', 'actions']);

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson("/api/v1/documents/{$document->id}/undo")
        ->assertStatus(422);

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson("/api/v1/documents/{$document->id}/redo")
        ->assertStatus(422);
});

it('rejects history access for unowned document', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $otherUser->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);
    $token = $user->createToken('test-token')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson("/api/v1/documents/{$document->id}/actions")
        ->assertForbidden();
});
