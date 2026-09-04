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

#### Scenario: Collect similarity, AI, grammar, spelling, and paraphrase issues

- **WHEN** similarity, AI-content, correction (grammar/spelling), or paraphrase detection produces findings
- **THEN** each finding appears as a probabilistic `DocumentIssue` with source `similarity`, `ai`, `grammar`, `spelling`, or `paraphrase` and review mode `similarity`, `ai`, or `grammar`

#### Scenario: List issues for a document

- **WHEN** an owner requests issues for a document
- **THEN** the system returns all collected issues, filterable by severity, category, decision state, or review mode, and paginated
