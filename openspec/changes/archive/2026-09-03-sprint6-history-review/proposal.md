## Why

Sprint 5 shipped detection of citations, bibliography, abbreviations, and duplicates, but produced no durable action trail and no way for a user to review and take control of automated changes. Traceability, reversibility, and user control are core product principles: every document-changing operation must be logged, undoable (to at least 50 actions), and reviewable through an issue panel with accept/reject/edit/ignore decisions.

## What Changes

- New `DocumentAction` record + `document_actions` table capturing id, document_id, user_id, timestamp, action_type, element_type, element_id, origin (automatic/manual), old_value, new_value, and a `Reversibility` classification (FULL/PARTIAL/NONE).
- New `HistoryService` providing per-document undo/redo stacks capped at 50 actions, plus `POST /documents/{document}/undo` and `/redo` endpoints. NON_REVERSIBLE (external analysis) actions are logged but never undoable and never represented as modifying the document.
- New `DocumentIssue` + `document_issues` table as a unified single source for the issue panel. An `IssueCollector` service normalizes persisted `StyleViolation`s and transient citation/bibliography/abbreviation/duplicate/page-integrity/numbering issues into one shape with severity, category, description, location, recommendation, and a decision state.
- New review workflow: `POST /documents/{document}/issues/{issue}/accept|reject|edit|ignore` applying or discarding changes, each logging a `DocumentAction`. Document transitions between `REVIEW_REQUIRED` and `READY_FOR_EXPORT` as decisions are made.
- Review modes (All, Formatting, Citations, Bibliography, Similarity, AI, Grammar) and a `POST /documents/{document}/issues/bulk` endpoint with confirmation; a single undo reverts an entire bulk operation.
- Manual page breaks: `POST /documents/{document}/page-breaks` (before chapter/section/figure/table/appendix) and `DELETE /documents/{document}/page-breaks/{element}`, with `origin=user` distinguishing user vs automated breaks.
- Paragraph-marks toggle rendered in the viewer only; marks are never printed or written into exported DOCX.
- Fix pre-existing bug: `STYLE_ANALYSIS` index endpoint called a non-existent `DocumentAnalysis::violations()` relation (500s); and add the missing `/documents/{document}/bibliography/{entry}/merge` route already called by the frontend.

## Capabilities

### New Capabilities
- `document-actions`: Action logging model/enums, undo/redo engine, history endpoints, reversibility classification, and wiring of logging into every mutation point.
- `document-issues`: Unified issue model, issue collector normalization, issue listing endpoint, review decision endpoints, review modes, and bulk actions.
- `page-breaks`: Manual page-break insertion/deletion with `origin=user`, and the viewer-only paragraph-marks toggle.

### Modified Capabilities
- `document-analysis`: Issue collection and action logging integrate into the analysis lifecycle; review-aware document status transitions.

## Impact

- New migrations: `document_actions`, `document_issues`.
- New models: `DocumentAction`, `DocumentIssue`; new enums (`Reversibility`, `ActionOrigin`, `IssueDecision`, `IssueCategory`, `ReviewMode`).
- New services: `ActionLogger`, `HistoryService` (+ `Reversor` implementations), `IssueCollector`, `PageBreakService`.
- New controllers/actions: history (actions/undo/redo), issues (index/accept/reject/edit/ignore/bulk), page-breaks.
- New API endpoints (all under `/api/v1`, auth:sanctum): `GET/.../actions`, `POST .../undo`, `POST .../redo`, `GET .../history`, `GET .../issues`, `POST .../issues/{issue}/accept|reject|edit|ignore`, `POST .../issues/bulk`, `POST .../page-breaks`, `DELETE .../page-breaks/{element}`, plus restored `POST .../bibliography/{entry}/merge`.
- Frontend: rebuild `DocumentView` placeholder into a workspace shell (outline | document | issues), `IssuePanel` component, review-mode toolbar, bulk-action confirmation, page-break controls, paragraph-marks toggle, sidebar "Issues" nav, `/documents/:id/issues` route, and API service clients.
- No new external providers; no change to original-document immutability (all processing remains on working copies/versions).
