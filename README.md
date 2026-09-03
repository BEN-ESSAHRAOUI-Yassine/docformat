<p align="center">
  <strong>DocFormat</strong> — Automated Document Processing & Quality Control Platform
</p>

<p align="center">
  <a href="#overview">Overview</a> ·
  <a href="#features">Features</a> ·
  <a href="#architecture">Architecture</a> ·
  <a href="#tech-stack">Tech Stack</a> ·
  <a href="#quick-start">Quick Start</a> ·
  <a href="#api-reference">API</a> ·
  <a href="#roadmap">Roadmap</a>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-13-FF2D20?style=flat-square&logo=laravel&logoColor=white" alt="Laravel 13">
  <img src="https://img.shields.io/badge/PHP-8.3-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP 8.3">
  <img src="https://img.shields.io/badge/React-19-61DAFB?style=flat-square&logo=react&logoColor=black" alt="React 19">
  <img src="https://img.shields.io/badge/Vite-8-646CFF?style=flat-square&logo=vite&logoColor=white" alt="Vite 8">
  <img src="https://img.shields.io/badge/Tailwind_CSS-4-38BDF8?style=flat-square&logo=tailwindcss&logoColor=white" alt="Tailwind CSS 4">
  <img src="https://img.shields.io/badge/Tests-256+-22C55E?style=flat-square" alt="Tests">
  <img src="https://img.shields.io/badge/License-MIT-green?style=flat-square" alt="License">
</p>

---

## Overview

**DocFormat** is a document-intelligence platform built for academic, technical, and professional documents. It analyzes structure, enforces configurable formatting rules, validates citations and bibliography, detects abbreviations, and produces quality reports — all while preserving complete user control over every modification.

The original uploaded document is never modified. All processing operates on working copies, every automated change is reversible, and deterministic findings are clearly separated from probabilistic estimates.

### Core Principles

- **Immutability** — The original `.docx` is never overwritten; all operations target working copies.
- **Reversibility** — Every material change is traceable, undoable, and user-approvable.
- **User Control** — Automated suggestions require explicit acceptance; bulk actions require confirmation.
- **Deterministic vs Probabilistic** — Style violations are facts; plagiarism/AI detection are estimates.
- **Graceful Failure** — One element's error never corrupts the entire document.
- **Privacy-Aware** — External processing is explicit; document content stays out of logs.

---

## Features

### Document Intelligence

| Capability | Status | Details |
|------------|--------|---------|
| Heading Detection (1–6) | Implemented | Multi-signal analysis: style, font size, bold, capitalization, numbering, spacing, indentation. Confidence scoring. |
| Hierarchy Validation | Implemented | Detects skipped levels (e.g., H4 before H3). |
| Manual Heading Assignment | Implemented | User can mark any text as Heading 1–6. |
| Structure Visualizer | Planned | Outline panel with clickable navigation. |

### Figures, Tables & Captions

| Capability | Status | Details |
|------------|--------|---------|
| Figure Detection | Implemented | Auto-detect images with metadata (type, dimensions, watermark). |
| Table Detection | Implemented | Row/column counts, header detection, content extraction. |
| Caption Detection | Implemented | Label, number, element type, section tracking. |
| Source Detection | Implemented | Figure/table source attribution. |
| Page Integrity | Planned | Keep figure + caption + source on same page. |

### Citations & Bibliography

| Capability | Status | Details |
|------------|--------|---------|
| Citation Detection | Implemented | Author-year `(Smith, 2020)`, numeric `[1]`, bracketed `[Smith 2020]`. |
| Bibliography Parsing | Implemented | APA, IEEE, Vancouver formats. Entry type classification (article, book, chapter, conference, online, thesis). |
| Two-Way Validation | Implemented | Orphaned citations, uncited entries, author/year mismatches, ambiguous matches. |
| Duplicate Detection | Implemented | Exact (≥0.9), fuzzy title (>0.85 similarity), DOI (0.99). Side-by-side merge preview. |
| Bibliography Formatting | Implemented | APA, IEEE, Vancouver, MLA, Chicago styles. |
| Bidirectional Navigation | Implemented | Citation → Bibliography entry, Bibliography → Citing citations. |

### Abbreviations

