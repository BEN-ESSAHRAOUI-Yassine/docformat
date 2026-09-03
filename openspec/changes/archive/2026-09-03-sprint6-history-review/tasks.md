## 1. Database & Enums (Action log + Issues)

- [ ] 1.1 Create `document_actions` migration: id, document_id (FK cascade), user_id (FK nullable), action_type, element_type, element_id (nullable), origin enum, old_value json nullable, new_value json nullable, payload json nullable, reversibility enum, bulk_id nullable, timestamps; index document_id + bulk_id. Verify migration runs cleanly.
- [ ] 1.2 Create `document_issues` migration: id, document_id (FK cascade), document_analysis_id (FK nullable), detected_element_id (FK nullable), source enum, category, severity enum, description, recommendation nullable, location (json nullable), decision enum default pending, ignored_reason nullable, review_mode enum nullable, probabilistic boolean default false, reviewed_by FK nullable, reviewed_at nullable, timestamps; index document_id + decision + category + severity. Verify migration runs cleanly.
- [ ] 1.3 Create `ActionOrigin` enum (automatic, manual), `Reversibility` enum (full, partial, none), `ActionType` enum (heading_assigned, style_fixed, merged, renumbered, caption_added, citation_linked, page_break_added, page_break_removed, issue_accepted, issue_rejected, issue_edited, issue_ignored, bulk_resolved). Verify enums exist and validate values.
- [ ] 1.4 Create `IssueSource` enum (style, citation, bibliography, abbreviation, duplicate, figure, table, page_integrity, numbering), `IssueCategory` enum, `IssueDecision` enum (pending, accepted, rejected, edited, ignored), `ReviewMode` enum (all, formatting, citations, bibliography, similarity, ai, grammar). Verify enums exist and validate values.
- [ ] 1.5 Create `DocumentAction` model with fillable, casts (old_value/new_value/payload as array), relations (document, user), scopes (forDocument, forUser, ofType, betweenDates, inBulk), and a `isReversible()` accessor. Verify factory creates valid records and scopes filter correctly.
- [ ] 1.6 Create `DocumentIssue` model with fillable, casts (location array, probabilistic boolean, decision/source/category/severity/review_mode), relations (document, analysis, element, reviewer), scopes (forDocument, forSeverity, forCategory, byDecision, inReviewMode, pending). Verify factory creates valid records and scopes filter correctly.
- [ ] 1.7 Create `DocumentActionFactory` and `DocumentIssueFactory` with states for each enum. Verify factory states produce valid records.

## 2. Action Logging (Plane Task A)

- [ ] 2.1 Create `ActionLogger` service with a `record(Document $document, array $data): DocumentAction` method that fills user_id from auth (nullable for automatic), sets origin, reversibility, and snapshots old/new values. Verify it persists a DocumentAction with correct fields.
- [ ] 2.2 Create `DocumentActionResource` for JSON serialization exposing id, action_type, element_type, element_id, origin, reversibility, old_value, new_value, user, created_at. Verify API response structure.
- [ ] 2.3 Add `GET /api/v1/documents/{document}/actions` endpoint (controller action) with filters action_type, origin, date range, pagination and ownership policy. Verify endpoint returns 200 for owner, 403 for non-owner.
- [ ] 2.4 Wire logging into `DocumentAnalysisService::assignHeading` so every manual heading assignment records a DocumentAction (origin manual). Verify a DocumentAction is created when assigning a heading.
- [ ] 2.5 Wire logging into `DuplicateDetector::merge` and add the missing `POST /api/v1/documents/{document}/bibliography/{entry}/merge` route wired to a controller action. Verify merge creates a DocumentAction (origin manual, reversibility full).
- [ ] 2.6 Wire logging into `NumberingService` renumbering, `CaptionService` caption addition, and the citation auto-link inside `CitationValidator::validate`. Verify each mutation records a DocumentAction.

## 3. Undo/Redo Engine (Plane Task B)

- [ ] 3.1 Create `Reversor` interface with `canHandle(DocumentAction $action): bool` and `reverse(DocumentAction $action): mixed`. Verify interface exists.
- [ ] 3.2 Implement reversors for element-metadata/heading assignment, numbering renumber, caption add, and bibliography merge. Verify each reverse restores the prior stored value.
- [ ] 3.3 Create `HistoryService` with `recordAction()`, `undo(Document $document): DocumentAction`, `redo(Document $document): DocumentAction`, an `ActionCursor` managing per-document undo/redo stacks, and 50-action culling. Verify undo/redo return the correct action and stack depth is capped at 50.
- [ ] 3.4 Add `POST /api/v1/documents/{document}/undo`, `POST /api/v1/documents/{document}/redo`, and `GET /api/v1/documents/{document}/history` endpoints with ownership policy. Verify success responses and 403 for non-owner.
- [ ] 3.5 Ensure NON_REVERSIBLE actions are excluded from undo (external analysis logged but not undoable). Verify reversing a non-reversible action is refused.
- [ ] 3.6 Add bulk-id support: undo of a bulk reduces over the shared bulk_id in reverse order and reverts every action in the bulk. Verify a single undo reverts all actions within a bulk.

## 4. Unified Issue Collection (Plane Task C)

