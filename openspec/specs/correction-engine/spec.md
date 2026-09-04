## Purpose

Provides automatic corrections for capitalization, punctuation, spacing, and spelling with reasons, confidence, and reversibility, where non-reversible corrections are never auto-applied.

## ADDED Requirements

### Requirement: Correction detection

The system SHALL detect and propose corrections for capitalization, punctuation, double spaces, and common spelling/technical term errors.

#### Scenario: Detect a correction

- **WHEN** a text issue is detected
- **THEN** the system produces a correction with the original text, suggested text, reason, confidence, and reversibility

#### Scenario: Non-reversible corrections require confirmation

- **WHEN** a correction is classified as non-reversible
- **THEN** the system never auto-applies it and requires user confirmation

### Requirement: Correction issues

The system SHALL surface corrections as issues with review mode `grammar` or `spelling`.

#### Scenario: Correction issue created

- **WHEN** a correction is proposed
- **THEN** the system records an issue with source `grammar` or `spelling` and review mode `grammar`

### Requirement: Optional provider correction

The system SHALL allow provider-assisted corrections in addition to deterministic rule-based corrections.

#### Scenario: Provider correction pass

- **WHEN** provider-assisted correction is enabled
- **THEN** the system augments rule-based corrections with provider suggestions
