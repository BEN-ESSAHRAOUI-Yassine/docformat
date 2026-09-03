## Purpose

Detects abbreviation patterns in DOCX documents, builds and maintains an abbreviation registry, validates abbreviation consistency, and generates a structured "LISTE DES ABRÉVIATIONS" (List of Abbreviations) as a document element.

## Requirements

### Requirement: Abbreviation pattern detection

The system SHALL detect abbreviation patterns where a full form is followed by its abbreviation in parentheses, e.g., "Intelligence Artificielle (IA)" or "Application Programming Interface (API)".

#### Scenario: Detect standard abbreviation pattern

- **WHEN** a paragraph contains "Machine Learning (ML)"
- **THEN** the system creates an `Abbreviation` record with abbreviation "ML", full_form "Machine Learning", and the element position

#### Scenario: Detect abbreviation with multiple words

- **WHEN** a paragraph contains "Réseau de Neurones Artificiels (RNA)"
- **THEN** the system creates an `Abbreviation` record with abbreviation "RNA", full_form "Réseau de Neurones Artificiels"

#### Scenario: Detect abbreviation at document start

- **WHEN** the abbreviation appears in a paragraph before any other occurrence of the full form
- **THEN** the system records the first occurrence as the definition point

#### Scenario: Abbreviation already defined

- **WHEN** the same abbreviation (e.g., "IA") appears multiple times with the same full form
- **THEN** the system keeps only one `Abbreviation` record and records all occurrence positions in metadata

### Requirement: Abbreviation registry

The system SHALL maintain an abbreviation registry per document mapping abbreviations to their full forms, definition points, and usage count.

#### Scenario: Registry built from detection

- **WHEN** document analysis completes abbreviation detection
- **THEN** the registry contains all unique abbreviation↔full_form pairs with their first occurrence position and total usage count

#### Scenario: Registry accessible via API

- **WHEN** a user requests the abbreviation registry
- **THEN** the system returns all abbreviations with full form, abbreviation, definition page/element, and usage count

### Requirement: Abbreviation consistency checks

The system SHALL validate abbreviation usage and generate warnings for inconsistencies.

#### Scenario: Undefined abbreviation used

- **WHEN** the document uses an abbreviation (e.g., "DL") that was never defined with a pattern like "Deep Learning (DL)"
- **THEN** the system generates a warning: "Abbreviation 'DL' is used but never defined"

#### Scenario: Inconsistent abbreviation

- **WHEN** the same abbreviation maps to different full forms (e.g., "ML" = "Machine Learning" in one place and "Maximum Likelihood" in another)
- **THEN** the system generates a warning: "Abbreviation 'ML' has inconsistent definitions: 'Machine Learning' vs 'Maximum Likelihood'"

#### Scenario: Duplicate definition

- **WHEN** the same abbreviation+full_form pair is defined multiple times
- **THEN** the system generates an info message: "Abbreviation 'IA' is defined multiple times"

#### Scenario: Unused abbreviation

- **WHEN** an abbreviation is defined but never used elsewhere in the document
- **THEN** the system generates a warning: "Abbreviation 'DL' is defined but never used"

### Requirement: List of abbreviations generation

The system SHALL generate a "LISTE DES ABRÉVIATIONS" (or language-appropriate title) as a structured document element.

#### Scenario: Generate abbreviation list

- **WHEN** the user requests list of abbreviations generation
- **THEN** the system creates a structured element with title "LISTE DES ABRÉVIATIONS" containing all abbreviations sorted alphabetically with their full forms

#### Scenario: Language-aware title

- **WHEN** the document language is English
- **THEN** the list title is "LIST OF ABBREVIATIONS"

#### Scenario: List updates with document changes

- **WHEN** abbreviations are added or removed from the document
- **THEN** the generated list reflects the current state

### Requirement: Abbreviation API endpoints

The system SHALL expose the following endpoints:

- `GET /api/v1/documents/{document}/abbreviations` — list all detected abbreviations
- `GET /api/v1/documents/{document}/abbreviation-issues` — get consistency issues
- `POST /api/v1/documents/{document}/generate-abbreviation-list` — generate the abbreviation list element

#### Scenario: List abbreviations

- **WHEN** an authenticated user sends `GET /api/v1/documents/{document}/abbreviations`
- **THEN** the system returns all abbreviations with abbreviation, full_form, definition_element, usage_count, and occurrences

#### Scenario: Get abbreviation issues

- **WHEN** an authenticated user sends `GET /api/v1/documents/{document}/abbreviation-issues`
- **THEN** the system returns all consistency warnings (undefined, inconsistent, duplicate, unused)

#### Scenario: Ownership required

- **WHEN** a user who does not own the document's project requests abbreviation data
- **THEN** the system returns 403

### Requirement: Original document is never modified

Abbreviation detection and list generation SHALL NOT modify the original uploaded DOCX file.

#### Scenario: Read-only detection

- **WHEN** abbreviation detection runs
- **THEN** the original file hash remains unchanged

#### Scenario: List element is virtual

- **WHEN** a list of abbreviations is generated
- **THEN** it is stored as a `DetectedElement` record, not written into the original DOCX
