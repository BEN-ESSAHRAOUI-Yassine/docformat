## Purpose

Detects similarity between a document and the user's stored corpus, chunking content and producing probabilistic similarity findings with overall percentage, matching sections, source, confidence, and match type.

## ADDED Requirements

### Requirement: Similarity detection

The system SHALL detect similarity between a document and the owner's stored corpus, chunking the document into comparable units.

#### Scenario: Local corpus comparison

- **WHEN** similarity detection runs
- **THEN** the system compares document chunks against the owner's stored documents and reports matches

#### Scenario: Similarity result shape

- **WHEN** matches are found
- **THEN** each result reports a similarity percentage, matching sections, source, confidence, and match type

#### Scenario: Privacy respecting

- **WHEN** similarity detection runs against the local corpus
- **THEN** documents are not sent outside the application

### Requirement: Probabilistic similarity issues

The system SHALL surface similarity findings as probabilistic issues, clearly framed as estimates.

#### Scenario: Similarity issue created

- **WHEN** a similarity match is found
- **THEN** the system records a probabilistic issue with source `similarity` and review mode `similarity`

#### Scenario: No matches yields no issues

- **WHEN** no similarity is detected
- **THEN** no similarity issues are produced
