## 1. Quality Engine (Plane Task 57)

- [ ] 1.1 Create `QualityEngine` service computing per-category scores + weighted overall 0-100 score from `DocumentIssue` rows (grouped by category/severity), with error/warning/info counts. Verify deterministic output and correct weights.
- [ ] 1.2 Create `QualityRule` interface + `RuleRegistry` with default rules (formatting, citations, figure/table, style) each exposing enabled flag and severity. Verify rules register and can be enabled/disabled.
- [ ] 1.3 Ensure probabilistic issues are excluded from the deterministic category score and reported separately. Verify a probabilistic issue does not lower the category score.
- [ ] 1.4 Add `GET /api/v1/documents/{document}/quality` endpoint returning scores + counts with ownership policy. Verify 200 for owner, 403 for non-owner.

## 2. Quality Report (Plane Task 58)

- [ ] 2.1 Create `quality_reports` migration (document_id, analysis_id nullable, score json, sections json, summary json, generated_at, timestamps) and `QualityReport` model with casts + relations (document, analysis). Verify migration runs and factory creates valid records.
- [ ] 2.2 Create `QualityReportService` generating the documented structure (document info, quality score summary, per-category sections: structure/figures/tables/citations/bibliography/style, warnings, ignored issues). Verify report reflects current issues + decisions.
- [ ] 2.3 Create `QualityReportResource` and `GET /api/v1/documents/{document}/report` returning the latest report. Verify latest-of-many retrieval + 403 for non-owner.

## 3. DOCX Export (Plane Tasks 59 & 60)

- [ ] 3.1 Create `DocxIntegrityValidator` service (ZIP open, `[Content_Types].xml`, `word/document.xml`, `word/styles.xml`, `word/_rels/document.xml.rels`, XML well-formedness). Verify it returns `valid` + errors and detects a corrupt file.
- [ ] 3.2 Create `DocxExportService` that loads the current version via `DocxWriter`, applies accepted `DocumentIssue` decisions (heading text/style, page breaks, paragraphs) and saves to `storage/app/exports`. Verify export writes a file and applies an accepted heading change.
- [ ] 3.3 Record the export as a new `DocumentVersion` (file_path/file_size/mime/uploaded_by) and set document status `exporting`. Verify version created and status set.
- [ ] 3.4 Create `ExportJob` on the `exports` queue that runs the export, then `DocxIntegrityValidator`; status `completed` on success, `failed` on failure, and notifies the owner. Verify job runs and a stale/failed export preserves the prior version.
- [ ] 3.5 Add routes: `POST /api/v1/documents/{document}/export` (202 + async) and `GET /api/v1/documents/{document}/download` (stream file). Verify export returns 202 and download streams the DOCX; 403 for non-owner.
- [ ] 3.6 Ensure the original document file is never modified and its hash is unchanged after export. Verify original untouched.

## 4. Notifications (Plane Task 61)

- [ ] 4.1 Create `notifications` migration (Laravel default) and `DocumentExportCompleted` + `DocumentExportFailed` notification classes extending `Illuminate\Notifications\Notification` with the `database` channel. Verify notifications serialize and are stored.
- [ ] 4.2 Notify the owner on export completion and failure from `ExportJob`. Verify a `notifications` row is created for the owner.

## 5. Frontend

- [ ] 5.1 Create `frontend/src/api/reports.js` and `frontend/src/api/export.js` clients (getQuality, getReport, exportDocument, downloadDocument). Verify clients hit the correct `/api/v1` endpoints.
- [ ] 5.2 Build `frontend/src/pages/reports/QualityReport.jsx` showing the overall + per-category score breakdown and report sections. Verify it renders score + sections and handles loading/empty/error.
- [ ] 5.3 Add Export and Download actions (e.g. in `DocumentView`) and a `/documents/:id/report` route in `App.jsx`; add "Report"/"Export" nav in `Sidebar.jsx`. Verify navigation and export/download trigger the clients.
- [ ] 5.4 Provide export-status feedback (spinner/notice) and error handling on export/download. Verify disabled state during export.

## 6. Tests & Verification

- [ ] 6.1 Write feature tests for quality scoring (per-category + overall, deterministic, probabilistic exclusion) and the quality endpoint + ownership. Verify all pass.
- [ ] 6.2 Write feature tests for report generation/persistence/retrieval + ownership. Verify all pass.
- [ ] 6.3 Write feature tests for export end-to-end (fixture → export → integrity → re-open), download + auth, version creation, status transitions, original-unchanged, and failure path. Verify all pass.
- [ ] 6.4 Write feature tests for export completion/failure notifications. Verify all pass.
- [ ] 6.5 Run `vendor/bin/pint --dirty` to format changes and `php artisan test --compact` to verify the full suite passes with no regressions. Verify clean output.

## 7. OpenSpec Archive & Plane Sync

- [ ] 7.1 Sync delta specs to main specs (`quality-engine`, `quality-reports`, `docx-export` new; `document-analysis` modified). Verify main specs updated.
- [ ] 7.2 Archive the `sprint7-quality-export` change to `openspec/changes/archive/`. Verify change moved.
- [ ] 7.3 Update Plane: move Sprint 7 tasks 57-61 Todo → In Progress → Done with evidence comments; leave 62 (batch) deferred; move the Sprint 7 epic to Done. Verify tasks and epic are Done.
