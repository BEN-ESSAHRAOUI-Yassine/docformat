# DOCX Library Evaluation

## Overview

Evaluation of DOCX parsing technologies for the Docformat project.

## Architecture Decision

**Hybrid approach**: PHPWord for DOCX operations, Python for NLP/AI (future).

- PHPWord handles: reading, writing, modifying DOCX files
- Python handles: similarity detection, AI analysis, NLP (future sprints)

## PHP Libraries Evaluated

### PHPWord (phpoffice/phpword) — Selected

| Aspect | Details |
|---|---|
| **Version** | 1.4.0 |
| **PHP Requirement** | ^7.1 or ^8.0 |
| **Downloads** | 42M+ |
| **License** | LGPL v3 |
| **Last Release** | Active (2024) |
| **GitHub Stars** | 7.7K |

**Capabilities:**
- Read DOCX (Word2007 format)
- Write DOCX (Word2007 format)
- Read/write ODT, RTF, HTML, PDF (via TCPDF)
- Headings (Title element, levels 1-9)
- Tables (rows, cells, merged cells, styling)
- Images (inline, with styling)
- Headers/Footers
- Page breaks
- Numbering definitions
- Styles (paragraph, character, table)
- Sections with page settings

**Limitations:**
- Complex fields (TOC codes, cross-references) — partial support
- Footnotes/Endnotes — basic support
- Track changes — not supported
- Comments — basic support
- SmartArt, Charts — limited
- Complex numbering — may not fully preserve
- Large documents — memory intensive

**Pros:**
- Pure PHP, no external dependencies
- Well-documented API
- Active community
- Laravel-friendly
- Good for read/modify/write workflows

**Cons:**
- Not all Word features supported
- Some complex formatting may be lost on round-trip
- Memory usage for large documents

### OpenXML-Php

| Aspect | Details |
|---|---|
| **Status** | Less maintained |
| **Approach** | Low-level XML manipulation |
| **Recommendation** | Not selected — too low-level |

### docx-parser

| Aspect | Details |
|---|---|
| **Status** | Limited community |
| **Approach** | Read-only focused |
| **Recommendation** | Not selected — no write capability |

## Python Libraries Evaluated

### python-docx

| Aspect | Details |
|---|---|
| **Version** | Latest |
| **Approach** | Full DOCX read/write |
| **Community** | Very active |

**Capabilities:**
- Better support for complex Word features
- Better table/image handling
- Better style preservation
- More mature Open XML support

**Why NOT selected as primary:**
- Requires separate Python service
- Inter-process communication complexity
- Not natively integrated with Laravel
- Adds deployment complexity

**When Python IS preferred:**
- NLP analysis (future)
- Similarity/plagiarism detection (future)
- AI content analysis (future)
- Advanced text processing

## Comparison Matrix

| Feature | PHPWord | python-docx |
|---|---|---|
| Read DOCX | ✅ | ✅ |
| Write DOCX | ✅ | ✅ |
| Headings (1-6) | ✅ | ✅ |
| Tables | ✅ | ✅ |
| Images | ✅ | ✅ |
| Headers/Footers | ✅ | ✅ |
| Page Breaks | ✅ | ✅ |
| Styles | ✅ | ✅ |
| Numbering | ✅ | ✅ |
| Footnotes | ⚠️ Basic | ✅ |
| Track Changes | ❌ | ⚠️ Basic |
| Complex Fields | ⚠️ Partial | ✅ |
| Laravel Integration | ✅ Native | ❌ Requires service |
| Performance | Good | Good |
| Maintenance | Active | Active |

## Final Recommendation

**PHPWord** is the recommended primary DOCX engine for:

1. **Laravel integration** — native PHP, no IPC needed
2. **Core operations** — read, write, modify headings/tables/images
3. **Round-trip fidelity** — sufficient for formatting operations
4. **Community support** — large user base, good documentation

**Python** will be used for (future sprints):

1. Similarity/plagiarism detection
2. AI content analysis
3. NLP processing
4. Advanced text analysis

## Testing Status

- [x] PHPWord installed (v1.4.0)
- [ ] Round-trip test with complex DOCX
- [ ] Style preservation verification
- [ ] Table/image handling verification
- [ ] Performance benchmarking
