## Why

Citation-bibliography consistency is a major pain point for academic writers. Students, researchers, and professionals frequently submit documents with orphaned citations (cited but no bibliography entry), unused bibliography entries (never cited), duplicate references, and inconsistent author/year formatting. Without automated two-way validation, these errors are caught manually — often too late. Sprint 5 introduces the reference engine that detects, validates, and manages citations, bibliography, and abbreviations, adding significant value to the quality control platform.

## What Changes

- **Citation detection**: Parse in-text citations from DOCX paragraphs using multiple patterns (author-year, numeric, bracketed, footnote-based)
- **Bibliography detection**: Extract bibliography entries with structured fields (authors, title, year, journal, DOI, etc.)
- **Two-way validation**: Cross-validate citations against bibliography and vice versa, generating warnings for mismatches
- **Duplicate detection**: Identify potential duplicate bibliography entries with confidence scores and merge preview
- **Bibliography formatting**: Support APA, MLA, Chicago, IEEE, Vancouver, and custom citation styles
- **Abbreviation detection**: Identify abbreviation patterns (e.g., "Intelligence Artificielle (IA)") and build an abbreviation registry
- **List of abbreviations**: Generate "LISTE DES ABRÉVIATIONS" as a structured document element
- **Bidirectional navigation**: API endpoints for citation↔bibliography cross-reference lookup
- **Frontend pages**: Citation list, bibliography list, abbreviation list with validation issues panel

## Capabilities

### New Capabilities

- `citation-bibliography`: Citation detection, bibliography management, two-way validation, duplicate detection, bibliography formatting styles, bidirectional navigation
- `abbreviation-management`: Abbreviation pattern detection, abbreviation registry, consistency checks, list generation

### Modified Capabilities

- `document-analysis`: Extend analysis pipeline to include citation, bibliography, and abbreviation detection as part of the analysis lifecycle

## Impact

- **New migrations**: `citations`, `bibliography_entries`, `abbreviations` tables
- **New services**: `CitationDetector`, `BibliographyDetector`, `CitationValidator`, `DuplicateDetector`, `AbbreviationDetector`, `BibliographyFormatter`
- **New models**: `Citation`, `BibliographyEntry`, `Abbreviation`
- **Extended services**: `DocumentAnalysisService` calls citation/bibliography/abbreviation detection
- **New API endpoints**: validation, duplicate review, merge, navigation
- **Frontend**: New pages for citations, bibliography, abbreviations with issue panels
- **No breaking changes**: All existing functionality preserved
- **DOCX integrity**: No modifications to original documents; detection is read-only analysis