| Capability | Status | Details |
|------------|--------|---------|
| Pattern Detection | Implemented | `Full Form (ABBR)` pattern recognition. |
| Registry Building | Implemented | Abbreviation → full form mapping with usage counts. |
| Consistency Checks | Implemented | Detects inconsistent definitions, unused abbreviations. |

### Style Profiles

| Capability | Status | Details |
|------------|--------|---------|
| Default Profile | Implemented | Times New Roman, 11pt body, configurable headings 1–6. |
| Custom Profiles | Implemented | Create, edit, import (JSON), export. |
| Style Analysis | Implemented | Multi-signal violation detection (font, size, color, bold, italic, alignment, spacing). |

### Quality Control

| Capability | Status | Details |
|------------|--------|---------|
| Style Violations | Implemented | Errors, warnings, info per document element. |
| Reference Issues | Implemented | Citation/bibliography validation summary. |
| Quality Reports | Planned | Executive summary, per-domain breakdowns. |
| Similarity/Plagiarism | Planned | Local + external provider adapters. |
| AI Content Analysis | Planned | Probabilistic detection, never definitive. |

---

## Architecture

```
                         ┌─────────────────────┐
                         │    React Frontend    │
                         │  (SPA + Tailwind v4) │
                         └──────────┬──────────┘
                                    │ REST API (Sanctum)
                         ┌──────────▼──────────┐
                         │   Laravel Backend    │
                         │   (PHP 8.3)          │
                         └──┬───────┬───────┬──┘
                            │       │       │
               ┌────────────▼┐ ┌────▼────┐ ┌▼──────────────┐
               │  PostgreSQL  │ │  Redis  │ │   Queue/Horizon │
               │  (Database)  │ │ (Cache) │ │  (Background)   │
               └──────────────┘ └─────────┘ └───────┬────────┘
                                                    │
                                    ┌───────────────┴───────────────┐
                                    │       Processing Services      │
                                    │                                │
                              ┌─────▼─────┐                  ┌───────▼───────┐
                              │ DOCX Engine│                  │  NLP/AI Engine │
                              │ (PhpWord)  │                  │  (Python)     │
                              └───────────┘                  └───────────────┘
```

### Service Layer

```
app/Services/
├── DocumentAnalysisService.php    # Orchestrates full analysis pipeline
├── HeadingDetectionService.php    # Multi-signal heading detection
├── CaptionDetector.php            # Figure/table caption detection
├── CaptionService.php             # Caption management
├── CitationDetector.php           # In-text citation parsing (author-year, numeric, bracketed)
├── BibliographyDetector.php       # Bibliography entry extraction & classification
├── CitationValidator.php          # Two-way citation ↔ bibliography validation
├── DuplicateDetector.php          # Fuzzy + DOI duplicate detection with confidence scoring
├── AbbreviationDetector.php       # Pattern-based abbreviation detection & consistency
├── BibliographyFormatter.php      # APA/IEEE/Vancouver/MLA/Chicago formatting
├── NumberingService.php           # Numbering management
├── ListGeneratorService.php       # TOC/LOF/LOT generation
├── PageIntegrityService.php       # Page break management
├── StyleEngine/                   # Style violation detection engine
└── DocxEngine/                    # DOCX read/write abstraction
    ├── DocxReader.php
    ├── DocxWriter.php
    └── CitationParser.php
```

---

## Tech Stack

| Layer | Technology | Version | Purpose |
|-------|-----------|---------|---------|
| **Backend** | Laravel | 13.x | Application framework, REST API, auth, queues |
| **Language** | PHP | 8.3+ | Typed models, enums, constructor promotion |
| **Frontend** | React | 19.x | SPA with React Router v6 |
| **Build** | Vite | 8.x | Frontend bundling, HMR |
| **Styling** | Tailwind CSS | 4.x | Utility-first CSS (via `@tailwindcss/vite`) |
| **State** | Zustand | 5.x | Lightweight client state |
| **HTTP** | Axios | 1.x | API client with token interceptors |
| **Auth** | Laravel Sanctum | 4.x | Token-based API authentication |
| **DOCX** | PhpWord | latest | DOCX parsing and writing |
| **Database** | SQLite/PostgreSQL | — | Local dev / production |
| **Queue** | Redis + Horizon | — | Background document processing |
| **Testing** | Pest | 4.7 | Unit + feature tests (256+ tests, 777 assertions) |
| **Linting** | Laravel Pint | 1.x | PSR-12 code style |
| **AI Tooling** | Laravel Boost | 2.x | Agent-assisted development |

