## Why

Sprint 6 built issues, review, and undo/redo but left no way to turn a reviewed document into a scored, shareable deliverable. The quality score and report are what users hand off, and DOCX export must preserve all applied formatting while guaranteeing the file is valid. This sprint delivers the quality control engine, quality report generation, the final processing pipeline that materializes accepted changes into a clean DOCX, export with runtime integrity validation, and completion notifications.

## What Changes

- New quality scoring engine computing per-category scores (formatting compliance, citation-bibliography consistency, figure/table management, style adherence) with configurable weights and a deterministic 0-100 overall score, plus error/warning/info counts.
- New modular quality rules with enable/disable and configurable severity (mirrors the existing StyleCheck pattern).
- New `QualityReport` model + `quality_reports` table and a report generation service producing the documented report structure (document info, summary, per-category sections, warnings, ignored issues). Report exposed as JSON via the API.
- New final processing pipeline that re-reads the current DOCX version, applies each accepted issue decision (heading text/style, page breaks, paragraphs — the operations the DOCX engine supports), and saves a new export version to `storage/app/exports`.
- New DOCX export: `POST /documents/{document}/export` dispatches an `ExportJob`; `GET /documents/{document}/download` streams the exported file. Document status transitions `exporting → completed/failed`.
- New `DocxIntegrityValidator` service (ZIP/`[Content_Types].xml`/`word/document.xml`/`styles.xml`/relationships + XML well-formedness), run after every export; failure keeps the prior version and marks the document `failed`.
- New notification system: `notifications` migration + `DocumentExportCompleted` / `DocumentExportFailed` notification classes delivered via the `database` channel to the owner on export completion.
- Frontend: Quality Report page (score + per-category breakdown), Export/Download actions and report nav, `reports.js`/`export.js` API clients.

## Capabilities

### New Capabilities
- `quality-engine`: Deterministic per-category and weighted overall quality scoring, modular quality rules with enable/disable and severity, and error/warning/info counts.
- `quality-reports`: `QualityReport` model, report generation service with the documented structure, and report retrieval/dispatch endpoints.
- `docx-export`: Final processing that applies accepted changes, DOCX export + download, runtime integrity validation, and document status transitions.

### Modified Capabilities
- `document-analysis`: Analysis integration now also serves as the source for quality scoring/reporting, and the export pipeline consumes detected elements and accepted issues.

## Impact

- New migrations: `quality_reports`, `notifications`.
- New models: `QualityReport`; new enum(s) for weights/categories as needed.
- New services: `QualityEngine`, `QualityReportService`, `DocxExportService`, `DocxIntegrityValidator` (+ modular quality rules).
- New jobs: `ExportJob` (and optional `GenerateReportJob`).
- New controllers/actions: export (trigger/download), report (view).
- New API endpoints (all under `/api/v1`, auth:sanctum): `POST /documents/{document}/export`, `GET /documents/{document}/download`, `GET /documents/{document}/quality`, `GET /documents/{document}/report`.
- Frontend: report page, export/download buttons, sidebar "Report"/"Export" nav, `reports.js`/`export.js` clients.
- No new external providers. PDF export is explicitly deferred to a later sprint (report and export are DOCX-only). Batch processing is deferred.
