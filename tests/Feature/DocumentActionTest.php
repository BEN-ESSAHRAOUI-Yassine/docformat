<?php

use App\Enums\ActionOrigin;
use App\Enums\ActionType;
use App\Enums\Reversibility;
use App\Models\Document;
use App\Models\DocumentAction;
use App\Models\Project;
use App\Models\User;
use App\Services\ActionLogger;

it('records a document action with correct fields', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);

    $action = app(ActionLogger::class)->record($document, [
        'action_type' => ActionType::HeadingAssigned->value,
        'element_type' => 'DetectedElement',
        'element_id' => 42,
        'origin' => ActionOrigin::Manual->value,
        'old_value' => ['type' => 'paragraph'],
        'new_value' => ['type' => 'heading'],
    ]);

    expect($action)->toBeInstanceOf(DocumentAction::class);
    expect($action->document_id)->toBe($document->id);
    expect($action->action_type)->toBe(ActionType::HeadingAssigned->value);
    expect($action->origin)->toBe(ActionOrigin::Manual);
    expect($action->reversibility)->toBe(Reversibility::Full);
    expect($action->old_value)->toBe(['type' => 'paragraph']);
});

it('records external actions as non-reversible', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);

    $action = app(ActionLogger::class)->recordExternal($document, [
        'action_type' => 'similarity_analyzed',
    ]);

    expect($action->reversibility)->toBe(Reversibility::None);
    expect($action->isReversible())->toBeFalse();
});

it('returns actions filtered by type and origin', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);

    DocumentAction::factory()->create(['document_id' => $document->id, 'action_type' => ActionType::HeadingAssigned->value]);
    DocumentAction::factory()->create(['document_id' => $document->id, 'action_type' => ActionType::Merged->value]);

    $types = $document->actions()->ofType(ActionType::HeadingAssigned->value)->get();

    expect($types)->toHaveCount(1);
    expect($types->first()->action_type)->toBe(ActionType::HeadingAssigned->value);
});

it('filters actions by date range', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);

    DocumentAction::factory()->create(['document_id' => $document->id, 'created_at' => now()->subDays(5)]);
    DocumentAction::factory()->create(['document_id' => $document->id, 'created_at' => now()]);

    $actions = $document->actions()->betweenDates(now()->subDay(), now()->addDay())->get();

    expect($actions)->toHaveCount(1);
});
