<?php

use App\Enums\ActionType;
use App\Models\DetectedElement;
use App\Models\Document;
use App\Models\DocumentAction;
use App\Models\Project;
use App\Models\User;
use App\Services\PageBreakService;

it('inserts a user page break before a target element', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);
    $target = DetectedElement::factory()->create([
        'document_id' => $document->id,
        'type' => 'heading',
        'element_index' => 5,
    ]);

    $element = app(PageBreakService::class)->insertBefore($document, $target, 'chapter');

    expect($element->type)->toBe('page_break');
    expect($element->metadata['origin'])->toBe('user');
    expect($element->metadata['context'])->toBe('chapter');
    expect($element->metadata['before_element_id'])->toBe($target->id);

    expect(DocumentAction::forDocument($document->id)->ofType(ActionType::PageBreakAdded->value)->count())->toBe(1);
});

it('removes only user page breaks', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);

    $userBreak = DetectedElement::factory()->create([
        'document_id' => $document->id,
        'type' => 'page_break',
        'metadata' => ['origin' => 'user'],
    ]);

    $service = app(PageBreakService::class);
    expect($service->remove($document, $userBreak))->toBeTrue();
    expect($userBreak->fresh())->toBeNull();
});

it('refuses to remove automated page breaks', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);

    $autoBreak = DetectedElement::factory()->create([
        'document_id' => $document->id,
        'type' => 'page_break',
        'metadata' => [],
    ]);

    expect(app(PageBreakService::class)->remove($document, $autoBreak))->toBeFalse();
    expect($autoBreak->fresh())->not->toBeNull();
});

it('exposes page break create and delete endpoints', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);
    $target = DetectedElement::factory()->create([
        'document_id' => $document->id,
        'type' => 'figure',
        'element_index' => 3,
    ]);
    $token = $user->createToken('test-token')->plainTextToken;

    $create = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson("/api/v1/documents/{$document->id}/page-breaks", [
            'context' => 'figure',
            'element_id' => $target->id,
        ])
        ->assertStatus(201);
    $elementId = $create->json('id');

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->deleteJson("/api/v1/documents/{$document->id}/page-breaks/{$elementId}")
        ->assertOk();
});

it('rejects page break access for non-owner', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $otherUser->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);
    $token = $user->createToken('test-token')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson("/api/v1/documents/{$document->id}/page-breaks", ['context' => 'chapter'])
        ->assertForbidden();
});
