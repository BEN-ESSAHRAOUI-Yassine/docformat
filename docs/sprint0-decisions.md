# Sprint 0 — Technical Decisions

## 1. DOCX Engine: PHPWord 1.4.0

**Decision:** PHPWord is the primary DOCX processing library.

**Reasoning:**
- Native PHP, no external service required
- 42M+ Composer downloads, actively maintained
- Supports read/write of headings, paragraphs, tables, images, page breaks, styles
- Good enough for core DOCX round-trip: read → modify → save

**Known Limitations:**
- Title element has no `setText()` — must use reflection to modify heading text
- Heading depth from DOCX reader is returned as string (from regex capture), not int
- Must explicitly register heading styles with `addTitleStyle()` before creating headings — PHPWord does not auto-register them
- No built-in support for: footnotes, endnotes, TOC, citations, bibliography, headers/footers (partial)
- Style preservation through round-trip is partial — some formatting details may be lost

**Alternatives Rejected:**
- python-docx: Better DOCX support, but requires separate Python service + IPC complexity
- OpenXML-Php: Lower-level, more verbose API, smaller community

## 2. Architecture: Hybrid (PHP + Python)

**Decision:** PHP for DOCX operations, Python for future NLP/AI features.

**Reasoning:**
- PHPWord handles the core document engine needs
- Python services will be added later for: similarity detection, AI content analysis, paraphrasing, plagiarism detection
- Keeps the initial stack simple and deployable as a single Laravel app
- Python services communicate via queue/HTTP when needed

## 3. Storage Structure

**Decision:** Laravel filesystem with `docformat` disk, directories under `storage/app/`.

**Directories:**
- `originals/` — Uploaded source documents (immutable, never modified)
- `working/` — Temporary working copies during processing
- `versions/` — Version history of documents
- `exports/` — Generated output documents
- `reports/` — QC reports and analysis results
- `temporary/` — Ephemeral files (test output, processing intermediates)

**Rationale:** Clear separation of concerns, easy backup/restore, supports future multi-user storage.

## 4. Queue Strategy

**Decision:** Laravel queues with separate queue names per concern.

**Queues:**
- `docformat` — Default queue for document operations
- `docformat-processing` — Heavy document processing jobs
- `docformat-nlp` — Future Python NLP jobs
- `docformat-exports` — Export generation
- `docformat-reports` — Report generation

**Note:** Laravel Horizon could not be installed on Windows (requires `ext-pcntl`). Deferred to production (Linux deployment). Basic queue config is functional.

## 5. Test Corpus

**Decision:** Generate fixtures programmatically with PHPWord (not manual DOCX files).

**Fixtures Created:**
- `simple.docx` — 3 headings (H1-H3), 3 paragraphs, no special elements
- `complex.docx` — 12 headings (H1-H4), 2 tables (3x3, 4x2), 1 page break, 6 paragraphs
- `multilingual.docx` — 5 headings, 1 table, 7 paragraphs (French + English)

**Rationale:** Deterministic, reproducible, version-controllable, easy to extend.

## 6. Testing Approach

**Decision:** Pest PHP for all tests, organized as Unit + Feature.

**Test Results (Sprint 0):** 34/34 passing, 93 assertions.

- `DocxReaderTest` — 12 tests: headings, tables, images, page breaks, paragraphs, element counts, error handling
- `DocxWriterTest` — 7 tests: create, load, heading modification, page breaks, save
- `DocxRoundTripTest` — 5 tests: round-trip preservation, heading modification, tables, page breaks, ZIP validity
- `DocxOutputIntegrityTest` — 5 tests: ZIP structure, Content_Types, styles XML, document XML validity

## 7. Spec Deviations

- **Task 2.1 (Fixture Generator):** Only 3 of 6 planned fixtures generated (simple, complex, multilingual). Missing: academic, malformed, oversized. These can be added when those features are built.
- **Task 4.1 (Storage/Queue):** Storage directories created and config updated. Horizon installation deferred. Queue config added but not fully validated with dispatched jobs.
