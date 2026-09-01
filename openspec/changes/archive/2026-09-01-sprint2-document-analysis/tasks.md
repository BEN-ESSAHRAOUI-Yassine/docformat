## 1. Database Schema & Models

- [x] 1.1 Create `document_analyses` migration: id, document_id (FK), document_version_id (FK), status (enum: pending, analyzing, completed, failed), error_message (nullable text), metadata (json, nullable), timestamps. **Verify:** Migration runs, `Schema::hasTable('document_analyses')` returns true.
- [x] 1.2 Create `detected_elements` migration: id, document_analysis_id (FK), document_id (FK), type (string), element_index (integer), content (longText, nullable), heading_level (unsignedTinyInteger, nullable), metadata (json, nullable), timestamps. **Verify:** Migration runs, `Schema::hasTable('detected_elements')` returns true.
- [x] 1.3 Create `add_language_to_documents` migration: add `language` column (string, default `fr-FR`) to documents table. **Verify:** Migration runs, `Schema::hasColumn('documents', 'language')` returns true.
- [x] 1.4 Create `DocumentAnalysis` model with belongsTo Document, belongsTo DocumentVersion, hasMany DetectedElements, status enum cast. **Verify:** `DocumentAnalysis::factory()->create()` works, all relationships resolve.
- [x] 1.5 Create `DetectedElement` model with belongsTo DocumentAnalysis, belongsTo Document, metadata JSON cast. **Verify:** `DetectedElement::factory()->create()` works.
- [x] 1.6 Create `AnalysisStatus` enum (pending, analyzing, completed, failed) in `app/Enums/`. **Verify:** `AnalysisStatus::cases()` returns all 4 values.
- [x] 1.7 Create factories for DocumentAnalysis and DetectedElement. **Verify:** Factories create valid model instances.
- [x] 1.8 Add `language` column cast and `analysis` relationship to Document model. **Verify:** `$document->language` returns `fr-FR` by default, `$document->analyses` resolves.

## 2. Analysis Services

- [x] 2.1 Create `DocumentAnalysisService` in `app/Services/` with methods: `analyze(Document $document, DocumentVersion $version): DocumentAnalysis`. Orchestrates the full analysis pipeline. **Verify:** Service can be resolved from container, `analyze()` method exists.
- [x] 2.2 Implement element extraction in `DocumentAnalysisService`: load DOCX via DocxReader, iterate sections, extract all element types (heading, paragraph, table, figure, caption, source, list, page_break, section), store as DetectedElement records. **Verify:** Analyzing `tests/fixtures/docx/simple.docx` creates DetectedElement records for headings and paragraphs.
- [x] 2.3 Implement `HeadingDetectionService` in `app/Services/` with method `detectHeadings(PhpWord $phpWord): array`. Uses multi-signal detection: style name, font size, bold, capitalization, numbering, spacing, indentation, text patterns. **Verify:** Unit test with fixture containing styled headings detects all headings with correct levels.
- [x] 2.4 Implement confidence scoring in `HeadingDetectionService`. Weighted signals: style (0.4), font size (0.15), bold (0.1), capitalization (0.1), numbering (0.1), spacing (0.05), indentation (0.05), text pattern (0.05). **Verify:** Heading with 4+ signals gets confidence ≥ 0.9; heading with 1 signal gets confidence 0.1-0.49.
- [x] 2.5 Implement hierarchy validation in `HeadingDetectionService`. Detect skipped levels (e.g., H4 before H3) and return warnings. **Verify:** Test with heading sequence H1→H3 produces warning about missing H2.
- [x] 2.6 Implement manual heading assignment method in `DocumentAnalysisService`: `assignHeading(DetectedElement $element, int $level): DetectedElement`. Updates element type to `heading`, sets level, sets confidence to 1.0, stores original data in metadata. **Verify:** Assigning level 3 to a paragraph element updates its type and level correctly.

## 3. Queue Job

