## Purpose

Lets a user process multiple documents in one batch, tracking per-document progress and quality scores, with a batch summary and individual or ZIP export.

## ADDED Requirements

### Requirement: Batch creation

The system SHALL allow a user to create a batch containing multiple documents within a project.

#### Scenario: Create a batch from multiple documents

- **WHEN** an owner submits a batch with a name and a collection of documents
- **THEN** the system creates a batch with one item per document and dispatches background processing

#### Scenario: Batch requires owned documents

- **WHEN** a user includes a document they do not own in a batch
- **THEN** the system rejects the batch

### Requirement: Per-item processing

The system SHALL process each batch item through the analysis pipeline and track its status and quality score.

#### Scenario: Items processed to completion

- **WHEN** a batch is processed
- **THEN** each item transitions to a completed or failed state and records its quality score

#### Scenario: Per-item failure is isolated

- **WHEN** one item fails during batch processing
- **THEN** the other items continue processing and the batch reports a partial-failure summary

### Requirement: Batch summary

The system SHALL compute a batch summary from its items, reporting counts of pending, processing, completed, and failed items along with an average quality score.

#### Scenario: Summary reflects items

- **WHEN** the user views a batch
- **THEN** the batch returns per-item statuses and a summary of completed/failed/pending counts and average score

### Requirement: Batch export

The system SHALL let the owner export a batch as individual files or a single ZIP archive.

#### Scenario: Export individual files

- **WHEN** an owner exports an individual file for a batch item
- **THEN** the system streams that document

#### Scenario: Export batch as ZIP

- **WHEN** an owner requests a ZIP of the batch
- **THEN** the system streams a ZIP archive containing the exported documents

### Requirement: Batch ownership

The system SHALL restrict batch access to the project owner.

#### Scenario: Non-owner cannot view a batch

- **WHEN** a user who does not own the batch's project requests it
- **THEN** the system returns 403
