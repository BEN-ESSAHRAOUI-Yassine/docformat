## Purpose

Provides a unified quality-control issue panel where detections from style, citation, bibliography, abbreviation, figure/table, page integrity, and numbering sources are surfaced together with severity, location, and recommendation, and can be accepted, rejected, edited, or ignored by the user.

## ADDED Requirements

### Requirement: Unified issue collection

The system SHALL normalize issues from all detection sources into a single `DocumentIssue` representation for a document, each carrying severity, category, description, location, recommendation, and a decision state.

#### Scenario: Collect style issues

- **WHEN** style analysis has produced violations for a document
- **THEN** each violation appears as a `DocumentIssue` with its severity, category, description, and recommendation

#### Scenario: Collect citation and bibliography issues

- **WHEN** citation/bibliography validation produces issues for a document
- **THEN** each orphaned, uncited, mismatch, or ambiguous finding appears as a `DocumentIssue`

#### Scenario: Collect abbreviation, duplicate, page-integrity, and numbering issues

- **WHEN** abbreviation, duplicate, page-integrity, or numbering detection produces findings
- **THEN** each finding appears as a `DocumentIssue` in the appropriate category

#### Scenario: List issues for a document

- **WHEN** an owner requests issues for a document
- **THEN** the system returns all collected issues, filterable by severity, category, decision state, or review mode, and paginated

#### Scenario: Non-owner is denied issue access

- **WHEN** a user who does not own the document requests its issues
- **THEN** the system returns 403

### Requirement: Issue review decisions

The system SHALL let a user accept, reject, edit, or ignore an individual issue, recording the decision and actor.

#### Scenario: Accept an issue

- **WHEN** an owner accepts an issue
- **THEN** the system applies the recommended change (if any), marks the issue decision as accepted, and records the decision as an action

#### Scenario: Reject an issue

- **WHEN** an owner rejects an issue
- **THEN** the system marks the issue decision as rejected and records the decision as an action, without applying a change

#### Scenario: Edit an issue

- **WHEN** an owner edits the recommendation of an issue before deciding
- **THEN** the system stores the edited recommendation and marks the decision as edited

#### Scenario: Ignore an issue with reason

- **WHEN** an owner ignores an issue
- **THEN** the system marks the issue decision as ignored, stores the user-provided reason, and records the decision as an action

### Requirement: Deterministic vs probabilistic distinction

The system SHALL distinguish deterministic findings (facts) from probabilistic estimates (suggestions) when presenting issues.

#### Scenario: Deterministic finding presented as a fact

- **WHEN** an issue originates from a deterministic rule (e.g. a heading has the wrong font)
- **THEN** the issue is displayed as a detected problem with clear deterministic framing

#### Scenario: Probabilistic finding presented as an estimate

- **WHEN** an issue originates from a probabilistic estimate (e.g. a possible duplicate or AI-like text)
- **THEN** the issue is displayed with uncertainty framing, never as a definitive claim

### Requirement: Review modes

The system SHALL allow reviewing issues one by one or filtered by category (All, Formatting, Citations, Bibliography, Similarity, AI, Grammar).

#### Scenario: Filter by review mode

- **WHEN** an owner selects a review mode
- **THEN** the issue list is filtered to the matching category

#### Scenario: All mode shows everything

- **WHEN** an owner selects the All review mode
- **THEN** all collected issues for the document are shown

### Requirement: Bulk actions with confirmation

The system SHALL support safe bulk decisions (accept all formatting corrections, reject all suggestions) after explicit confirmation.

#### Scenario: Bulk accept formatting

- **WHEN** an owner confirms a bulk accept of all formatting issues
- **THEN** the system applies and records each action individually, and the whole bulk can be undone as a unit

#### Scenario: Bulk reject suggestions

- **WHEN** an owner confirms a bulk reject of all suggestions
- **THEN** the system rejects each suggested issue and records each decision individually

#### Scenario: Single undo reverts a bulk operation

- **WHEN** an owner undoes a bulk operation
- **THEN** a single undo reverts every action performed within that bulk operation

### Requirement: Review-aware document status

The system SHALL track document review state so a document is not exported as final while reviewable issues remain.

#### Scenario: Pending issues set review required

- **WHEN** a document has issues in a pending decision state
- **THEN** the document status becomes review_required

#### Scenario: Resolved issues allow export

- **WHEN** every reviewable issue has been decided
- **THEN** the document status transitions to ready_for_export
