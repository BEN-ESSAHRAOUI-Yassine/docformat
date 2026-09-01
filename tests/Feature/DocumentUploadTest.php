<?php

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->user = User::factory()->create([
        'email_verified_at' => now(),
    ]);
    $this->token = $this->user->createToken('auth-token')->plainTextToken;
    $this->headers = ['Authorization' => 'Bearer '.$this->token];
    $this->project = Project::factory()->create(['owner_id' => $this->user->id]);
});

it('allows authenticated user to upload a docx file', function () {
    Storage::fake('docformat');

    $file = UploadedFile::fake()->createWithContent(
        'test.docx',
        file_get_contents(__DIR__.'/../fixtures/docx/simple.docx')
    );

    $response = $this->withHeaders($this->headers)
        ->postJson("/api/v1/projects/{$this->project->id}/documents", [
            'file' => $file,
            'name' => 'Test Document',
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['data' => ['id', 'name', 'status', 'file_hash']]);

    $this->assertDatabaseHas('documents', [
        'name' => 'Test Document',
        'project_id' => $this->project->id,
        'status' => 'analysis_completed',
    ]);
});

it('rejects non-docx files', function () {
    Storage::fake('docformat');

    $file = UploadedFile::fake()->create('test.txt', 100, 'text/plain');

    $response = $this->withHeaders($this->headers)
        ->postJson("/api/v1/projects/{$this->project->id}/documents", [
            'file' => $file,
        ]);

    $response->assertStatus(422);
});

it('rejects upload without file', function () {
    $response = $this->withHeaders($this->headers)
        ->postJson("/api/v1/projects/{$this->project->id}/documents", []);

    $response->assertStatus(422)->assertJsonValidationErrors('file');
});

it('rejects unauthenticated upload', function () {
    $file = UploadedFile::fake()->createWithContent(
        'test.docx',
        file_get_contents(__DIR__.'/../fixtures/docx/simple.docx')
    );

    $response = $this->postJson("/api/v1/projects/{$this->project->id}/documents", [
        'file' => $file,
    ]);

    $response->assertUnauthorized();
});

it('prevents listing documents from another users project', function () {
    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $otherProject = Project::factory()->create(['owner_id' => $otherUser->id]);

    $response = $this->withHeaders($this->headers)
        ->getJson("/api/v1/projects/{$otherProject->id}/documents");

    $response->assertForbidden();
});

it('detects duplicate uploads by file hash', function () {
    Storage::fake('docformat');

    $file = UploadedFile::fake()->createWithContent(
        'test.docx',
        file_get_contents(__DIR__.'/../fixtures/docx/simple.docx')
    );

    $this->withHeaders($this->headers)
        ->postJson("/api/v1/projects/{$this->project->id}/documents", [
            'file' => $file,
            'name' => 'First Upload',
        ]);

    $file2 = UploadedFile::fake()->createWithContent(
        'test2.docx',
        file_get_contents(__DIR__.'/../fixtures/docx/simple.docx')
    );

    $response = $this->withHeaders($this->headers)
        ->postJson("/api/v1/projects/{$this->project->id}/documents", [
            'file' => $file2,
            'name' => 'Second Upload',
        ]);

    $response->assertOk();
});
