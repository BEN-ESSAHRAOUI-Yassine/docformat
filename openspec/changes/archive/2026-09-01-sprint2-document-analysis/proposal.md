## Why

After a user uploads a DOCX file, the system has no understanding of the document's structure. Every downstream feature — formatting, quality checks, citations, figures, export — depends on accurate document structure extraction. Without analysis, the platform cannot detect headings, figures, tables, captions, citations, or any other structural element. This sprint builds the foundational analysis engine that all subsequent processing depends on.

## What Changes

- New `DocumentAnalysis` model to store analysis results per document version
- New `DetectedElement` model to store individual extracted elements (headings, paragraphs, figures, tables, captions, citations, bibliography, abbreviations, lists, page breaks, footnotes, headers, footers, sections, appendices)
- `DocumentAnalysisService` that orchestrates DOCX parsing via the existing `DocxReader` and produces a structured element tree
- Heading detection using multiple signals: Word styles, font size/weight, capitalization, numbering, spacing, indentation, text patterns
- Confidence scoring for automatically detected headings (0.0–1.0)
- Manual heading assignment API endpoint (mark text as heading level 1-6)
- Hierarchy validation that detects and warns about invalid heading sequences (e.g., H4 before H3)
- Language field on documents (default `fr-FR`), required before analysis starts
- `AnalyzeDocumentJob` dispatched to `document-processing` queue after upload
- API endpoints: `POST /api/v1/documents/{document}/analyze`, `GET /api/v1/documents/{document}/analysis`, `POST /api/v1/documents/{document}/elements/{element}/assign-heading`
- Analysis status tracking (pending → analyzing → completed → failed)

## Capabilities

### New Capabilities

- `document-analysis`: Core analysis pipeline — parse DOCX, extract structural elements, store analysis results, track analysis status, handle analysis lifecycle (pending/analyzing/completed/failed)
- `heading-detection`: Heading detection using Word styles, font properties, capitalization, numbering, spacing, indentation, and text patterns. Confidence scoring. Manual heading assignment. Hierarchy validation.

### Modified Capabilities

- `document-upload`: After upload completes, the system now dispatches an `AnalyzeDocumentJob` and sets document status to `analyzing`. The `language` field is required before analysis can start.

## Impact

- **Models**: New `DocumentAnalysis`, `DetectedElement` models; `Document` model gains `language` column and `analysis` relationship
- **Migrations**: `document_analyses` table, `detected_elements` table, `add_language_to_documents` migration
- **Services**: New `DocumentAnalysisService` in `app/Services/`
- **Jobs**: New `AnalyzeDocumentJob` in `app/Jobs/`
- **Controllers**: New `AnalysisController` for analysis endpoints; `DocumentController` updated to dispatch analysis after upload
- **API**: 3 new endpoints under `/api/v1/`
- **Queue**: Uses existing `document-processing` queue from Sprint 1
- **Dependencies**: No new external dependencies — extends existing PHPWord-based `DocxReader`
