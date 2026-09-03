# Sprint 5 — Citations, Bibliography & Abbreviations

## Task 1: Citation Detection Service

**Type:** backend
**Label:** database
**Priority:** high

### Description

Build `CitationDetector` service that parses in-text citations from document paragraphs. Support author-year, numeric, and bracketed patterns. Store detected citations as `Citation` model records and `DetectedElement` records with type `citation`.

### Acceptance Criteria

- [ ] `Citation` model with migration (citations table)
- [ ] `CitationDetector::detect(array $paragraphs): array` returns detected citations
- [ ] Support author-year pattern: `(Smith, 2020)`, `(Dupont et al., 2021)`
- [ ] Support numeric pattern: `[1]`, `[2, 3, 5]`
- [ ] Support bracketed pattern: `[Smith 2020]`
- [ ] Each citation stores: type, raw_text, author, year, numbers, element_index, confidence
- [ ] Unit tests for each pattern type with fixture data

### Technical Notes

- Use regex patterns from design.md
- Extend `DocumentAnalysisService::extractElements()` to call citation detection
- Store as both `DetectedElement` (type=citation) and `Citation` record

---

## Task 2: Bibliography Detection & Model

**Type:** backend
**Label:** database
**Priority:** high

### Description

Build `BibliographyDetector` service that extracts bibliography entries with structured fields. Parse entry type, authors, title, year, journal, volume, issue, pages, DOI, URL. Store as `BibliographyEntry` model records.

### Acceptance Criteria

- [ ] `BibliographyEntry` model with migration (bibliography_entries table)
- [ ] `BibliographyDetector::detect(array $paragraphs): array` returns parsed entries
- [ ] Detect entry type: article, book, chapter, conference, online, thesis, other
- [ ] Extract structured fields: authors (json), title, year, journal, volume, issue, pages, doi, url
- [ ] Preserve unknown fields in `extra_fields` JSON column
- [ ] Store raw_text for each entry
- [ ] Unit tests with realistic bibliography fixtures (APA, IEEE, Vancouver formats)

### Technical Notes

- Multi-pass approach: split entries → classify type → extract fields → fallback to raw
- Extend `DocumentAnalysisService` to call bibliography detection

---

## Task 3: Two-Way Validation Engine

**Type:** backend
**Label:** backend
**Priority:** high

### Description

Build `CitationValidator` service that cross-validates citations against bibliography entries. Detect orphaned citations, uncited entries, inconsistent author/year, and ambiguous matches.

### Acceptance Criteria

- [ ] `CitationValidator::validate(Document $document): ValidationResult`
- [ ] Detect citation without bibliography entry → warning
- [ ] Detect bibliography entry never cited → warning
- [ ] Detect author/year mismatch between citation and matched entry → warning
- [ ] Detect ambiguous citation (matches multiple entries) → warning
- [ ] Return structured result with errors, warnings, info counts
- [ ] API endpoint: `POST /api/v1/documents/{document}/validate-references`
- [ ] API endpoint: `GET /api/v1/documents/{document}/reference-issues`
- [ ] Unit tests for each validation scenario

### Technical Notes

- Link citations to bibliography entries via `bibliography_entry_id` foreign key
- Validation results stored in analysis metadata

---

## Task 4: Duplicate Detection with Merge

**Type:** backend
**Label:** backend
**Priority:** medium

### Description

Build `DuplicateDetector` service that identifies potential duplicate bibliography entries using normalized matching and fuzzy title comparison. Assign confidence scores and provide merge preview.

### Acceptance Criteria

- [ ] `DuplicateDetector::detect(array $entries): array` returns duplicate groups
- [ ] Exact match (same normalized author+title+year) → confidence ≥ 0.9
- [ ] Fuzzy title match (>85% similarity) → confidence 0.7–0.89
- [ ] DOI match → confidence 0.99
- [ ] Flag duplicates: `is_duplicate`, `duplicate_group_id`, `duplicate_confidence`
- [ ] API endpoint: `POST /api/v1/documents/{document}/bibliography/{entry}/merge`
- [ ] Merge preview shows side-by-side field comparison
- [ ] Unit tests with fixture data

### Technical Notes

- Use `similar_text()` or Levenshtein for title similarity
- Normalize: lowercase, strip accents, remove punctuation, normalize whitespace

---

## Task 5: Bibliography Formatting Styles

**Type:** backend
**Label:** backend
**Priority:** medium

### Description

Build `BibliographyFormatter` service that formats bibliography entries in multiple citation styles: APA, MLA, Chicago, IEEE, Vancouver, Custom.

### Acceptance Criteria

- [ ] `BibliographyFormatter::format(BibliographyEntry $entry, string $style): string`
- [ ] APA format: "Author, A. A. (Year). Title of article. Title of Periodical, volume(issue), pages."
- [ ] IEEE format: "[1] A. A. Author, "Title of article," Title of Periodical, vol. volume, no. issue, pp. pages, Year."
- [ ] Vancouver format: numbered list format
- [ ] Custom format: configurable template
- [ ] Unit tests for each style with sample entries

