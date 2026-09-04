## Context

See proposal.md — Why. Per-document processing already exists: upload dispatches `AnalyzeDocumentJob` (which runs `DocumentAnalysisService::analyze`, `IssueCollector::collect`, `ReviewStatusService::refresh`), and `ExportJob` handles export. `QualityEngine` produces per-document scores. Queues `document-processing`, `exports` are already defined in `config/queue.php`. Batch processing must orchestrate these existing pieces per item without duplicating them.

## Goals / Non-Goals

**Goals:**
- A `Batch` + `BatchItem` model pair with status and per-item quality score.
- `BatchJob` that runs the existing per-document pipeline per item and aggregates a summary.
- Create/list/show/items/export endpoints with ownership enforcement.
- Individual and ZIP export.

**Non-Goals:**
- Re-implementing document analysis, quality scoring, or export — batch reuses the existing per-document services.
- Re-running AI/similarity features (future sprint) — batch processes whatever the existing pipeline produces.

## Decisions

**1. Batch orchestrates existing per-document jobs, not a new analyzer.**
`BatchJob` delegates to `AnalyzeDocumentJob` per item (or directly to the same services) and then captures each item's quality score via `QualityEngine`. Chosen over a bespoke multiplayer pipeline because the existing jobs already encapsulate review/issue/status behavior and staying with them keeps batch behavior consistent with single-document processing.

**2. Batch status + summary stored on the batch row.**
Summary (counts by status + average score) is recomputed after each item so the dashboard reflects live progress without an extra table.

**3. Export reuses `ExportJob`/`DocxExportService` output.**
Individual export streams the item's current export version if available, or triggers an export. ZIP export collects each item's exported DOCX into a temp archive via `ZipArchive` and streams it. Failures are reported per item, not silently dropped.

**4. Isolated per-item failure.**
Each item is processed in isolation; a failing item is marked `failed` and the batch continues, surfacing a `partial` state rather than aborting the whole batch.

## Risks / Trade-offs

- **Long-running batches** → Mitigation: batch process runs on the `document-processing` queue and is resumable; per-item isolation prevents one failure from stalling the batch.
- **Export size** → Mitigation: ZIP export streams from collected temp files; individual export avoids building a ZIP for single-item cases.

## Migration Plan

Additive migrations: `batches`, `batch_items`. No changes to existing tables. Rollback-safe (dropping the new tables returns to prior behavior).
