<?php

namespace App\Services;

use App\Enums\IssueCategory;
use App\Enums\IssueDecision;
use App\Enums\IssueSource;
use App\Models\Document;
use App\Models\DocumentAnalysis;
use App\Models\DocumentIssue;
use App\Models\StyleViolation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class IssueCollector
{
    public function __construct(
        private CitationValidator $citationValidator,
        private AbbreviationDetector $abbreviationDetector,
        private DuplicateDetector $duplicateDetector,
        private PageIntegrityService $pageIntegrityService,
        private NumberingService $numberingService,
    ) {}

    /**
     * Collect and normalize all issues for a document into DocumentIssue records.
     *
     * @return Collection<int, DocumentIssue>
     */
    public function collect(Document $document, ?DocumentAnalysis $analysis = null): Collection
    {
        $analysis ??= $document->latestAnalysis;

        return DB::transaction(function () use ($document, $analysis) {
            DocumentIssue::forDocument($document->id)->delete();

            $issues = collect();

            $issues = $issues->concat($this->collectStyle($document, $analysis));
            $issues = $issues->concat($this->collectCitations($document, $analysis));
            $issues = $issues->concat($this->collectAbbreviations($document, $analysis));
            $issues = $issues->concat($this->collectDuplicates($document, $analysis));
            $issues = $issues->concat($this->collectPageIntegrity($document, $analysis));
            $issues = $issues->concat($this->collectNumbering($document, $analysis));

            return $issues->map(fn (DocumentIssue $issue) => $issue->fresh());
        });
    }

    private function collectStyle(Document $document, ?DocumentAnalysis $analysis): Collection
    {
        $violations = $analysis
            ? StyleViolation::where('document_analysis_id', $analysis->id)->get()
            : StyleViolation::whereHas('analysis', fn ($q) => $q->where('document_id', $document->id))->get();

        return $violations->map(function (StyleViolation $v) use ($document) {
            return $this->make(
                $document,
                $v->analysis,
                IssueSource::Style,
                IssueCategory::tryFrom($v->category) ?? IssueCategory::Other,
                $v->severity,
                $v->description,
                $v->recommendation,
                $v->detected_element_id,
                $v->analysis?->id,
                probabilistic: false,
            );
        });
    }

    private function collectCitations(Document $document, ?DocumentAnalysis $analysis): Collection
    {
        $validation = $this->citationValidator->validate($document);
        $issues = collect();

        foreach ($validation['warnings'] ?? [] as $warning) {
            $category = match ($warning['type'] ?? '') {
                'uncited' => IssueCategory::Reference,
                'mismatch' => IssueCategory::Consistency,
                'ambiguous' => IssueCategory::Consistency,
                default => IssueCategory::Reference,
            };

            $issues->push($this->make(
                $document,
                $analysis,
                IssueSource::Citation,
                $category,
                'error',
                $warning['message'] ?? $warning['type'],
                $this->citationRecommendation($warning),
                null,
                $analysis?->id,
                probabilistic: in_array($warning['type'] ?? '', ['ambiguous']),
            ));
        }

        return $issues;
    }

    private function collectAbbreviations(Document $document, ?DocumentAnalysis $analysis): Collection
    {
        $abbreviations = $document->abbreviations()->get()->map(fn ($abbr) => [
            'abbreviation' => $abbr->abbreviation,
            'full_form' => $abbr->full_form,
            'definition_element_index' => $abbr->definition_element_index,
            'usage_count' => $abbr->usage_count,
            'occurrences' => $abbr->occurrences ?? [],
            'is_consistent' => (bool) $abbr->is_consistent,
            'inconsistent_forms' => $abbr->inconsistent_forms ?? [],
        ])->all();

        $validation = $this->abbreviationDetector->getIssues($abbreviations);
        $issues = collect();

        foreach ($validation['inconsistent'] as $item) {
            $issues->push($this->make(
                $document,
                $analysis,
                IssueSource::Abbreviation,
                IssueCategory::Consistency,
                'warning',
                "Abbreviation '{$item['abbreviation']}' has inconsistent definitions: ".implode(' vs ', $item['forms']),
                'Use a single consistent definition for the abbreviation.',
                null,
                $analysis?->id,
            ));
        }

        foreach ($validation['unused'] as $item) {
            $issues->push($this->make(
                $document,
                $analysis,
                IssueSource::Abbreviation,
                IssueCategory::Consistency,
                'info',
                "Abbreviation '{$item['abbreviation']}' is defined but never used.",
                'Remove it or reference it in the document.',
                null,
                $analysis?->id,
            ));
        }

        return $issues;
    }

    private function collectDuplicates(Document $document, ?DocumentAnalysis $analysis): Collection
    {
        $entries = $document->bibliographyEntries()->get();
        $groups = $this->duplicateDetector->detect($entries);
        $issues = collect();

        foreach ($groups as $group) {
            $issues->push($this->make(
                $document,
                $analysis,
                IssueSource::Duplicate,
                IssueCategory::Duplicate,
                'warning',
                'Potential duplicate bibliography entries ('.round($group['confidence'] * 100).'% confidence).',
                'Review and merge or keep both.',
                null,
                $analysis?->id,
                probabilistic: true,
            ));
        }

        return $issues;
    }

    private function collectPageIntegrity(Document $document, ?DocumentAnalysis $analysis): Collection
    {
        $elements = $analysis ? $analysis->detectedElements()->get()->all() : [];
        $integrity = $this->pageIntegrityService->analyzeIntegrity($elements);
        $issues = collect();

        foreach ($integrity['warnings'] ?? [] as $warning) {
            $issues->push($this->make(
                $document,
                $analysis,
                IssueSource::PageIntegrity,
                IssueCategory::PageIntegrity,
                $warning['type'] === 'oversized' ? 'warning' : 'error',
                $warning['message'],
                null,
                $warning['element_index'] ? $this->findElementId($analysis, $warning['element_index']) : null,
                $analysis?->id,
            ));
        }

        return $issues;
    }

    private function collectNumbering(Document $document, ?DocumentAnalysis $analysis): Collection
    {
        $elements = $analysis ? $analysis->detectedElements()->get()->all() : [];
        $summary = $this->numberingService->detectInconsistencies($elements);
        $issues = collect();

        foreach (array_merge($summary['figures'] ?? [], $summary['tables'] ?? []) as $issue) {
            $issues->push($this->make(
                $document,
                $analysis,
                IssueSource::Numbering,
                IssueCategory::Numbering,
                'warning',
                $issue['issue'],
                'Renumber the affected captions sequentially.',
                $issue['element_index'] ? $this->findElementId($analysis, $issue['element_index']) : null,
                $analysis?->id,
            ));
        }

        return $issues;
    }

    private function make(
        Document $document,
        ?DocumentAnalysis $analysis,
        IssueSource $source,
        IssueCategory $category,
        string $severity,
        string $description,
        ?string $recommendation,
        ?int $elementId,
        ?int $analysisId,
        bool $probabilistic = false,
    ): DocumentIssue {
        return DocumentIssue::create([
            'document_id' => $document->id,
            'document_analysis_id' => $analysisId,
            'detected_element_id' => $elementId,
            'source' => $source,
            'category' => $category,
            'severity' => $severity,
            'description' => $description,
            'recommendation' => $recommendation,
            'location' => ['analysis_id' => $analysisId],
            'decision' => IssueDecision::Pending,
            'review_mode' => $source->reviewMode(),
            'probabilistic' => $probabilistic,
        ]);
    }

    private function findElementId(?DocumentAnalysis $analysis, int $elementIndex): ?int
    {
        return $analysis?->detectedElements()->where('element_index', $elementIndex)->value('id');
    }

    private function citationRecommendation(array $warning): string
    {
        return match ($warning['type'] ?? '') {
            'uncited' => 'Cite this entry in the document or remove it.',
            'orphaned' => 'Add a matching bibliography entry or remove the citation.',
            'mismatch' => 'Align the cited author/year with the bibliography entry.',
            'ambiguous' => 'Select the intended bibliography entry.',
            default => 'Review the referenced sources.',
        };
    }
}
