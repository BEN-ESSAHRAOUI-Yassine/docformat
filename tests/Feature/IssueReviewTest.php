<?php

use App\Enums\ActionType;
use App\Enums\DocumentStatus;
use App\Enums\IssueDecision;
use App\Models\Document;
use App\Models\DocumentAction;
use App\Models\DocumentIssue;
use App\Models\Project;
use App\Models\User;
use App\Services\ReviewStatusService;

it('accepts an issue and records an action', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);
    $issue = DocumentIssue::factory()->create(['document_id' => $document->id]);
    $token = $user->createToken('test-token')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson("/api/v1/documents/{$document->id}/issues/{$issue->id}/accept")
        ->assertOk()
        ->assertJsonPath('decision', 'accepted');

    expect($issue->fresh()->decision)->toBe(IssueDecision::Accepted);
    expect(DocumentAction::forDocument($document->id)->ofType(ActionType::IssueAccepted->value)->count())->toBe(1);
});

it('ignores an issue with a reason', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);
    $issue = DocumentIssue::factory()->create(['document_id' => $document->id]);
    $token = $user->createToken('test-token')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson("/api/v1/documents/{$document->id}/issues/{$issue->id}/ignore", ['reason' => 'Not relevant'])
        ->assertOk()
        ->assertJsonPath('decision', 'ignored')
        ->assertJsonPath('ignored_reason', 'Not relevant');

    expect($issue->fresh()->decision)->toBe(IssueDecision::Ignored);
});

it('rejects an issue and records an action', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);
    $issue = DocumentIssue::factory()->create(['document_id' => $document->id]);
    $token = $user->createToken('test-token')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson("/api/v1/documents/{$document->id}/issues/{$issue->id}/reject")
        ->assertOk()
        ->assertJsonPath('decision', 'rejected');

    expect(DocumentAction::forDocument($document->id)->ofType(ActionType::IssueRejected->value)->count())->toBe(1);
});

it('rejects non-owner access to decisions', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $otherUser->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);
    $issue = DocumentIssue::factory()->create(['document_id' => $document->id]);
    $token = $user->createToken('test-token')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson("/api/v1/documents/{$document->id}/issues/{$issue->id}/accept")
        ->assertForbidden();
});

it('rejects bulk and logs each action with a shared bulk id', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);
    $token = $user->createToken('test-token')->plainTextToken;

    DocumentIssue::factory()->count(3)->create(['document_id' => $document->id, 'decision' => IssueDecision::Pending->value]);

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson("/api/v1/documents/{$document->id}/issues/bulk", ['decision' => 'accept']);

    $response->assertOk()->assertJsonPath('count', 3);

    $bulkActions = DocumentAction::forDocument($document->id)->ofType(ActionType::IssueAccepted->value)->get();
    expect($bulkActions)->toHaveCount(3);
    expect($bulkActions->pluck('bulk_id')->unique())->toHaveCount(1);
});

it('transitions document to review required and ready for export', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);

    $issue = DocumentIssue::factory()->create(['document_id' => $document->id, 'decision' => IssueDecision::Pending->value]);

    $service = app(ReviewStatusService::class);
    $service->refresh($document);
    expect($document->fresh()->status)->toBe(DocumentStatus::REVIEW_REQUIRED);

    $issue->update(['decision' => IssueDecision::Accepted->value]);
    $service->refresh($document);
    expect($document->fresh()->status)->toBe(DocumentStatus::READY_FOR_EXPORT);
});
