## Purpose

Generates and stores a quality report for a processed document, aggregating the quality score, per-category detection results, warnings, and ignored issues into a shareable structure exposed via the API.

## ADDED Requirements

### Requirement: Quality report generation

The system SHALL generate a quality report for a document capturing document info, the quality score summary, per-category sections (structure, figures, tables, citations, bibliography, style), warnings, and ignored issues.

#### Scenario: Generate a report

- **WHEN** report generation runs for a document
- **THEN** the system produces a `QualityReport` record with the documented structure and a generated timestamp

#### Scenario: Report reflects current issues

- **WHEN** a report is generated
- **THEN** it reflects the document's current issues, decisions, and quality score

#### Scenario: Report retrieval

- **WHEN** an owner requests the latest report for a document
- **THEN** the system returns the report with its summary and per-category breakdown

### Requirement: Report persistence

The system SHALL persist each generated report so reports are retrievable after generation.

#### Scenario: Latest report returned

- **WHEN** a document has multiple reports
- **THEN** the system returns the most recent one

### Requirement: Report ownership

The system SHALL restrict report access to the document owner.

#### Scenario: Non-owner denied report access

- **WHEN** a user who does not own the document requests the report
- **THEN** the system returns 403
