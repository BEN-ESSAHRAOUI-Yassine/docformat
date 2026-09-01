## Context

Sprint 1 established the Laravel foundation: authentication, database schema, project/document models, file storage, and a `DocxReader` service that can extract basic elements (headings, tables, images, paragraphs, page breaks) from DOCX files using PHPWord. The document upload pipeline stores originals, creates versions, and sets status to `uploaded`.

Sprint 2 builds the analysis layer that transforms raw DOCX data into a structured element tree. The analysis engine must:
- Extend the existing `DocxReader` without modifying it
- Store analysis results in the database
- Run asynchronously via the queue system
- Support both automatic and manual heading detection
- Enforce language selection before processing

## Goals / Non-Goals

**Goals:**

- Extract all structural elements from DOCX files into a queryable database representation
- Detect headings using multiple signals with confidence scoring
- Support manual heading assignment via API
- Validate heading hierarchy and warn about structural issues
- Run analysis asynchronously without blocking upload
- Preserve original document integrity throughout analysis

**Non-Goals:**

- Style validation or formatting correction (Sprint 3+)
- Citation/bibliography validation (Sprint 4+)
- Figure/table caption detection beyond basic extraction (Sprint 4+)
- Similarity/plagiarism analysis (Sprint 6+)
- AI content analysis (Sprint 6+)
- Export or report generation (Sprint 5+)
- Frontend UI for analysis results (future sprint)

## Decisions

### 1. Separate `DocumentAnalysis` and `DetectedElement` models

**Decision:** Create two models: `DocumentAnalysis` (analysis metadata, status, version link) and `DetectedElement` (individual extracted elements).

**Rationale:** Separating analysis metadata from element data allows:
- Multiple analyses per document (re-analysis preserves history)
- Efficient querying of elements by type without loading analysis metadata
- Future ability to compare analyses across versions

**Alternatives considered:**
- Single `DocumentAnalysis` with embedded JSON array of elements → rejected because querying individual elements by type would require JSON parsing, and element count could exceed practical JSON sizes for large documents
- Single `DetectedElement` without analysis grouping → rejected because tracking analysis lifecycle and status would require duplicating status on every element

### 2. Heading confidence scoring uses weighted signal matching

**Decision:** Heading detection assigns confidence based on the number of matching signals, with weights: Word style (0.4), font size (0.15), bold weight (0.1), capitalization (0.1), numbering pattern (0.1), spacing (0.05), indentation (0.05), text pattern (0.05).

**Rationale:** Word style is the most reliable signal (Microsoft's own heading markup), so it gets the highest weight. Font properties and numbering are secondary indicators. Spacing and indentation are weak signals that help disambiguate.

**Alternatives considered:**
- Equal weights per signal → rejected because style name is significantly more reliable than spacing
- Binary (heading/not-heading) without confidence → rejected because the product spec requires confidence scores and manual assignment fallback
- Machine learning classifier → rejected as overkill for MVP; can be added later

### 3. Analysis runs as a queued job, not synchronously

**Decision:** `AnalyzeDocumentJob` is dispatched to the `document-processing` queue. The API returns 202 immediately.

**Rationale:** DOCX parsing for large documents (50+ pages) can take seconds to minutes. Blocking the HTTP request would cause timeouts. The queue infrastructure from Sprint 1 supports this pattern.

**Alternatives considered:**
- Synchronous analysis with extended timeout → rejected because it ties up a PHP-FPM worker and doesn't scale
- JavaScript/Node-based parser → rejected because we already have PHPWord and the analysis is PHP-native

### 4. Language stored on Document model, not as a separate entity

**Decision:** Add a `language` column (string, default `fr-FR`) to the `documents` table.

**Rationale:** Language is a document-level property that affects all processing stages. It's simple enough to be a column rather than a separate table. The value is a BCP 47 language tag (e.g., `fr-FR`, `en-US`).

**Alternatives considered:**
- Separate `document_languages` table → rejected as over-engineered for a single required field
- Language detection from content → rejected because the spec requires explicit user selection

### 5. Heading hierarchy validation is post-analysis, not real-time

**Decision:** Hierarchy validation runs as a final step after all elements are extracted, not during extraction.

**Rationale:** Validating hierarchy requires the complete heading sequence. Running it during extraction would require lookahead logic. Post-analysis validation is simpler and produces clearer warnings.

**Alternatives considered:**
- Real-time validation during extraction → rejected because it adds complexity and requires buffering headings until the sequence is complete

## Risks / Trade-offs

- **PHPWord limitations** → PHPWord may not extract all metadata (e.g., precise spacing values, some font properties). Mitigation: extract what's available, store null for unavailable properties, and enhance in future sprints.
- **Large document performance** → Documents with 1000+ elements may create large database inserts. Mitigation: batch inserts in chunks of 100 elements.
- **Heading detection false positives** → Multi-signal detection may still produce false positives for styled body text. Mitigation: confidence scoring allows downstream features to filter by threshold; manual override available.
- **Re-analysis cost** → Re-analyzing a large document re-processes the entire file. Mitigation: preserve previous analysis records, and consider incremental analysis in future sprints.

## Migration Plan

1. Add `language` column to `documents` table (default `fr-FR`)
2. Create `document_analyses` table
3. Create `detected_elements` table
4. Create `AnalysisPolicy` for authorization
5. Create `AnalysisController` with analyze and show endpoints
6. Create `DocumentAnalysisService` and `HeadingDetectionService`
7. Create `AnalyzeDocumentJob`
8. Update `DocumentController::store` to dispatch analysis job
9. Add tests for analysis pipeline, heading detection, and API endpoints

No rollback needed — all new tables and columns are additive. Existing documents are unaffected.

## Open Questions

- None at this time. The design follows directly from the specs and existing architecture.