---

## Project Structure

```
docformat/
├── app/
│   ├── Enums/                    # DocumentStatus, AnalysisStatus, etc.
│   ├── Http/Controllers/         # API controllers
│   │   ├── AnalysisController.php
│   │   ├── CitationController.php
│   │   ├── BibliographyController.php
│   │   ├── AbbreviationController.php
│   │   ├── ReferenceController.php
│   │   ├── StyleAnalysisController.php
│   │   └── ...
│   ├── Models/                   # Eloquent models (12 models)
│   │   ├── Document.php
│   │   ├── Citation.php
│   │   ├── BibliographyEntry.php
│   │   ├── Abbreviation.php
│   │   ├── DetectedElement.php
│   │   ├── StyleProfile.php
│   │   └── ...
│   └── Services/                 # Domain services (see Service Layer)
├── database/
│   ├── factories/                # Model factories for testing
│   ├── migrations/               # Schema migrations
│   └── seeders/
├── frontend/                     # React SPA
│   ├── src/
│   │   ├── api/                  # API service functions
│   │   ├── components/           # Reusable UI components
│   │   ├── pages/                # Route pages
│   │   │   ├── citations/
│   │   │   ├── bibliography/
│   │   │   ├── abbreviations/
│   │   │   ├── documents/
│   │   │   ├── style-profiles/
│   │   │   └── auth/
│   │   ├── stores/               # Zustand stores
│   │   └── lib/                  # Utilities (cn, etc.)
│   └── package.json
├── openspec/                     # Product specification
│   ├── DocformatProject.md       # Master spec (3000+ lines)
│   ├── config.yaml
│   └── changes/                  # Sprint planning artifacts
├── routes/
│   └── api.php                   # API routes (v1 prefix)
├── tests/
│   ├── Feature/                  # Feature/integration tests
│   ├── Unit/                     # Unit tests
│   └── fixtures/docx/            # DOCX test fixtures
├── composer.json
└── README.md
```

---

## Quick Start

### Prerequisites

- PHP 8.3+
- Node.js 20+
- Composer
- SQLite (default) or PostgreSQL

### Setup

```bash
# Clone the repository
git clone https://github.com/BEN-ESSAHRAOUI-Yassine/docformat.git
cd docformat

# Install dependencies, generate key, migrate, build frontend
composer run setup
```

### Development

```bash
# Start all services (server + queue + Vite)
composer run dev
```

This launches:
- **Laravel server** on `http://localhost:8000`
- **Queue worker** for background jobs
- **Vite dev server** on `http://localhost:5173`

### Manual Setup (Alternative)

```bash
# Backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate

# Frontend
cd frontend
npm install
npm run dev

# Start backend separately
php artisan serve
```

### Run Tests

```bash
# Full suite
vendor/bin/pest

# With coverage summary
vendor/bin/pest --compact

# Specific test file
vendor/bin/pest tests/Feature/CitationApiTest.php

# Frontend lint
cd frontend && npm run lint
```

---

## API Reference

All endpoints require a Sanctum Bearer token. Base URL: `/api/v1`

### Authentication

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/register` | Create account |
| POST | `/login` | Get token |
| POST | `/logout` | Revoke token |

### Projects

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/projects` | List projects |
| POST | `/projects` | Create project |
| GET | `/projects/{id}` | Get project |
| DELETE | `/projects/{id}` | Delete project |

### Documents

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/projects/{project}/documents` | List documents |
| POST | `/projects/{project}/documents` | Upload document |
| GET | `/documents/{id}` | Get document |
| DELETE | `/documents/{id}` | Delete document |

### Analysis

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/documents/{id}/analyze` | Start analysis |
| GET | `/documents/{id}/analysis` | Get analysis results |
| POST | `/documents/{id}/analyze-style` | Run style analysis |
| GET | `/documents/{id}/style-violations` | Get style violations |

### Citations & Bibliography

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/documents/{id}/citations` | List citations |
| GET | `/documents/{id}/citations/{id}/bibliography-entry` | Get linked entry |
| GET | `/documents/{id}/bibliography` | List bibliography entries |
| GET | `/documents/{id}/bibliography/{id}/citations` | Get citing citations |
| POST | `/documents/{id}/validate-references` | Run two-way validation |
| GET | `/documents/{id}/reference-issues` | Get validation issues |

### Abbreviations

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/documents/{id}/abbreviations` | List abbreviations |
| GET | `/documents/{id}/abbreviation-issues` | Get consistency issues |

