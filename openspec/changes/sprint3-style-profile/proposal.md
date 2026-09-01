## Why

The platform needs configurable style profiles to enforce formatting rules across documents. After Sprint 2 established document analysis and heading detection, the next logical step is building the style system that defines what "correct" formatting looks like and detects violations against it. Users need to select from preset profiles (university, thesis, report, article) or create custom profiles, and the system must compare actual document formatting against the selected profile to produce actionable style issues.

## What Changes

- New `StyleProfile` model with full CRUD, versioning (each edit creates a new version), and JSON import/export
- Profile types: university, thesis, report, article, custom
- Default academic style profile with specific formatting rules (Times New Roman, 11pt body, heading levels 1-6 with numbering/indentation, captions, sources)
- `StyleEngine` service that reads current formatting from document elements, compares with the selected profile, and detects violations
- Individual style checks: font family, font size, color, bold, italic, underline, capitalization, alignment, indentation, spacing, line spacing, numbering, borders, shading, paragraph style
- Each violation produces an issue with severity, category, description, location, and recommendation
- Three enforcement modes: Strict (auto-enforce), Recommended (suggest with review, default), Audit Only (detect but never modify)
- Custom style editor UI component with live preview and reset support
- Style profile changes can apply to current document, future documents, or both

## Capabilities

### New Capabilities

- `style-profiles`: Style profile model, CRUD, versioning, import/export, profile types, and default academic profile
- `style-engine`: Style engine core service, individual style checks, violation detection, and enforcement modes

### Modified Capabilities

- `document-analysis`: Style analysis will be triggered as part of the document analysis pipeline (add style check trigger to existing analysis lifecycle)

## Impact

- New database migrations: `style_profiles` table with JSON rules column, `style_violations` table
- New models: `StyleProfile`, `StyleViolation`
- New services: `StyleEngine`, individual check classes
- New API endpoints: style profile CRUD, style analysis trigger, style violations retrieval
- New queue job: `AnalyzeStyleJob` for background style checking
- Modified: `DocumentAnalysisService` to optionally trigger style analysis
- Frontend: new style profile editor component, style violations display
- Dependencies: none new (uses existing Laravel + PHPWord stack)
