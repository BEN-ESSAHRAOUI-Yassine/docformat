## Why

The platform processes confidential documents, yet has no encryption at rest, no data-retention policy, and only basic per-document access control. This sprint hardens privacy and security: encrypt sensitive content, provide configurable retention and automatic cleanup, audit security-sensitive operations, and offer GDPR-style data export and right-to-deletion.

## What Changes

- Encryption at rest: `encrypted` casts on sensitive content columns so data is stored ciphertext (APP_KEY-based) and transparently decrypted on read.
- Retention: a scheduled purge command in `routes/console.php` that cleans up expired/soft-deleted documents and their files per a configurable TTL.
- Audit + access control: security-sensitive operations (access, export, external processing, deletion) logged to the action log; per-document ownership enforced on security-sensitive endpoints.
- GDPR basics: data export (a user's projects/documents) and right-to-deletion endpoints.
- Documented file-encryption (AES-256) deferral for stored DOCX files (kept as a noted limitation).

## Capabilities

### New Capabilities
- `privacy-security`: Encryption at rest for sensitive content, configurable retention and scheduled cleanup, security-sensitive audit logging, and GDPR data-export/deletion endpoints.

## Impact

- New migration: add `encrypted` casts support on existing content columns (cast only; no schema change) — plus optional columns for retention metadata.
- New service: `DataRetentionService`; new command in `routes/console.php`.
- New controller: `PrivacyController` (data export, delete).
- New endpoints (auth:sanctum): `GET /user/data-export`, `DELETE /user/data`, plus security-sensitive audit writes via existing `ActionLogger`.
- Config in `config/services.php`/`.env` for retention TTL.
- No new external dependency (Laravel's built-in encryption uses APP_KEY).
