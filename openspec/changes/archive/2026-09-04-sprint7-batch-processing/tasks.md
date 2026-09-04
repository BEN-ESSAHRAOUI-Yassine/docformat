## 1. Batch Data & Models

- [ ] 1.1 Create `batches` migration (project_id FK, name, style_profile_id FK nullable, status, summary json, timestamps) and `batch_items` migration (batch_id FK cascade, document_id FK cascade, status, quality_score nullable, error nullable, timestamps). Verify migrations run cleanly.
- [ ] 1.2 Create `Batch` model with fillable, casts (status enum, summary array), relations (project, items, styleProfile), scopes (forProject). Verify factory creates valid records.
- [ ] 1.3 Create `BatchItem` model with fillable, casts (status, quality_score float), relations (batch, document), scopes. Verify factory creates valid records.
- [ ] 1.4 Create `BatchFactory` and `BatchItemFactory` with states (queued/processing/completed/failed). Verify factory states produce valid records.

## 2. Batch Job & Processing

- [ ] 2.1 Create `BatchProcessingService` that processes each item through the existing analysis flow and captures the item's quality score. Verify per-item status/score update.
- [ ] 2.2 Create `BatchJob` on the `document-processing` queue that runs the service and recomputes the batch summary. Verify batch transitions to completed and summary is correct.
- [ ] 2.3 Isolate per-item failure so one failing item marks failed and the batch continues. Verify a partial-failure batch still completes other items.
- [ ] 2.4 Compute batch summary (counts by status + average quality score) after processing. Verify summary reflects items.

## 3. Batch API

- [ ] 3.1 Create `BatchController` with store/create, index, show, items, export, exportDirectory actions. Verify each returns correct status codes.
- [ ] 3.2 Add `POST /api/v1/batches` (create batch with document_ids + optional profile_id) validating owned documents. Verify 201 and validation rejects non-owned documents.
- [ ] 3.3 Add `GET /api/v1/batches`, `GET /api/v1/batches/{batch}`, `GET /api/v1/batches/{batch}/items`. Verify 200 for owner, 403 for non-owner.
- [ ] 3.4 Add `POST /api/v1/batches/{batch}/export` and `GET /api/v1/batches/{batch}/export/download` (ZIP via ZipArchive). Verify ZIP streamed and 403 for non-owner.
- [ ] 3.5 Register batch routes in `routes/api.php` under auth:sanctum. Verify routes accessible.

## 4. Batch Frontend

- [ ] 4.1 Create `frontend/src/api/batches.js` client (createBatch, listBatches, getBatch, getBatchItems, exportBatch, downloadBatchExport). Verify client hits correct endpoints.
- [ ] 4.2 Build batch list page and batch create page (document multi-select + optional style profile) and batch detail with progress + per-document quality scores + batch summary. Verify renders and handles loading/empty/error.
- [ ] 4.3 Add export individual/ZIP actions and a batch nav item in the sidebar; add batch routes in `App.jsx`. Verify navigation and export trigger.

## 5. Tests & Verification

- [ ] 5.1 Write feature tests for batch creation (owned docs, reject non-owned), item processing, summary, individual/ZIP export, ownership 403, and partial failure. Verify all pass.
- [ ] 5.2 Run the full test suite and `vendor/bin/pint --dirty`. Verify clean output.

## 6. OpenSpec Archive & Plane Sync

- [ ] 6.1 Sync the batch-processing delta spec to main specs and archive `sprint7-batch-processing`. Verify change archived.
- [ ] 6.2 Update Plane: move Sprint 7 Task 62 through to Done, add traceability comment, and move the Sprint 7 epic to Done. Verify task and epic are Done.
