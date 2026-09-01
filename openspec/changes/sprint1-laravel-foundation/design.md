## Context

Sprint 0 validated PHPWord for DOCX round-trip processing. The project has:
- Laravel 13.29.0 with Pest PHP for testing
- PHPWord 1.4.0 installed
- DocxReader/DocxWriter services in `app/Services/DocxEngine/`
- 3 DOCX test fixtures with 34 passing tests
- Storage directories partially created (originals, working, versions, exports, reports, temporary)
- SQLite for database (needs PostgreSQL for production)
- Queue config with docformat queue names (Horizon not yet installed)

Sprint 1 builds the Laravel foundation that Sprint 2+ depends on.

## Goals / Non-Goals

**Goals:**
- Establish database schema for core domain (projects, documents, versions, elements)
- Implement authentication with role-based access
- Create document upload pipeline with immutable original storage
- Configure queue infrastructure for background processing
- Build REST API layer with consistent patterns

**Non-Goals:**
- Frontend UI (React/Vue) — API-only for MVP
- Document analysis engine (Sprint 2)
- Similarity/plagiarism detection (Sprint 7+)
- AI content analysis (Sprint 7+)
- PDF export (future)
- Multi-user collaboration (future — roles designed for it but not implemented)

## Decisions

### 1. Database: SQLite for dev, PostgreSQL for production

**Decision:** Use SQLite locally (already configured), PostgreSQL via env config for production.

**Rationale:** SQLite requires zero setup for development. PostgreSQL is the production target per the project spec. The schema uses standard SQL that works on both.

**Alternatives considered:**
- PostgreSQL everywhere: Rejected — requires Docker/PG install for local dev, adds friction
- MySQL: Rejected — PostgreSQL is the specified production database

### 2. Authentication: Laravel Breeze (API only)

**Decision:** Install Laravel Breeze with the `api` stack only (no Blade views). This gives login, register, password reset, email verification, and Sanctum token management out of the box.

**Rationale:** Breeze is lightweight, well-maintained, and follows Laravel conventions. The API stack gives us token-based auth suitable for a future SPA or mobile app. Fortify was considered but Breeze includes Fortify's features plus scaffolding.

**Alternatives considered:**
- Fortify only: Rejected — more setup, less scaffolding
- Jetstream: Rejected — too opinionated, includes Teams which we don't need
- Custom auth: Rejected — reinventing the wheel for standard flows

### 3. API versioning: Route prefix

**Decision:** Use `/api/v1/` prefix for all API routes. Version via route group, not via headers or content negotiation.

**Rationale:** Simple, explicit, easy to test. When v2 is needed, add a new route group. Controllers can be shared or forked as needed.

**Alternatives considered:**
- Header-based versioning: Rejected — harder to test, less visible
- URI without version: Rejected — breaking changes require careful migration

### 4. Document storage: Hash-based paths

**Decision:** Store files using `{originals|working|versions}/{YYYY/MM/DD}/{sha256}.{ext}` path structure. Database records reference the path, not the filename.

**Rationale:** Hash-based paths prevent collisions, avoid filename issues, and make deduplication natural. Date prefix provides organic directory sharding. Original filenames are preserved in the database only.

**Alternatives considered:**
- UUID-based paths: Rejected — less human-readable for debugging
- Original filename paths: Rejected — collision risk, encoding issues

### 5. Document status: Enum with 11 states

**Decision:** Use a string enum column with states: uploaded, queued, analyzing, analysis_completed, processing, review_required, ready_for_export, exporting, completed, failed, archived.

**Rationale:** The full status model is needed for the processing pipeline. Even though Sprint 1 only uses "uploaded", designing the complete enum now avoids schema changes later.

**Alternatives considered:**
- Start with fewer states, add later: Rejected — schema migration on production data is risky
- Boolean flags (is_processing, is_complete): Rejected — doesn't model the pipeline well

### 6. Filesystem: Laravel filesystem abstraction

**Decision:** Use Laravel's `Storage` facade with configurable driver (local for dev, S3 for production). All file operations go through the filesystem service, never direct PHP file operations.

**Rationale:** Driver-agnostic, testable with in-memory filesystem, easy to switch to S3 in production.

### 7. Queue: Database driver with Horizon

**Decision:** Use database queue driver for development (already configured), Redis for production. Install Horizon for queue management and monitoring.

**Rationale:** Database queue works without Redis for local dev. Horizon provides the dashboard and worker management for production. Horizon requires ext-pcntl which is unavailable on Windows — deferred to Linux production.

### 8. Validation: Form Request classes

**Decision:** Use dedicated Form Request classes for all API endpoints. Validation logic lives in request classes, not controllers.

**Rationale:** Follows Laravel conventions, keeps controllers thin, makes validation testable, provides reusable validation rules.

### 9. Authorization: Policy classes

**Decision:** Use Laravel Policy classes for authorization. One policy per model (ProjectPolicy, DocumentPolicy). Controllers call `$this->authorize()` or use Form Request `authorize()` method.

**Rationale:** Laravel's built-in authorization system, clean separation, testable.

## Risks / Trade-offs

- **[Risk] Horizon on Windows:** Horizon requires ext-pcntl which is unavailable on Windows. **Mitigation:** Skip Horizon installation during development. Use `queue:work` directly. Install Horizon on Linux production only.
- **[Risk] SQLite vs PostgreSQL differences:** Some features (JSON columns, enums) may behave differently. **Mitigation:** Use standard SQL. Test on both where possible.
- **[Risk] File storage switching:** Local to S3 migration may require path migration. **Mitigation:** Use hash-based paths from the start. Store paths in database, not hardcoded.
- **[Trade-off] No frontend:** API-only means no immediate visual verification. **Mitigation:** Use tools like Postman/Insomnia for testing. Frontend comes in a later sprint.
- **[Trade-off] 11-state enum upfront:** More complex than needed for Sprint 1. **Mitigation:** Enum column is just a string — no performance cost, and avoids future migrations.

## Migration Plan

1. Install Breeze, run migrations
2. Create new migrations for projects, documents, document_versions, document_elements
3. Build models, factories, policies
4. Build controllers and routes
5. Test with Pest
6. Configure Horizon (Linux only)
7. Run full test suite
8. Deploy to staging

## Open Questions

- Should DocumentElement be polymorphic or a separate table per element type? (Decision: polymorphic for flexibility — one table, type column, JSON metadata)
- Should we use API Resources for response formatting? (Decision: yes — consistent JSON shape, easy to extend)