### Style Profiles

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/style-profiles` | List profiles |
| POST | `/style-profiles` | Create profile |
| PUT | `/style-profiles/{id}` | Update profile |
| DELETE | `/style-profiles/{id}` | Delete profile |
| POST | `/style-profiles/import` | Import from JSON |
| GET | `/style-profiles/{id}/export` | Export to JSON |

---

## Configuration

### Environment Variables

Key variables in `.env`:

```env
APP_NAME=DocFormat
APP_URL=http://localhost:8000
FRONTEND_URL=http://localhost:5173

DB_CONNECTION=sqlite
QUEUE_CONNECTION=database
SESSION_DRIVER=database
```

### CORS & Sanctum

For local development with the React frontend on port 5173:

- `config/cors.php`: `FRONTEND_URL` defaults to `http://localhost:5173`
- `config/sanctum.php`: Stateful domains include `localhost:5173`

### Default Style Profile

The platform ships with a default academic style:

- **Font:** Times New Roman
- **Body:** 11pt, black, justified
- **Heading 1:** 18pt, all caps, centered
- **Heading 2:** 16pt, small caps, left
- **Heading 3:** 14pt, numbered `1. / 2. / 3.`
- **Captions:** 10pt, gray, centered
- **Sources:** 10pt, gray, italic, underlined, right-aligned

Profiles are fully customizable and importable/exportable as JSON.

---

## Development

### Code Standards

- PHP 8.3 with constructor property promotion
- Explicit return types and type hints on all methods
- PSR-12 enforced via Laravel Pint
- Pest for all tests (unit + feature)
- React with Tailwind CSS v4 on the frontend

### Useful Commands

```bash
# Format code
vendor/bin/pint --dirty --format agent

# Run specific tests
vendor/bin/pest --filter=testName

# Fresh migration (dev only)
php artisan migrate:fresh --seed

# Clear all caches
php artisan optimize:clear

# Check routes
php artisan route:list --path=api
```

### Testing Patterns

```php
// Feature test with Sanctum auth
$user = User::factory()->create();
$project = Project::factory()->create(['owner_id' => $user->id]);
$document = Document::factory()->create(['project_id' => $project->id]);
$token = $user->createToken('test-token')->plainTextToken;

$response = $this->withHeader('Authorization', 'Bearer ' . $token)
    ->getJson("/api/v1/documents/{$document->id}/citations");

$response->assertOk();
```

---

## Roadmap

### Completed

- [x] **Sprint 0** — Project scaffolding, Laravel setup, auth
- [x] **Sprint 0.5** — React SPA foundation, Tailwind, routing, auth pages
- [x] **Sprint 1** — Document upload, project CRUD, DOCX parsing
- [x] **Sprint 2** — Document analysis engine, heading detection, figures, tables
- [x] **Sprint 3** — Style profiles, style analysis engine, violation detection
- [x] **Sprint 4** — Page integrity, numbering, lists, document visualizer
- [x] **Sprint 5** — Citations, bibliography, abbreviations, two-way validation

### Planned

- [ ] **Sprint 6** — Table of Contents, List of Figures/Tables generation
- [ ] **Sprint 7** — DOCX export with applied corrections
- [ ] **Sprint 8** — Modification history, undo/redo (50-action depth)
- [ ] **Sprint 9** — Similarity/plagiarism detection (provider adapters)
- [ ] **Sprint 10** — AI content analysis (probabilistic, never definitive)
- [ ] **Sprint 11** — Grammar, correction, paraphrasing suggestions
- [ ] **Sprint 12** — Quality reports (PDF/DOCX export)

---

## Privacy & Security

- Documents are processed on working copies; originals are immutable
- Encrypted transport (HTTPS in production)
- Per-user/project isolation via ownership policies
- Sensitive document content is never written to application logs
- External AI/NLP processing is explicit and user-approved
- Configurable document retention and deletion workflows

---

## License

This project is licensed under the [MIT License](https://opensource.org/licenses/MIT).

---

<p align="center">
  Built with Laravel 13 · React 19 · Tailwind CSS 4
</p>
