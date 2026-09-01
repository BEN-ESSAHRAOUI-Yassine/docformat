## Purpose

Detects headings in DOCX documents using multiple signals, assigns confidence scores to each detected heading, supports manual heading assignment for levels 1-6, and validates heading hierarchy for structural correctness.

## Requirements

### Requirement: Automatic heading detection using multiple signals

The system SHALL detect headings using a combination of signals: Word style names (Heading1–Heading6, Title), font size relative to body text, font weight (bold), capitalization patterns, numbering patterns, spacing before/after paragraph, indentation, and text patterns (e.g., "Chapter X", "Section X", numbered patterns like "1.2.3").

#### Scenario: Heading detected by Word style

- **WHEN** a paragraph has a Word style named "Heading 1" through "Heading 6" or "Title"
- **THEN** the system detects it as a heading with the corresponding level

#### Scenario: Heading detected by font properties

- **WHEN** a paragraph has no heading style but has font size ≥ 14pt, bold weight, and is not part of a table
- **THEN** the system detects it as a potential heading with a confidence score based on the number of matching signals

#### Scenario: Heading detected by numbering pattern

- **WHEN** a paragraph starts with a pattern like "1.", "1.1", "1.1.1", or "Chapter 1"
- **THEN** the system considers this as a heading signal and contributes to the confidence score

#### Scenario: Not a heading

- **WHEN** a paragraph is body text (11pt, normal weight, no heading style, no numbering pattern)
- **THEN** the system does not detect it as a heading

### Requirement: Confidence scoring for detected headings

The system SHALL assign a confidence score between 0.0 and 1.0 to each automatically detected heading. The score SHALL reflect the number and strength of matching signals.

#### Scenario: High confidence heading

- **WHEN** a paragraph matches 4 or more heading signals (e.g., Heading style + bold + large font + numbered)
- **THEN** the confidence score is ≥ 0.9

#### Scenario: Medium confidence heading

- **WHEN** a paragraph matches 2-3 heading signals (e.g., bold + large font, no heading style)
- **THEN** the confidence score is between 0.5 and 0.89

#### Scenario: Low confidence heading

- **WHEN** a paragraph matches only 1 heading signal (e.g., only large font)
- **THEN** the confidence score is between 0.1 and 0.49

#### Scenario: Confidence stored with element

- **WHEN** a heading is detected
- **THEN** the confidence score is stored in the element's `metadata` JSON field under the key `confidence`

### Requirement: Manual heading assignment

The system SHALL allow users to manually assign heading levels (1-6) to any paragraph element that was not automatically detected as a heading.

#### Scenario: Mark paragraph as heading

- **WHEN** a user sends `POST /api/v1/documents/{document}/elements/{element}/assign-heading` with `level: 2`
- **THEN** the system updates the element type to `heading`, sets the heading level to 2, sets confidence to 1.0 (manual), and stores the original element data in metadata

#### Scenario: Reassign heading level

- **WHEN** a user reassigns a detected heading from level 2 to level 3
- **THEN** the system updates the heading level, marks confidence as 1.0, and records the change

#### Scenario: Invalid level rejected

- **WHEN** a user sends a heading level outside the range 1-6
- **THEN** the system returns a 422 validation error

#### Scenario: Ownership required

- **WHEN** a user who does not own the document's project attempts to assign a heading
- **THEN** the system returns 403

### Requirement: Heading hierarchy validation

The system SHALL validate heading hierarchy and detect sequences that skip levels (e.g., Heading 4 appearing before any Heading 3 in the same section).

#### Scenario: Valid hierarchy

- **WHEN** headings appear in order: H1 → H2 → H3 → H2 → H3
- **THEN** no hierarchy warnings are generated

#### Scenario: Skipped level detected

- **WHEN** headings appear in order: H1 → H3 (no H2 in between)
- **THEN** the system generates a warning: "Heading Level 3 appears before any Heading Level 2"

#### Scenario: Multiple skipped levels

- **WHEN** headings appear in order: H1 → H4
- **THEN** the system generates warnings for each skipped level (H2, H3)

#### Scenario: Hierarchy warnings stored

- **WHEN** hierarchy violations are detected
- **THEN** warnings are stored in the analysis metadata and returned via the analysis API

### Requirement: Heading detection results are stored as elements

Detected headings SHALL be stored as `DetectedElement` records with type `heading` and metadata including: confidence, level, signals that matched, font properties, and style name.

#### Scenario: Heading element storage

- **WHEN** a heading is detected in the document
- **THEN** a `DetectedElement` record is created with `type: heading`, the extracted text, the heading level, and metadata containing confidence score and detection signals

#### Scenario: Heading level accessible via API

- **WHEN** a user retrieves analysis results
- **THEN** each heading element includes its level and confidence in the response

### Requirement: Heading detection works with the existing DocxReader

The heading detection service SHALL extend the existing `DocxReader` service to leverage its DOCX parsing capabilities while adding confidence scoring and multi-signal detection.

#### Scenario: Reuse existing parser

- **WHEN** heading detection is triggered
- **THEN** it uses the existing `DocxReader::extractHeadings()` as a baseline and enhances results with additional signal analysis

#### Scenario: Backward compatible

- **WHEN** `DocxReader::extractHeadings()` returns results
- **THEN** the heading detection service can process them without modifying the DocxReader class
