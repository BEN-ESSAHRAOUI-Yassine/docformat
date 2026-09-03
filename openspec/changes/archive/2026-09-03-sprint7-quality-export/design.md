## Context

See proposal.md — Why. Sprint 6 built `document_issues` (severity/category/decision/probabilistic), `DocumentAction` history, undo/redo, and `ReviewStatusService` (already gates `ready_for_export`). The only DOCX write primitive is `DocxWriter::save()`/`loadFromFile()`; it cannot create tables, images, headers/footers, TOC, or bibliography. `DocumentStatus` already has `READY_FOR_EXPORT/EXPORTING/COMPLETED/FAILED`. `config/queue.php` already defines `exports`/`reports` queues; disks `docformat`/`public`/`local` and `storage/app/{exports,reports,versions}` exist. No report model, export service/job, integrity validator, or notifications exist.

## Goals / Non-Goals

**Goals:**
- Deterministic per-category + weighted overall quality score (0-100) with error/warning/info counts, derived from `document_issues`.
- Modular quality rules (enable/disable, severity) extending the StyleCheck pattern.
- `QualityReport` persisted + retrievable; report generation service with the documented structure.
- Final processing that applies accepted issue decisions (engine-supported ops) and exports DOCX.
- Runtime `DocxIntegrityValidator` (ZIP/CT/styles/rels + XML well-formedness) run after export.
- Download streaming; `exporting → completed/failed` transitions; export completion/failure notifications.
- Frontend report + export/download UI.

**Non-Goals:**
- PDF export (documented as deferred — no PDF library in the project).
- Batch processing (deferred).
- Building tables/images/headers/footers/TOC/bibliography via the engine — unsupported by the current `DocxWriter`; such accepted changes are recorded but not materialized in this sprint (kept traceable, not silently dropped).

## Decisions

**1. Score from `document_issues`, not a new detector.** `QualityEngine` aggregates counts from persisted `DocumentIssue` rows grouped by category/severity, computing category scores and an overall weighted score. Weights default per the epic: formatting 40%, citations 25%, figure/table 20%, style 15%. Chosen over re-running all detectors because issues are already the single normalized source after Sprint 6 and the score must stay deterministic and review-aware. Probabilistic issues are excluded from the deterministic category score and reported separately.

**2. Modular rules via a small `QualityRule` contract + threshold-based defaults.** Rather than a large new rule engine, provide a `QualityRule` interface and a registry; each rule computes a per-category subscore/penalty from issue counts and carries an `enabled` flag and severity. This mirrors the existing `StyleEngine` checks and keeps Sprint 7 focused.

**3. Export re-uses `DocxWriter` and records a new `DocumentVersion`.** `DocxExportService` loads the current version, applies accepted `DocumentIssue` decisions (heading text/style, page breaks, paragraphs — the ops `DocxWriter` supports), saves to `storage/app/exports`, then runs `DocxIntegrityValidator`. On success it creates an export `DocumentVersion` (or updates the metadata) and marks `completed`; on failure it marks `failed` and keeps the prior version. `ExportJob` runs on the `exports` queue.

**4. Integrity validation lifted from test patterns into a service.** `DocxIntegrityValidator::validate(path): array{valid, errors[]}` checks ZIP openability, `[Content_Types].xml`, `word/document.xml`, `word/styles.xml`, `word/_rels/document.xml.rels`, and XML well-formedness via `simplexml`. This formalizes what `DocxOutputIntegrityTest` currently asserts inline.

**5. Notifications via Laravel's `database` channel.** Add the `notifications` migration (Laravel default) + `DocumentExportCompleted`/`DocumentExportFailed` notification classes. The owner is notified via `->notify()`. Kept to the built-in database channel (no mail/queue changes needed for the deliverable).

**6. Limits documented, not hidden.** Any accepted issue whose operation the engine cannot replay (e.g. caption insertion, bibliography renumber) is recorded as an export limitation on the report/export result, and the export proceeds with the supported changes plus the unchanged original content. This honors "never silently lose content."

## Risks / Trade-offs

- **Score drift** → Mitigation: score is deterministic over persisted issues; if a downstream detector changes, its issue mapping changes, not the scoring formula.
- **Export fidelity** → Mitigation: export applies only engine-supported ops and always re-runs integrity validation; unsupported accepted changes are surfaced, never silently applied or dropped.
- **Notification scope** → Mitigation: use the built-in `database` channel so no mail config is required; prefs can be added later without breaking the channel.
- **Report payload size** → Mitigation: report stores a structured summary + category sections; the API returns JSON (DOM rendering in frontend).

## Migration Plan

Additive migrations: `quality_reports`, `notifications` (Laravel default). No changes to existing tables. Export writes new files under `storage/app/exports` and records them as document versions. Rollback-safe: dropping the new tables returns to prior behavior; exports remain on disk until cleaned.