- [x] 3.1 Create `AnalyzeDocumentJob` in `app/Jobs/` with `handle(DocumentAnalysisService $service)` method. Sets document status to `analyzing`, calls service, sets status to `analysis_completed` on success or `failed` on exception. Queue: `document-processing`. Tries: 3. Timeout: 120s. **Verify:** Job can be dispatched, `php artisan queue:work --once` processes it.
- [x] 3.2 Add failure tracking to `AnalyzeDocumentJob`: on exception, store error message in `DocumentAnalysis.error_message` and set status to `failed`. **Verify:** Job that throws exception creates failed analysis record with error message.

## 4. API Layer

- [x] 4.1 Create `AnalysisPolicy` with view (owner only) and trigger (owner only) methods. **Verify:** Policy returns true for owner, false for non-owner.
- [x] 4.2 Create `AnalysisController` with `store` (trigger analysis) and `show` (get latest analysis) methods. `store` returns 202, `show` returns analysis with elements grouped by type. **Verify:** Controller methods exist and return typed responses.
- [x] 4.3 Create `TriggerAnalysisRequest` Form Request with authorization check via `AnalysisPolicy::trigger`. **Verify:** Validation passes for authenticated owner, fails for non-owner.
- [x] 4.4 Create `AnalysisResource` API Resource for consistent JSON response shape. **Verify:** `AnalysisResource::make($analysis)->toArray()` returns expected structure.
- [x] 4.5 Create `DetectedElementResource` API Resource for element responses. **Verify:** Resource includes id, type, content, heading_level, metadata.
- [x] 4.6 Register analysis routes under `/api/v1/documents/{document}/analyze` (POST) and `/api/v1/documents/{document}/analysis` (GET) with auth middleware. **Verify:** `php artisan route:list --path=api/v1/documents` shows analysis routes.
- [x] 4.7 Update `DocumentController::store` to dispatch `AnalyzeDocumentJob` after successful upload when language is set. **Verify:** Upload response includes analysis status; job is dispatched to queue.

## 5. Tests

- [x] 5.1 Write Pest tests for `DocumentAnalysisService::analyze()` with `simple.docx` fixture: creates analysis record, creates detected elements, sets status to completed. **Verify:** `php artisan test --filter=DocumentAnalysisTest` passes.
- [x] 5.2 Write Pest tests for heading detection: fixture with styled headings detects correct levels and text. **Verify:** `php artisan test --filter=HeadingDetectionTest` passes.
- [x] 5.3 Write Pest tests for confidence scoring: verify signal weighting produces expected confidence ranges. **Verify:** `php artisan test --filter=HeadingConfidenceTest` passes.
- [x] 5.4 Write Pest tests for hierarchy validation: verify warnings for skipped levels. **Verify:** `php artisan test --filter=HierarchyValidationTest` passes.
- [x] 5.5 Write Pest tests for manual heading assignment: assign level, verify type/level/confidence changes. **Verify:** `php artisan test --filter=ManualHeadingTest` passes.
- [x] 5.6 Write Pest tests for analysis API endpoints: trigger analysis (202), get analysis (200), get analysis for non-owner (403), get analysis for unanalyzed document (404). **Verify:** `php artisan test --filter=AnalysisApiTest` passes.
- [x] 5.7 Write Pest tests for `AnalyzeDocumentJob`: dispatch, process, failure handling. **Verify:** `php artisan test --filter=AnalyzeDocumentJobTest` passes.
- [x] 5.8 Write Pest test for language requirement: analysis blocked when language not set. **Verify:** `php artisan test --filter=LanguageRequiredTest` passes.
- [x] 5.9 Run full test suite: `php artisan test --compact`. Fix any failures. **Verify:** All tests pass (Sprint 0 + Sprint 1 + Sprint 2 tests).
- [x] 5.10 Run `vendor/bin/pint --dirty --format agent` to ensure code style compliance. **Verify:** No formatting errors.

## 6. Cleanup & Documentation

- [x] 6.1 Create `docs/sprint2-setup.md` documenting analysis engine setup, database migrations, queue configuration, and API endpoints. **Verify:** Documentation file exists and covers all setup steps.
- [x] 6.2 Mark Sprint 2 tasks as "Done" on Plane. **Verify:** All Sprint 2 tasks show Done status on Plane board.
