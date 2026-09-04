<?php

use App\Enums\IssueSource;
use App\Models\DetectedElement;
use App\Models\Document;
use App\Models\DocumentIssue;
use App\Models\Project;
use App\Models\User;
use App\Services\Ai\GroqProvider;
use App\Services\Ai\LocalHeuristicProvider;
use App\Services\Ai\ProviderManager;
use App\Services\AiContentService;
use App\Services\CorrectionEngine;
use App\Services\IssueCollector;
use App\Services\ParaphraseEngine;
use App\Services\SimilarityEngine;
use App\Services\SynonymEngine;
use Illuminate\Support\Facades\Http;

it('registers and resolves providers via the manager', function () {
    $manager = app(ProviderManager::class);

    expect($manager->has('local'))->toBeTrue();
    expect($manager->get('local'))->toBeInstanceOf(LocalHeuristicProvider::class);
});

it('groq provider sends a request to the configured endpoint', function () {
    Http::fake([
        'https://api.groq.com/openai/v1/chat/completions' => Http::response([
            'choices' => [['message' => ['content' => '[{"type":"reference","text":"s","message":"m","confidence":0.7}]']]],
        ], 200),
    ]);

    $provider = new GroqProvider('key', 'https://api.groq.com/openai/v1', 'model');
    $result = $provider->analyze('some text');

    expect($result)->toBeArray();
    expect($result[0]['type'])->toBe('reference');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/chat/completions');
    });
});

it('local heuristic provider is deterministic', function () {
    $provider = new LocalHeuristicProvider;

    $a = $provider->analyze('This is a very long sentence that lacks any reference marker whatsoever and should be flagged by the heuristic analysis engine.');
    $b = $provider->analyze('This is a very long sentence that lacks any reference marker whatsoever and should be flagged by the heuristic analysis engine.');

    expect($a)->toBe($b);
});

it('manager falls back to local provider on failure', function () {
    $manager = app(ProviderManager::class);
    $result = $manager->withFallback('local', fn ($p) => $p->suggestSynonyms('important'));

    expect($result)->toContain('significant');
});

it('similarity engine detects a corpus match', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);

    $other = Document::factory()->create(['project_id' => $project->id]);
    DetectedElement::factory()->create([
        'document_id' => $other->id,
        'type' => 'paragraph',
        'content' => 'The quick brown fox jumps over the lazy dog and continues running through the meadow.',
    ]);

    $doc = Document::factory()->create(['project_id' => $project->id]);
    DetectedElement::factory()->create([
        'document_id' => $doc->id,
        'type' => 'paragraph',
        'content' => 'The quick brown fox jumps over the lazy dog and continues running through the meadow.',
    ]);

    $result = app(SimilarityEngine::class)->compare($doc);

    expect($result['overall'])->toBeGreaterThan(0);
});

it('ai content service produces probabilistic findings', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $doc = Document::factory()->create(['project_id' => $project->id]);
    DetectedElement::factory()->create([
        'document_id' => $doc->id,
        'type' => 'paragraph',
        'content' => 'This is likely a very important statement that may be weakly supported by the available evidence.',
    ]);

    $findings = app(AiContentService::class)->analyze($doc);

    expect($findings)->toBeArray();
});

it('correction engine detects a spelling error', function () {
    $engine = new CorrectionEngine;
    $corrections = $engine->checkText('The teh dog runs.');

    expect($corrections)->not->toBeEmpty();
    expect($corrections[0]['original'])->toBe('teh');
    expect($corrections[0]['suggested'])->toBe('the');
});

it('correction engine flags missing punctuation', function () {
    $engine = new CorrectionEngine;
    $corrections = $engine->checkText('This sentence has no period');

    $hasPunctuation = collect($corrections)->contains(fn ($c) => $c['reason'] === 'Sentence is missing ending punctuation');
    expect($hasPunctuation)->toBeTrue();
});

it('paraphrase and synonym suggestions are returned', function () {
    expect(app(ParaphraseEngine::class)->suggest('Hello world'))->toHaveKeys(['original', 'alternative', 'confidence', 'suggest_citation']);
    expect(app(SynonymEngine::class)->suggest('important'))->toHaveKeys(['word', 'synonyms']);
});

it('intelligence issues are collected with the new sources', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $doc = Document::factory()->create(['project_id' => $project->id, 'ai_enabled' => true]);
    DetectedElement::factory()->create([
        'document_id' => $doc->id,
        'type' => 'paragraph',
        'content' => 'The teh dog runs. This is a very long sentence that lacks any reference marker whatsoever and should be flagged by the heuristic analysis engine.',
    ]);

    app(IssueCollector::class)->collect($doc);

    $issues = DocumentIssue::forDocument($doc->id)->get();
    $corrections = $issues->whereIn('source', [IssueSource::Grammar, IssueSource::Spelling]);
    expect($corrections->count())->toBeGreaterThan(0);
});

it('exposes intelligence endpoints for the owner', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $doc = Document::factory()->create(['project_id' => $project->id]);
    $token = $user->createToken('t')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson("/api/v1/documents/{$doc->id}/ai/toggle", ['enabled' => true])
        ->assertOk()
        ->assertJsonPath('ai_enabled', true);

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson("/api/v1/documents/{$doc->id}/similarity")
        ->assertOk()
        ->assertJsonStructure(['overall', 'matches']);

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson("/api/v1/documents/{$doc->id}/synonyms/suggest", ['word' => 'important'])
        ->assertOk();
});

it('rejects intelligence access for a non-owner', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $otherUser->id]);
    $doc = Document::factory()->create(['project_id' => $project->id]);
    $token = $user->createToken('t')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson("/api/v1/documents/{$doc->id}/similarity")
        ->assertForbidden();
});
