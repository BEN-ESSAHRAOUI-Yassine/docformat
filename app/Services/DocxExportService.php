<?php

namespace App\Services;

use App\Enums\ActionOrigin;
use App\Enums\ActionType;
use App\Enums\IssueDecision;
use App\Enums\Reversibility;
use App\Models\Document;
use App\Models\DocumentIssue;
use App\Models\DocumentVersion;
use App\Services\DocxEngine\DocxIntegrityValidator;
use App\Services\DocxEngine\DocxWriter;
use Illuminate\Support\Facades\Storage;

class DocxExportService
{
    public function __construct(
        private DocxWriter $writer,
        private DocxIntegrityValidator $validator,
        private ActionLogger $actionLogger,
    ) {}

    /**
     * @return array{version: DocumentVersion, path: string, integrity: array{valid: bool, errors: array<int, string>}, applied: array<int, string>, skipped: array<int, string>}
     */
    public function export(Document $document, ?int $userId = null): array
    {
        $version = $document->currentVersion;

        if (! $version) {
            throw new \RuntimeException('Document has no current version.');
        }

        $source = Storage::disk('docformat')->path($version->file_path);

        $this->writer->loadFromFile($source);

        [$applied, $skipped] = $this->applyAcceptedChanges($document, $this->writer);

        $exportPath = Storage::disk('docformat')->path('exports/'.$document->id.'/'.$version->version_number.'.docx');

        if (! $this->writer->save($exportPath)) {
            throw new \RuntimeException('Failed to write DOCX export.');
        }

        $integrity = $this->validator->validate($exportPath);

        $size = filesize($exportPath);

        $exportVersion = $document->versions()->create([
            'version_number' => ($document->versions()->max('version_number') ?? 0) + 1,
            'file_path' => 'exports/'.$document->id.'/'.$version->version_number.'.docx',
            'file_size' => $size,
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'uploaded_by' => $userId,
            'kind' => 'export',
        ]);

        $document->update(['current_version_id' => $exportVersion->id]);

        $this->actionLogger->record($document, [
            'action_type' => ActionType::Merged->value,
            'element_type' => 'DocumentVersion',
            'element_id' => $exportVersion->id,
            'origin' => ActionOrigin::Automatic,
            'reversibility' => Reversibility::None,
            'payload' => [
                'export_path' => $exportPath,
                'integrity_valid' => $integrity['valid'],
                'applied' => $applied,
                'skipped' => $skipped,
            ],
        ]);

        return [
            'version' => $exportVersion->fresh(),
            'path' => $exportPath,
            'integrity' => $integrity,
            'applied' => $applied,
            'skipped' => $skipped,
        ];
    }

    /**
     * Apply accepted issue decisions to the in-memory document model.
     * Returns lists of applied and skipped change descriptions.
     *
     * @param  array<int, string>
     */
    private function applyAcceptedChanges(Document $document, DocxWriter $writer): array
    {
        $accepted = $document->issues()
            ->where('decision', IssueDecision::Accepted->value)
            ->get();

        $applied = [];
        $skipped = [];

        foreach ($accepted as $issue) {
            if ($this->applyIssue($issue, $writer)) {
                $applied[] = $issue->id.':'.$issue->description;
            } else {
                $skipped[] = $issue->id.':'.$issue->description;
            }
        }

        return [$applied, $skipped];
    }

    private function applyIssue(DocumentIssue $issue, DocxWriter $writer): bool
    {
        $category = $issue->category?->value ?? '';

        return match ($category) {
            'font', 'spacing', 'alignment', 'numbering' => $this->applyElementStyle($issue, $writer),
            default => false,
        };
    }

    private function applyElementStyle(DocumentIssue $issue, DocxWriter $writer): bool
    {
        $element = $issue->element;

        if (! $element) {
            return false;
        }

        $index = $element->element_index;

        if ($element->type === 'heading' && $element->heading_level && $element->content) {
            return $writer->modifyHeading($index, $element->content);
        }

        return false;
    }
}
