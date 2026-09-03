## Purpose

Detects in-text citations and bibliography entries in DOCX documents, validates consistency between citations and bibliography (two-way), identifies duplicate references with confidence scores, supports multiple bibliography formatting styles, and provides bidirectional navigation between citations and their bibliography entries.

## Requirements

### Requirement: Citation detection using multiple patterns

The system SHALL detect in-text citations using multiple pattern types: author-year (e.g., "(Smith, 2020)"), numeric (e.g., "[1]", "[2,3]"), bracketed author-year (e.g., "[Smith 2020]"), and footnote-based references.

#### Scenario: Detect author-year citation

- **WHEN** a paragraph contains text like "(Dupont, 2021)" or "(Dupont et al., 2021)"
- **THEN** the system creates a `Citation` record with type `author_year`, extracted author "Dupont", year "2021", and the element position

#### Scenario: Detect numeric citation

- **WHEN** a paragraph contains text like "[1]" or "[2, 5, 7]"
- **THEN** the system creates `Citation` records with type `numeric`, extracted numbers [1, 2, 5, 7], and the element position

#### Scenario: Detect bracketed author-year

- **WHEN** a paragraph contains text like "[Smith 2020]" or "[Dupont et al. 2021]"
- **THEN** the system creates a `Citation` record with type `bracketed`, extracted author and year

#### Scenario: Citation stored with metadata

- **WHEN** a citation is detected
- **THEN** the system stores the raw text, citation type, extracted components (author, year, numbers), element index, and confidence score in the `Citation` model

### Requirement: Bibliography entry detection with structured fields

The system SHALL detect bibliography entries and extract structured fields: authors, title, year, journal/book, publisher, volume, issue, pages, DOI, URL, access date, and entry type.

#### Scenario: Detect a bibliography entry

- **WHEN** the document contains a bibliography section with entries like "Smith, J. (2020). Artificial Intelligence Review, 15(2), 123-145."
- **THEN** the system creates a `BibliographyEntry` record with extracted fields: authors=["Smith, J."], title="Artificial Intelligence Review", year=2020, volume=15, issue=2, pages="123-145"

#### Scenario: Preserve unknown fields

- **WHEN** a bibliography entry contains fields not in the standard set (e.g., custom notes, ISBN)
- **THEN** the system preserves these fields in a JSON `extra_fields` column rather than discarding them

#### Scenario: Bibliography entry type detection

- **WHEN** a bibliography entry is parsed
- **THEN** the system classifies it as one of: article, book, chapter, conference, online, thesis, other

### Requirement: Two-way citation-to-bibliography validation

The system SHALL validate that every in-text citation has a corresponding bibliography entry and vice versa.

#### Scenario: Citation without bibliography entry

- **WHEN** the document contains a citation "(Martin, 2019)" but no bibliography entry for Martin 2019 exists
- **THEN** the system generates a warning: "Citation (Martin, 2019) has no corresponding bibliography entry"

#### Scenario: Bibliography entry never cited

- **WHEN** the document contains a bibliography entry "Bernard, L. (2020). ..." but no in-text citation references it
- **THEN** the system generates a warning: "Bibliography entry (Bernard, 2020) is never cited in the document"

#### Scenario: Matching citation and entry

- **WHEN** a citation "(Smith, 2020)" exists and a bibliography entry "Smith, J. (2020). ..." exists
- **THEN** the system links them and records the match with high confidence

#### Scenario: Inconsistent author/year

- **WHEN** a citation references "Smith, 2020" but the bibliography entry shows "Smith, 2019"
- **THEN** the system generates a warning: "Citation (Smith, 2020) does not match bibliography entry year (2019)"

### Requirement: Ambiguous citation detection

The system SHALL detect ambiguous citations where a citation could match multiple bibliography entries.

#### Scenario: Multiple entries for same author-year

- **WHEN** the bibliography contains two entries "Smith, J. (2020). ..." and "Smith, A. (2020). ..."
- **THEN** the system generates a warning: "Citation (Smith, 2020) is ambiguous — matches 2 bibliography entries"

### Requirement: Duplicate bibliography detection with confidence scores

The system SHALL identify potential duplicate bibliography entries using multiple signals and assign a confidence score.

