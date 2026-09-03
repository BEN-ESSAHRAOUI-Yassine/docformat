## Context

See proposal.md — Why. Sprint 5 established detection (citations, bibliography, abbreviations, duplicates, style violations) but left no durable action trail and no review surface. The existing pipeline already persists `StyleViolation` rows and transiently produces arrays from `CitationValidator`, `AbbreviationDetector`, `DuplicateDetector`, `PageIntegrityService`, `NumberingService`, and `StyleEngine`. `DocumentVersion` snapshots files; `DocumentAnalysisService::assignHeading` already snapshots `original_data` + sets `manual=true` — a precedent for reversible changes. Currently no action/history table, no undo/redo, no issue panel, and no accept/reject/ignore.

## Goals / Non-Goals

**Goals:**
- A unified `DocumentIssue` surface normalizing ALL detection sources into one panel with severity/category/description/location/recommendation/decision.
- A durable `DocumentAction` log with undo/redo up to 50 actions, with correct reversibility classification.
- Accept/reject/edit/ignore review workflow with per-decision actions and review-aware document status.
- Review modes (All, Formatting, Citations, Bibliography, Similarity, AI, Grammar) plus safe bulk actions.
- Manual page breaks (`origin=user`) and a viewer-only paragraph-marks toggle.
- Fold in two pre-existing bugs: `StyleAnalysisController@index` invalid relation; missing bibliography merge route.

**Non-Goals:**
- Real document-editor rendering (the viewer is a structural preview, not a WYSIWYG editor).
- Similarity/AI provider integrations (categories exist, no provider work).
- Refactoring every detection producer's internal shape — they keep their own outputs; the collector adapter handles normalization.

## Decisions

**1. Unified `DocumentIssue` as panel source of truth.**
Chose over reusing only `StyleViolation` or a light decision-layer: matches the master spec's `document_issues` entity and centralizes decision state + review modes. Producers keep their signatures; an `IssueCollector` service adapts their outputs into `DocumentIssue` rows. Alternative considered: add `decision` columns to `StyleViolation` and scatter — rejected because citation/bibliography/abbreviation/duplicate issues are transient and would each need schema divergence.

**2. Snapshot-based reversal (schema + command pattern), not full file diffing.**
Each `DocumentAction` stores `old_value`/`new_value`/`payload` as JSON. A `HistoryService` maintains per-document undo/redo stacks (capped 50) using an `ActionCursor`; `Reversor` implementations restore prior state by re-writing stored values or reversing the targeted DB mutation. Chosen over file-snapshot-per-action (heavy, hard to attribute to elements) while still noting major operations may trigger an intermediate `DocumentVersion` snapshot. `Reversibility` enum: `FULL`, `PARTIAL`, `NONE`. External analysis = `NONE` (logged, never undoable, never presented as a document modification).

**3. Decision state lives on the issue, actions capture the audit.**
`DocumentIssue.decision ∈ {pending, accepted, rejected, edited, ignored}` plus `ignored_reason`, `reviewed_by`, `reviewed_at`. Accept (with a recommendation that produces a change), reject, edit, and ignore all persist a `DocumentAction`. Bulk ops reuse the same issue decision path and so are individually logged; a single `undo` of the bulk loop reverses each in reverse order.

**4. Page breaks as `DetectedElement` with `origin`.**
`PageBreakService` inserts a `page_break` element at a target element with `metadata.origin = 'user'`; automated breaks keep no/other origin. This reuses the existing element model and keeps manual/auto distinguishable for the UI, while `DocumentAction` records the insertion as reversible.

**5. Enforcement mode wired through analysis.**
Untangle `DocumentStatus::REVIEW_REQUIRED` (`DocumentStatus` enum already exists) with real transitions: pending issues → `review_required`; all decided → `ready_for_export`. `AnalyzeStyleJob` will pass the document's enforcement mode to `StyleEngine` and the collector will surface deterministic vs probabilistic framing.

**6. Bug fixes folded in.**
- `StyleAnalysisController@index`: replace non-existent `$analysis->violations()` with a correct query/relation.
- Add `POST /api/v1/documents/{document}/bibliography/{entry}/merge` wired to `DuplicateDetector` (frontend already calls it).

## Risks / Trade-offs

- **Snapshot accuracy** → Mitigation: keep reversors small, targeted, and covered by unit tests that assert old values restore exactly.
- **Bulk-undo coupling** → Mitigation: mark all actions in a bulk with a shared `bulk_id`; undo reduces over that id in one pass.
- **Collector duplication** → Mitigation: single `IssueCollector` maps each producer once; a category enum keeps provenance. If a producer output drifts, only its mapper changes.
- **Issue proliferation** → Mitigation: debounce/idempotency keyed to the analysis; re-running detection replaces a document's issues rather than stacking new rows.
- **200-row issue panel payload** → Mitigation: paginate + severity/category/mode filters on the endpoint.

## Migration Plan

Additive migrations only: `document_actions`, `document_issues` (and `bulk_id`, `ignored_reason`, decision columns on `document_issues`). No changes to existing tables beyond optional `origin` metadata already supported via JSON. Rollback-safe: dropping the new tables returns to prior behavior. Existing documents are unaffected until re-analysis/next detection run repopulates issues.
