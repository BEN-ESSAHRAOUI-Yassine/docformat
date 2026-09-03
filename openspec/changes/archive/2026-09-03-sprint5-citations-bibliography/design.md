## Architecture

### Service Layer

```
app/Services/
├── CitationDetector.php          # Parse in-text citations from paragraphs
├── BibliographyDetector.php      # Extract bibliography entries with fields
├── CitationValidator.php         # Two-way validation logic
├── DuplicateDetector.php         # Fuzzy matching + confidence scoring
├── AbbreviationDetector.php      # Pattern-based abbreviation detection
├── BibliographyFormatter.php     # Style-specific formatting (APA, IEEE, etc.)
└── DocxEngine/
    └── CitationParser.php        # Regex patterns for citation extraction
```

### Models

```
app/Models/
├── Citation.php                  # In-text citation record
├── BibliographyEntry.php         # Bibliography entry with structured fields
└── Abbreviation.php              # Abbreviation registry entry
```

### Database Schema

#### `citations` table

| Column | Type | Description |
|--------|------|-------------|
| id | integer | PK |
| document_id | integer | FK → documents |
| document_analysis_id | integer | FK → document_analyses |
| detected_element_id | integer | FK → detected_elements (nullable) |
| type | varchar | author_year, numeric, bracketed, footnote |
| raw_text | text | Original citation text |
| author | varchar | Extracted author (nullable) |
| year | varchar | Extracted year (nullable) |
| numbers | json | Extracted numbers for numeric citations (nullable) |
| element_index | integer | Position in document |
| confidence | decimal | Detection confidence 0.0–1.0 |
| metadata | json | Additional parsed data |
| bibliography_entry_id | integer | FK → bibliography_entries (nullable, set after validation) |
| created_at | datetime | |
| updated_at | datetime | |

#### `bibliography_entries` table

| Column | Type | Description |
|--------|------|-------------|
| id | integer | PK |
| document_id | integer | FK → documents |
| document_analysis_id | integer | FK → document_analyses |
| detected_element_id | integer | FK → detected_elements (nullable) |
| entry_type | varchar | article, book, chapter, conference, online, thesis, other |
| authors | json | Array of author strings |
| title | text | Entry title |
| year | varchar | Publication year |
| journal | varchar | Journal/book name (nullable) |
| publisher | varchar | Publisher (nullable) |
| volume | varchar | Volume (nullable) |
| issue | varchar | Issue (nullable) |
| pages | varchar | Page range (nullable) |
| doi | varchar | DOI (nullable) |
| url | varchar | URL (nullable) |
| access_date | varchar | Access date for online sources (nullable) |
| extra_fields | json | Preserved unknown fields |
| raw_text | text | Original entry text |
| element_index | integer | Position in document |
| is_duplicate | boolean | Flagged as potential duplicate |
| duplicate_group_id | varchar | Group ID for duplicate clusters (nullable) |
| duplicate_confidence | decimal | Confidence score if flagged (nullable) |
| created_at | datetime | |
| updated_at | datetime | |

#### `abbreviations` table

| Column | Type | Description |
|--------|------|-------------|
| id | integer | PK |
| document_id | integer | FK → documents |
| document_analysis_id | integer | FK → document_analyses |
| detected_element_id | integer | FK → detected_elements (nullable) |
| abbreviation | varchar | Short form (e.g., "IA") |
| full_form | text | Full form (e.g., "Intelligence Artificielle") |
| definition_element_index | integer | Element where first defined |
| usage_count | integer | Number of times used after definition |
| occurrences | json | Array of element indices where used |
| is_consistent | boolean | All occurrences map to same full_form |
| inconsistent_forms | json | Array of conflicting full forms (nullable) |
| created_at | datetime | |
| updated_at | datetime | |

### Citation Detection Patterns

