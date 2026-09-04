## Purpose

Provides probabilistic AI-content analysis that detects weakly-supported assertions, flags sections lacking references, and suggests additional references, with clear estimation framing and per-document activation control.

## ADDED Requirements

### Requirement: AI-content analysis

The system SHALL analyze document content for AI-assistable characteristics and produce probabilistic findings, never presented as definitive authorship evidence.

#### Scenario: Analyze content characteristics

- **WHEN** AI analysis runs for a document
- **THEN** the system flags weakly-supported assertions, sections lacking references, and suggests additional references based on context

#### Scenario: Confidence framing

- **WHEN** an AI finding is produced
- **THEN** it includes a confidence indication and is framed as an estimate

### Requirement: Probabilistic AI issues

The system SHALL surface AI findings as probabilistic issues with review mode `ai`.

#### Scenario: AI issue created

- **WHEN** an AI finding is produced
- **THEN** the system records a probabilistic issue with source `ai` and review mode `ai`

### Requirement: Per-document activation

The system SHALL let the user enable or disable AI analysis per document.

#### Scenario: Toggle AI analysis

- **WHEN** a user disables AI analysis for a document
- **THEN** AI analysis is not run for that document
