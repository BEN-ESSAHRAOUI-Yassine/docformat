## MODIFIED Requirements

### Requirement: Analysis lifecycle management

The system SHALL track analysis status for each document. The status transitions SHALL follow: pending → analyzing → completed | failed.

#### Scenario: Analysis starts after upload

- **WHEN** a DOCX file is uploaded and language is set
- **THEN** the system dispatches an analysis job and sets document status to `analyzing`

#### Scenario: Analysis completes successfully

- **WHEN** the analysis job finishes processing all elements
- **THEN** the system stores all detected elements, sets document status to `analysis_completed`, and records the analysis timestamp

#### Scenario: Analysis fails

- **WHEN** the analysis job encounters a parsing error or unsupported content
- **THEN** the system sets document status to `failed`, stores the error message, and preserves the original document unchanged

#### Scenario: Analysis is re-triggered

- **WHEN** a user requests analysis on a document that already has a completed analysis
- **THEN** the system discards the previous analysis results and starts a new analysis

#### Scenario: Analysis records reviewable issues

- **WHEN** analysis completes and quality detection (style, citation, bibliography, abbreviation, duplicate, page-integrity, numbering) produces findings
- **THEN** the system persists the normalized set of findings as `DocumentIssue` records associated with the analysis, and sets document status to `review_required`

#### Scenario: Collection is read-only on the original

- **WHEN** quality detection runs during analysis
- **THEN** the original uploaded document is not modified; gathering issues is read-only until a user decides

#### Scenario: Analysis sources quality scoring

- **WHEN** quality scoring or report generation is requested for a document
- **THEN** the system derives scores and report sections from the document's issues and detected elements