#### Scenario: High-confidence duplicate

- **WHEN** two bibliography entries have the same normalized author, title, and year
- **THEN** the system flags them as duplicates with confidence ≥ 0.9

#### Scenario: Fuzzy title match

- **WHEN** two entries have the same author and year but titles with >85% similarity
- **THEN** the system flags them as potential duplicates with confidence between 0.7 and 0.89

#### Scenario: Duplicate review options

- **WHEN** duplicates are detected
- **THEN** the system provides options: "Keep Both", "Merge" (with field preview), "Delete Selected", "Ignore"

#### Scenario: Merge preview

- **WHEN** the user selects "Merge" for duplicate entries
- **THEN** the system shows a side-by-side comparison of all fields, highlighting which values will be retained

### Requirement: Bibliography formatting styles

The system SHALL support formatting bibliography entries in multiple citation styles: APA, MLA, Chicago, IEEE, Vancouver, and Custom.

#### Scenario: Format entry in APA style

- **WHEN** a bibliography entry is formatted in APA style
- **THEN** the output follows APA rules: "Author, A. A. (Year). Title of article. Title of Periodical, volume(issue), pages."

#### Scenario: Format entry in IEEE style

- **WHEN** a bibliography entry is formatted in IEEE style
- **THEN** the output follows IEEE rules: "[1] A. A. Author, "Title of article," Title of Periodical, vol. volume, no. issue, pp. pages, Year."

#### Scenario: Custom style configuration

- **WHEN** the user selects "Custom" bibliography style
- **THEN** the system allows configuring format template, separator, and field order

### Requirement: Bidirectional citation/bibliography navigation

The system SHALL provide API endpoints for navigating between citations and bibliography entries.

#### Scenario: Get bibliography entry for citation

- **WHEN** a user requests the bibliography entry for a specific citation
- **THEN** the system returns the matched bibliography entry with all fields

#### Scenario: Get all citations for bibliography entry

- **WHEN** a user requests all citations referencing a specific bibliography entry
- **THEN** the system returns all matching citations with their element positions and page numbers

#### Scenario: No match found

- **WHEN** a citation has no matching bibliography entry
- **THEN** the system returns 404 with a message indicating no entry was found

### Requirement: Citation and bibliography API endpoints

The system SHALL expose the following endpoints:

- `GET /api/v1/documents/{document}/citations` — list all detected citations
- `GET /api/v1/documents/{document}/bibliography` — list all bibliography entries
- `POST /api/v1/documents/{document}/validate-references` — run two-way validation
- `GET /api/v1/documents/{document}/reference-issues` — get validation issues
- `POST /api/v1/documents/{document}/bibliography/{entry}/merge` — merge duplicate entries
- `GET /api/v1/documents/{document}/citations/{citation}/bibliography-entry` — get linked entry
- `GET /api/v1/documents/{document}/bibliography/{entry}/citations` — get all citations for entry

#### Scenario: List citations

- **WHEN** an authenticated user sends `GET /api/v1/documents/{document}/citations`
- **THEN** the system returns all citations with type, raw text, extracted components, and element position

#### Scenario: Run validation

- **WHEN** an authenticated user sends `POST /api/v1/documents/{document}/validate-references`
- **THEN** the system runs two-way validation and returns counts of errors, warnings, and info messages

#### Scenario: Ownership required

- **WHEN** a user who does not own the document's project requests citation or bibliography data
- **THEN** the system returns 403

### Requirement: Analysis pipeline integration

The system SHALL integrate citation, bibliography, and abbreviation detection into the existing `DocumentAnalysisService` pipeline.

#### Scenario: Analysis includes citation detection

- **WHEN** document analysis is triggered
- **THEN** the system runs citation detection, bibliography detection, and abbreviation detection after structural analysis completes

#### Scenario: Detection results stored as elements

- **WHEN** citations and bibliography entries are detected
- **THEN** they are stored as `DetectedElement` records with types `citation` and `bibliography`, linked to the analysis

#### Scenario: Original document unchanged

- **WHEN** citation/bibliography detection runs
- **THEN** the original DOCX file is never modified; detection is read-only analysis
