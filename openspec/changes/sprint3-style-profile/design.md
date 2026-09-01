## Context

Sprint 2 established document analysis with heading detection and the `DetectedElement` model. The platform can now parse DOCX files, extract structural elements, and store them with metadata. Sprint 3 builds the style profile system on top of this foundation — defining what "correct" formatting looks like and detecting violations.

The existing codebase follows Laravel conventions: models with factories, services for business logic, policies for authorization, jobs for queue processing, and API resources for JSON responses. All new code must follow these patterns.

## Goals / Non-Goals

**Goals:**

- StyleProfile model with versioning, CRUD, and JSON import/export
- StyleEngine service with modular, toggleable style checks
- Three enforcement modes (Strict, Recommended, Audit Only)
- Default academic style profile with all specified formatting rules
- Integration with existing document analysis pipeline

**Non-Goals:**

- Frontend UI components (style editor, live preview) — deferred to Sprint 3 frontend tasks
- Applying style corrections to DOCX files — only detection and suggestions in this sprint
- Style profile sharing between users or organizations
- Machine learning-based style detection

## Decisions

### 1. Style rules stored as JSON column

**Decision:** Store style rules as a single JSON column on `style_profiles` rather than normalizing into separate tables.

**Rationale:** Style rules are highly variable (font, size, color, spacing, numbering per element type). A JSON column allows flexible schema evolution without migrations for each new check type. The engine reads the entire profile into memory for comparison anyway, so there's no query-level benefit to normalization.

**Alternative considered:** Normalized `style_rules` table with `element_type`, `property`, `expected_value` columns. Rejected because: (a) adds join complexity for no performance gain, (b) makes import/export harder, (c) profile versioning would require copying all child rows.

### 2. Modular check classes with interface

**Decision:** Each style check (font, size, color, etc.) is a separate class implementing a `StyleCheckInterface` with `check(element, rule): ?Violation`.

**Rationale:** Enables toggleable checks per profile, independent testing, and future extension. The engine iterates over registered checks rather than having a monolithic comparison method.

**Alternative considered:** Single `StyleEngine::compare()` method with switch/if blocks. Rejected because: (a) violates Open/Closed Principle, (b) harder to test individual checks, (c) toggle logic would be scattered.

### 3. Violations stored in separate table

**Decision:** Create a `style_violations` table linked to the analysis record, storing each violation with element reference, check type, expected/actual values, and severity.

**Rationale:** Violations need to be queried by severity, category, and element. Storing them as JSON in the analysis would make filtering impossible. A separate table allows pagination, filtering, and future features like "fix all errors" batch operations.

### 4. Enforcement mode as document-level setting

**Decision:** Enforcement mode is stored on the document model (not the profile), so the same profile can be used in different modes for different documents.

**Rationale:** A user might want to audit an existing document (Audit Only) while auto-fixing a new one (Strict) using the same profile. The mode is a processing-time decision, not a profile definition.

### 5. Default profile is system-owned and read-only

**Decision:** The default academic profile is created by a seeder, has no owner, and cannot be modified. Users can copy it to create their own version.

**Rationale:** Prevents users from accidentally breaking the default. Supports the principle that built-in presets are reference implementations. The copy-on-edit pattern is familiar from design tools.

## Risks / Trade-offs

- **JSON rules complexity** → Mitigated by: clear schema documentation, validation on import, and the engine only reading known check keys
- **Check class proliferation** → Mitigated by: base abstract class with common logic, grouped checks (all font-related checks in one class if needed)
- **Performance with many violations** → Mitigated by: storing violations in DB (not re-computing), pagination in API, and lazy loading in UI
- **Profile versioning storage growth** → Mitigated by: soft deletes, version count limit (e.g., 50 versions per profile), and archival of old versions
