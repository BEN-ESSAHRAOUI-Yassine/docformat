## Context

See proposal.md — Why. `DocumentAction`/`ActionLogger` already exist for audit. `Document` and `Project` use `SoftDeletes`. No encryption, retention, or GDPR endpoints exist. Encryption uses Laravel's built-in `encrypted` cast (APP_KEY-based, no extra dependency). Scheduled work in Laravel 13 lives in `routes/console.php` (currently only `inspire`).

## Goals / Non-Goals

**Goals:**
- `encrypted` casts on sensitive content columns.
- A scheduled retention-purge command for expired/soft-deleted documents and files.
- Security-sensitive audit writes via `ActionLogger`.
- GDPR data-export and right-to-deletion endpoints.

**Non-Goals:**
- Full AES-256 file-encryption of stored DOCX files this sprint (documented deferral).
- Multi-tenant/organization scoping.

## Decisions

**1. `encrypted` cast for content columns.**
Apply Laravel's `encrypted` cast to sensitive `text`/`json` content columns (e.g., document analysis metadata / issue content where needed). APP_KEY-based, reversible, testable via round-trip. Chosen over a custom storage-driver encryption because it's low-risk, standard, and doesn't break existing rows (cast only).

**2. Retention via a scheduled command.**
`DataRetentionService::purge()` deletes documents older than the configured TTL (and their stored files on the `docformat` disk), respecting soft-deletes. Scheduled daily in `routes/console.php`. TTL configurable in `.env`.

**3. Audit through the existing `ActionLogger`.**
Security-sensitive operations already log via `DocumentAction`. Add explicit audit records for deletion/export/access of sensitive endpoints by calling `ActionLogger` at the controller action, keeping one unified audit trail.

**4. GDPR endpoints on the user resource.**
`GET /user/data-export` streams the user's projects/documents as JSON; `DELETE /user/data` hard-deletes the user's data after confirmation. Authorization is the authenticated user's own data.

## Risks / Trade-offs

- **Encrypted cast on existing rows** → Mitigation: apply only where safe (new/empty or nullable columns) and verify round-trip in tests; existing plaintext rows are handled by Laravel's cast (unencrypted read-back allowed).
- **Retention deleting user data** → Mitigation: purge only expired/soft-deleted per TTL; configurable, never immediate-by-default.
- **Audit volume** → Mitigation: log only security-sensitive events, not every read.

## Migration Plan

Additive: optional retention-metadata columns and `encrypted` casts (cast-only). New config keys. Scheduled command in `routes/console.php`. Rollback-safe.
