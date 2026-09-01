## Purpose

Orchestrates the parsing of DOCX files into a structured element tree, extracts all detectable document elements, stores analysis results, and manages the analysis lifecycle from pending through completion or failure.

## Requirements

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

### Requirement: Document element extraction

The system SHALL extract all structural elements from a DOCX file and store them with their type, content, position, and metadata. Supported element types SHALL include: heading, paragraph, figure, table, caption, source, citation, bibliography, abbreviation, list, page_break, footnote, header, footer, section, appendix.

#### Scenario: Extract all element types from a complex document

- **WHEN** a DOCX file containing headings, paragraphs, tables, images, captions, lists, and page breaks is analyzed
- **THEN** the system creates a `DetectedElement` record for each element with its type, text content (where applicable), element index, and metadata

#### Scenario: Preserve element ordering

- **WHEN** elements are extracted from the document
- **THEN** each element receives a sequential `element_index` reflecting its position in the document

#### Scenario: Store element metadata

- **WHEN** an element is extracted
- **THEN** the system stores a JSON `metadata` field containing element-specific properties (e.g., font size, style name, indentation for headings; row/column count for tables; image dimensions for figures)

### Requirement: Language must be set before analysis

The system SHALL NOT begin analysis until a language has been explicitly set on the document.

#### Scenario: Analysis blocked without language

- **WHEN** a user requests analysis on a document with no language set
- **THEN** the system returns an error indicating language selection is required

#### Scenario: Default language is fr-FR

- **WHEN** a new document is created
- **THEN** the system defaults the language to `fr-FR`

### Requirement: Analysis results are tied to document version

The system SHALL associate each analysis with a specific document version. Re-analyzing a document creates a new analysis record linked to the current version.

#### Scenario: Analysis linked to current version

- **WHEN** analysis is triggered for a document
- **THEN** the analysis record references the document's current version

#### Scenario: Previous analysis preserved

- **WHEN** a document is re-analyzed
- **THEN** the previous analysis record is preserved (not deleted) and the new analysis is linked to the current version

### Requirement: Analysis API endpoints

The system SHALL expose the following endpoints:

- `POST /api/v1/documents/{document}/analyze` — trigger analysis
- `GET /api/v1/documents/{document}/analysis` — retrieve latest analysis with detected elements

#### Scenario: Trigger analysis

- **WHEN** an authenticated user sends `POST /api/v1/documents/{document}/analyze`
- **THEN** the system dispatches the analysis job and returns 202 with the analysis ID and status `analyzing`

#### Scenario: Retrieve analysis results

- **WHEN** an authenticated user sends `GET /api/v1/documents/{document}/analysis`
- **THEN** the system returns the latest analysis with all detected elements, grouped by type

#### Scenario: No analysis exists

- **WHEN** an authenticated user requests analysis for a document that has never been analyzed
- **THEN** the system returns 404

### Requirement: Ownership-based access control

The system SHALL enforce that only the project owner can trigger or view analysis for a document.

#### Scenario: Non-owner cannot trigger analysis

- **WHEN** a user who does not own the document's project sends `POST /api/v1/documents/{document}/analyze`
- **THEN** the system returns 403

#### Scenario: Non-owner cannot view analysis

- **WHEN** a user who does not own the document's project sends `GET /api/v1/documents/{document}/analysis`
- **THEN** the system returns 403

### Requirement: Original document is never modified

The analysis process SHALL NOT modify the original uploaded DOCX file or any stored document version.

#### Scenario: Original file integrity

- **WHEN** analysis is triggered on a document
- **THEN** the original file hash remains unchanged and the file is not modified

#### Scenario: Working copy used for parsing

- **WHEN** analysis reads the DOCX content
- **THEN** it reads from a temporary working copy, never from the stored original