### Technical Notes

- Style configuration can be a simple array/map of format templates
- Return raw formatted string; rendering is frontend concern

---

## Task 6: Abbreviation Detection & Management

**Type:** backend
**Label:** database
**Priority:** medium

### Description

Build `AbbreviationDetector` service that detects abbreviation patterns, builds a registry, and validates consistency.

### Acceptance Criteria

- [ ] `Abbreviation` model with migration (abbreviations table)
- [ ] `AbbreviationDetector::detect(array $paragraphs): array` returns abbreviations
- [ ] Detect pattern: "Full Form (ABBR)"
- [ ] Build registry: abbreviation→full_form with definition_element_index and usage_count
- [ ] Consistency check: same abbreviation → same full_form
- [ ] Detect: undefined abbreviation used, inconsistent definition, duplicate definition, unused abbreviation
- [ ] API endpoint: `GET /api/v1/documents/{document}/abbreviations`
- [ ] API endpoint: `GET /api/v1/documents/{document}/abbreviation-issues`
- [ ] Unit tests with fixture data

### Technical Notes

- Pattern: `/(.+?)\s*\(([A-Z]{2,})\)/` for standard abbreviation detection
- Extend `DocumentAnalysisService` to call abbreviation detection

---

## Task 7: Bidirectional Navigation API

**Type:** backend
**Label:** backend
**Priority:** medium

### Description

Implement API endpoints for navigating between citations and bibliography entries, and for generating the list of abbreviations.

### Acceptance Criteria

- [ ] `GET /api/v1/documents/{document}/citations/{citation}/bibliography-entry` — returns linked entry
- [ ] `GET /api/v1/documents/{document}/bibliography/{entry}/citations` — returns all citing citations
- [ ] `POST /api/v1/documents/{document}/generate-abbreviation-list` — generates abbreviation list element
- [ ] Ownership-based access control on all endpoints
- [ ] 404 when no match found
- [ ] Integration tests for all endpoints

### Technical Notes

- Use existing `DocumentPolicy` for ownership checks
- Abbreviation list stored as `DetectedElement` with type `abbreviation_list`

---

## Task 8: Frontend — Citations, Bibliography & Abbreviations Pages

**Type:** frontend
**Label:** frontend
**Priority:** high

### Description

Build React pages for viewing citations, bibliography entries, and abbreviations with validation issue panels and duplicate merge UI.

### Acceptance Criteria

- [ ] `CitationList.jsx` — table of citations with type badge, raw text, author, year, status (linked/orphan)
- [ ] `BibliographyList.jsx` — table of entries with type, authors, title, year, duplicate flag, cited status
- [ ] `BibliographyDetail.jsx` — entry detail with all fields, list of citing citations, merge UI for duplicates
- [ ] `AbbreviationList.jsx` — table with abbreviation, full_form, consistency status, usage count
- [ ] Validation issues panel showing warnings/errors by category
- [ ] Routes added to `App.jsx`: `/documents/:id/citations`, `/documents/:id/bibliography`, `/documents/:id/abbreviations`
- [ ] Sidebar navigation updated with Citations, Bibliography, Abbreviations links
- [ ] API service functions in `src/api/citations.js`, `src/api/bibliography.js`, `src/api/abbreviations.js`
- [ ] Loading states, empty states, error handling

### Technical Notes

- Follow existing page patterns (DocumentList, StyleProfileList)
- Use existing UI components (Button, Card, Badge, Input, Skeleton)
- Duplicate merge UI: side-by-side field comparison with "Keep This" / "Keep Other" / "Merge" actions

---

## Task 9: Integration Tests & Fixtures

**Type:** testing
**Label:** testing
**Priority:** high

### Description

Write comprehensive tests for citation detection, bibliography parsing, two-way validation, duplicate detection, abbreviation detection, and API endpoints. Create DOCX test fixtures.

### Acceptance Criteria

- [ ] Unit tests for `CitationDetector` with author-year, numeric, bracketed patterns
- [ ] Unit tests for `BibliographyDetector` with APA, IEEE, Vancouver formats
- [ ] Unit tests for `CitationValidator` with all validation scenarios
- [ ] Unit tests for `DuplicateDetector` with exact, fuzzy, DOI matches
- [ ] Unit tests for `AbbreviationDetector` with standard patterns
- [ ] Feature tests for all API endpoints (CRUD, validation, navigation, ownership)
- [ ] At least 30 new tests, all passing
- [ ] DOCX test fixture with mixed citation types and bibliography entries

### Technical Notes

- Use Pest for test creation (`php artisan make:test --pest`)
- Use existing test patterns from Sprint 2/3/4
- Create `tests/Fixtures/citations-sample.docx` for integration tests
