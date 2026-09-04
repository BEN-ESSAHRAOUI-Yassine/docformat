## Why

Sprint 7 delivered per-document processing, quality scoring, and export, but users with many documents must repeat the same work document-by-document. Batch processing lets a user submit a set of documents, run the same analysis pipeline on all of them, track per-document progress and quality scores, and export the results together — reducing repetitive work and completing the remaining Sprint 7 scope.

## What Changes

- New `Batch` + `BatchItem` records and tables. A `Batch` belongs to a project, has a name, an optional style profile, a status, and a summary. Each `BatchItem` links a document to a batch and tracks its status, quality score, and error.
- New `BatchJob` on the `document-processing` queue that processes each item through the existing analysis pipeline, updates per-item status and score, and recomputes the batch summary.
- New API endpoints: create/list batch, show batch, list batch items, export batch (individual files or a ZIP archive), and download the export.
- New frontend batch page to create a batch from a set of documents, watch progress, review per-document scores, and export.

## Capabilities

### New Capabilities

- `batch-processing`: Batch creation, per-item processing and progress, batch summary statistics, and individual or ZIP export.

## Impact

- New migrations: `batches`, `batch_items`.
- New models: `Batch`, `BatchItem`.
- New service: `BatchProcessingService`; new job: `BatchJob` (on `document-processing`).
- New controller: `BatchController`.
- New API endpoints (auth:sanctum): `GET/POST /batches`, `GET /batches/{batch}`, `GET /batches/{batch}/items`, `POST /batches/{batch}/export`, `GET /batches/{batch}/export/download`.
- Frontend: batch create/progress/review page, sidebar nav, `batches.js` client.
- No new external dependencies (ZIP via PHP `ZipArchive`).