- [ ] 4.1 Fix `StyleAnalysisController@index`: replace the non-existent `$analysis->violations()` call with a correct StyleViolation → DocumentIssue query/relation. Verify `GET /style-violations` no longer 500s.
- [ ] 4.2 Create `IssueCollector` service with `collect(Document $document, ?DocumentAnalysis $analysis = null): Collection` that normalizes persisted StyleViolations and transient outputs from CitationValidator, AbbreviationDetector, DuplicateDetector, PageIntegrityService, and NumberingService. Verify mapping from each producer to DocumentIssue produces correct severity/category/source/location.
- [ ] 4.3 Ensure IssueCollector is idempotent per analysis: re-running replaces a document's issues rather than stacking duplicates. Verify repeated collection yields a stable count.
- [ ] 4.4 Add `GET /api/v1/documents/{document}/issues` endpoint with severity/category/decision/review_mode filters and pagination, ownership policy. Verify 200 for owner, 403 for non-owner.
- [ ] 4.5 Create `DocumentIssueResource` exposing id, source, category, severity, description, recommendation, location, decision, ignored_reason, probabilistic, created_at. Verify API response structure.

## 5. Review Workflow & Modes (Plane Tasks D & E)

- [ ] 5.1 Add `POST /api/v1/documents/{document}/issues/{issue}/accept` endpoint that applies the recommended change (when present), sets decision accepted, records a DocumentAction, and returns the updated issue. Verify accepted decision + action logged.
- [ ] 5.2 Add `POST .../issues/{issue}/reject`, `.../issues/{issue}/edit`, and `.../issues/{issue}/ignore` endpoints. Reject sets decision rejected; edit stores edited recommendation; ignore stores ignored_reason. Verify each sets the decision and logs an action.
- [ ] 5.3 Add ownership policy so only the owner can decide on issues. Verify 403 for non-owner on all decision endpoints.
- [ ] 5.4 Implement document status transitions: pending issues → `review_required`; all decided → `ready_for_export`. Verify transition behavior after decisions.
- [ ] 5.5 Add `POST /api/v1/documents/{document}/issues/bulk` endpoint accepting mode/category + decision (accept/reject) with guardrails (only safe bulk combinations), applying each decision, sharing a bulk_id, and logging each action. Verify confirmation of bulk is required and each action is logged.
- [ ] 5.6 Wire `AnalyzeStyleJob` to pass the document's enforcement mode to `StyleEngine` and mark style issues deterministic vs probabilistic. Verify deterministic framing is set on deterministic issues.

## 6. Page Breaks & Paragraph Marks (Plane Task F)

- [ ] 6.1 Create `PageBreakService` with `insertBefore(Document $document, DetectedElement $target, string $context): DetectedElement` inserting a page_break element with `metadata.origin = 'user'`. Verify insertion + origin set, original document unchanged.
- [ ] 6.2 Add `POST /api/v1/documents/{document}/page-breaks` endpoint (context: chapter/section/figure/table/appendix + target element) and `DELETE /api/v1/documents/{document}/page-breaks/{element}`. Verify 200 on create/delete, 403 for non-owner, and only user page breaks are removable.
- [ ] 6.3 Verify the page break insertion/removal is recorded as a reversible DocumentAction. Verify DocumentAction created with reversibility full and correct action type.
- [ ] 6.4 Implement viewer-only paragraph-marks toggle on the frontend; render subtle gray marks in the viewer. Verify marks render only in viewer and are not included in exported output.

## 7. Frontend Workspace & Issue Panel

- [ ] 7.1 Build `IssuePanel.jsx` component as the workspace right column with severity badge, category, description, location, recommendation, and Accept/Reject/Edit/Ignore buttons plus an ignore-reason dialog. Verify component renders issues and emits decision events.
- [ ] 7.2 Add review-mode toolbar (All, Formatting, Citations, Bibliography, Similarity, AI, Grammar) that filters the issue list and a bulk-action confirmation dialog. Verify mode filtering and bulk confirmation flow.
- [ ] 7.3 Rebuild placeholder `DocumentView.jsx` into a workspace shell (outline | document preview | issues panel) and add page-break controls + paragraph-marks toggle. Verify the shell renders and wires actions.
- [ ] 7.4 Add `/documents/:id/issues` route in `App.jsx` and the "Issues" nav item in `Sidebar.jsx`. Verify navigation and active-state styling.
- [ ] 7.5 Create `frontend/src/api/issues.js` and `frontend/src/api/actions.js` clients (list issues, decide issue, bulk, history, undo/redo, page breaks). Verify clients call the correct endpoints and handle 401 redirect via the shared axios client.
- [ ] 7.6 Add `HistoryPanel`/history controls (undo/redo buttons, action list) to the workspace. Verify undo/redo buttons call the endpoints and reflect state.

## 8. Tests & Verification

- [ ] 8.1 Write feature tests for DocumentAction CRUD/index + authorization + logging on mutation. Verify all pass.
- [ ] 8.2 Write feature tests for undo/redo endpoints, 50-depth culling, bulk-undo, and non-reversible guard. Verify all pass.
- [ ] 8.3 Write feature tests for issue collection (collect all sources, idempotency), issues index filters, and ownership. Verify all pass.
- [ ] 8.4 Write feature tests for review decisions (accept apply/ignore/edit/reject + status transitions) and bulk actions. Verify all pass.
- [ ] 8.5 Write feature tests for page break endpoints (insertion, deletion, authorization, origin) and DOCX export excluding paragraph marks. Verify all pass.
- [ ] 8.6 Run `vendor/bin/pint --dirty` to format changes and `php artisan test --compact` to verify the full suite passes with no regressions. Verify clean output.

## 9. OpenSpec Archive & Plane Sync

- [ ] 9.1 Sync delta specs to main specs (`document-actions`, `document-issues`, `page-breaks` new; `document-analysis` modified). Verify main specs updated.
- [ ] 9.2 Archive the `sprint6-history-review` change to `openspec/changes/archive/`. Verify change moved.
- [ ] 9.3 Update Plane: move all 6 Sprint 6 tasks Todo → In Progress → Done with evidence comments, assign to the user, and move the Sprint 6 epic to Done. Verify all tasks and epic are Done.
