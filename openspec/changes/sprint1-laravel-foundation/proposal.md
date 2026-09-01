## Why

Sprint 0 validated that PHPWord can handle DOCX round-trip processing (read, modify, save with style preservation). Now we need the Laravel application foundation that everything else builds on: authentication, database schema, file storage, document upload, and queue infrastructure. Without this layer, no user-facing features, document processing pipeline, or API endpoints can exist. This is the prerequisite for Sprint 2 (Document Analysis Engine).

## What Changes

- **Laravel project configuration**: PostgreSQL connection, Redis for cache/queue, environment settings, CORS, trusted proxies, application locale set to fr-FR
- **Authentication system**: Login, register, password reset, email verification via Laravel Breeze with role-based access (owner, admin for MVP, designed for expansion to editor, reviewer, viewer)
- **Database schema**: Migrations for users (with role enum), projects, documents (with 11-state status enum), document_versions (immutable file snapshots), document_elements (polymorphic: heading, paragraph, figure, table, caption, source, citation, bibliography, abbreviation, list, page_break, footnote, header, footer, appendix)
- **Core domain models**: Eloquent models with relationships, scopes, casts, factories for Project, Document, DocumentVersion, DocumentElement
- **File storage**: Structured directory system (originals/, working/, versions/, exports/, reports/, temporary/) using Laravel filesystem with local driver for development and S3-compatible for production. Signed URLs for secure downloads.
- **Document upload endpoint**: Strict validation (.docx only for MVP, size limits, MIME check), virus/malware scanning placeholder, duplicate detection. Original stored immutably in originals/. Document + DocumentVersion records created. Status set to "uploaded". DOCX files treated as untrusted input. Rate limiting and CSRF protection.
- **Queue system**: Laravel Horizon configured with separated queues (high, default, document-processing, nlp, external-api, exports, reports). Queue workers with appropriate concurrency. Retry logic, timeout settings, failure handling. Prototype job dispatched to verify pipeline.
- **API routes**: REST endpoints for projects CRUD (list, create, show, update, delete) and documents CRUD (list, create, show, update, delete), document versions (list, show). API versioning (/api/v1/), Form Request validation, authorization policies, consistent JSON error responses, rate limiting per user.

## Capabilities

### New Capabilities
- `user-auth`: Authentication, registration, password reset, email verification, role-based access control
- `document-upload`: Document upload endpoint with validation, storage, and versioning — the entry point for the entire processing pipeline
- `project-management`: Project CRUD, document ownership, multi-tenant document organization

### Modified Capabilities
<!-- Sprint 0 was technical validation only — no existing spec-level behavior changes -->

## Impact

- **New dependencies**: Laravel Breeze (auth scaffolding), Horizon (queue dashboard, requires ext-pcntl for Linux production)
- **Database**: New migrations for 5+ tables (users additions, projects, documents, document_versions, document_elements)
- **Storage**: New directory structure under storage/app/ (partially created in Sprint 0)
- **API**: New routes file, controllers, form requests, policies
- **Queue**: New job classes, Horizon config
- **Security**: DOCX files treated as untrusted input, CSRF protection, rate limiting, file MIME validation, signed URLs
- **Existing code**: Sprint 0 DocxReader/DocxWriter services remain unchanged — consumed by future processing jobs
- **No breaking changes**: This is additive infrastructure; no existing code is modified
