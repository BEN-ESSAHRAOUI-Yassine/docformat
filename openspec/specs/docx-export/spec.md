## Purpose

Applies accepted issue decisions to a working copy of the document, exports a clean DOCX, validates its integrity, and streams it for download, while keeping the original document unchanged.

## ADDED Requirements

### Requirement: Apply accepted changes

The system SHALL materialize accepted issue decisions onto a working DOCX copy before export, for the operations the document engine supports (heading text/style, page breaks, paragraphs).

#### Scenario: Export applies accepted changes

- **WHEN** a document is exported
- **THEN** the system loads the current version, applies accepted changes, and saves an export copy

### Requirement: DOCX export

The system SHALL export a document to a downloadable DOCX file and record the resulting version.

#### Scenario: Export to DOCX

- **WHEN** an owner triggers export
- **THEN** the system produces a DOCX export, records it as a document version, and returns it for download

#### Scenario: Export is asynchronous

- **WHEN** export is triggered
- **THEN** the export is dispatched to a background job and returns 202

### Requirement: Export integrity validation

The system SHALL validate that the exported DOCX is structurally valid (ZIP structure, content types, main document XML, styles, relationships, and XML well-formedness).

#### Scenario: Valid export passes validation

- **WHEN** the exported DOCX passes integrity validation
- **THEN** the document status becomes completed

#### Scenario: Invalid export marked failed

- **WHEN** the exported DOCX fails integrity validation
- **THEN** the export job marks the document failed and preserves the prior version

### Requirement: Download

The system SHALL let the owner download the exported DOCX.

#### Scenario: Download exported file

- **WHEN** an owner requests the download of the export
- **THEN** the system streams the DOCX file

#### Scenario: Non-owner denied download

- **WHEN** a user who does not own the document requests the download
- **THEN** the system returns 403

### Requirement: Original document unchanged

The export pipeline SHALL never modify the original uploaded document.

#### Scenario: Original file untouched

- **WHEN** export runs
- **THEN** the original document file hash is unchanged

### Requirement: Completion notification

The system SHALL notify the owner when export completes or fails.

#### Scenario: Notify on export completion

- **WHEN** an export completes
- **THEN** the owner receives a completion notification

#### Scenario: Notify on export failure

- **WHEN** an export fails
- **THEN** the owner receives a failure notification