```php
// Author-year: (Smith, 2020), (Dupont et al., 2021)
'/\(([A-ZÀ-Ÿ][a-zà-ÿ]+(?:\s+(?:et\s+al\.?|and\s+al\.?))?(?:,\s*[A-ZÀ-Ÿ][a-zà-ÿ]+)*),?\s+(\d{4})\)/'

// Numeric: [1], [2, 3, 5]
'/\[(\d+(?:\s*,\s*\d+)*)\]/'

// Bracketed author-year: [Smith 2020], [Dupont et al. 2021]
'/\[([A-ZÀ-Ÿ][a-zà-ÿ]+(?:\s+(?:et\s+al\.?|and\s+al\.?))?)\s+(\d{4})\]/'
```

### Bibliography Field Extraction

The `BibliographyDetector` uses a multi-pass approach:

1. **Entry splitting**: Split bibliography section into individual entries (by newline or numbered pattern)
2. **Type classification**: Detect entry type from format patterns (DOI → article, URL → online, "In:" → chapter)
3. **Field extraction**: Use regex patterns for each field (authors, year, title, journal, volume, pages, DOI)
4. **Fallback parsing**: For unrecognized formats, store raw text and preserve in `extra_fields`

### Duplicate Detection Algorithm

```php
// 1. Normalize: lowercase, remove accents, strip punctuation, normalize whitespace
// 2. Exact match: same normalized author + title + year → confidence 0.95
// 3. Fuzzy title: same author + year, title similarity > 0.85 → confidence 0.7–0.89
// 4. DOI match: same DOI → confidence 0.99
// 5. Similar author + title, different year → possible edition difference, confidence 0.5–0.7
```

### Integration with DocumentAnalysisService

The analysis pipeline extends as follows:

```
1. Extract elements (existing)
2. Detect headings (existing)
3. Detect figures/tables (existing via FigureDetector/TableDetector)
4. Detect captions (existing via CaptionDetector)
5. NEW: Detect citations → store as DetectedElement + Citation records
6. NEW: Detect bibliography entries → store as DetectedElement + BibliographyEntry records
7. NEW: Detect abbreviations → store as DetectedElement + Abbreviation records
8. NEW: Run two-way validation → generate issues
9. NEW: Run duplicate detection → flag duplicates
10. NEW: Run abbreviation consistency checks → generate issues
```

### Frontend Pages

```
frontend/src/pages/
├── citations/
│   ├── CitationList.jsx          # Table of all citations with status indicators
│   └── CitationDetail.jsx        # Single citation with linked bibliography entry
├── bibliography/
│   ├── BibliographyList.jsx      # Table of all entries with duplicate flags
│   └── BibliographyDetail.jsx    # Entry detail with citation links and merge UI
└── abbreviations/
    └── AbbreviationList.jsx      # Table of abbreviations with consistency status
```

### API Endpoints

```
GET    /api/v1/documents/{document}/citations
GET    /api/v1/documents/{document}/citations/{citation}/bibliography-entry
GET    /api/v1/documents/{document}/bibliography
GET    /api/v1/documents/{document}/bibliography/{entry}/citations
POST   /api/v1/documents/{document}/validate-references
GET    /api/v1/documents/{document}/reference-issues
POST   /api/v1/documents/{document}/bibliography/{entry}/merge
GET    /api/v1/documents/{document}/abbreviations
GET    /api/v1/documents/{document}/abbreviation-issues
POST   /api/v1/documents/{document}/generate-abbreviation-list
```

### Key Design Decisions

1. **No external providers**: Citation/bibliography parsing is deterministic regex-based, not probabilistic. No external API calls needed.
2. **Read-only detection**: All detection is read-only; no DOCX modification occurs during analysis.
3. **Structured storage**: Citations and bibliography entries get both `DetectedElement` records (for the unified element tree) and dedicated model records (for rich querying and validation).
4. **Confidence scoring**: Duplicate detection uses confidence scores to help users prioritize review, not to make automatic decisions.
5. **Merge is user-initiated**: The system flags duplicates but never auto-merges; the user must explicitly confirm.
6. **Language-aware**: Abbreviation list titles and citation format labels respect the document language setting.
