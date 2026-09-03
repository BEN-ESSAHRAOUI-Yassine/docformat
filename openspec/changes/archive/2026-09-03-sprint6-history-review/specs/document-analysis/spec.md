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

#### Scenario: Manual page break recorded with origin

- **WHEN** a page break is inserted by a user rather than detected automatically
- **THEN** the page-break element records its origin as `user` in metadata
