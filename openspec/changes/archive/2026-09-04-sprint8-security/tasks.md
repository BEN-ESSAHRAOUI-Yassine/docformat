## 1. Encryption at Rest

- [ ] 1.1 Add `encrypted` casts to sensitive content columns on applicable models (e.g., Document analysis/issue content). Verify round-trip encryption/decryption in a unit test.

## 2. Retention & Cleanup

- [ ] 2.1 Create `DataRetentionService` with `purge()` that removes documents older than the configured TTL and deletes their stored files on the `docformat` disk. Verify purge removes expired docs and files.
- [ ] 2.2 Add retention TTL config (`config/services.php` + `.env.example`). Verify config readable.
- [ ] 2.3 Schedule the purge command daily in `routes/console.php`. Verify scheduling registered.

## 3. Audit & Access Control

- [ ] 3.1 Add explicit audit writes via `ActionLogger` for security-sensitive operations (document deletion, export, external/AI processing). Verify an action record is created.
- [ ] 3.2 Enforce ownership on security-sensitive endpoints (already via policy). Verify 403 for non-owner.

## 4. GDPR Data Export & Deletion

- [ ] 4.1 Create `PrivacyController` with `exportData` returning the authenticated user's projects and documents as JSON. Verify 200 for the owner and correct data shape.
- [ ] 4.2 Add `deleteData` hard-deleting the authenticated user's projects/documents. Verify data removed.
- [ ] 4.3 Register routes: `GET /api/v1/user/data-export`, `DELETE /api/v1/user/data`. Verify routes accessible.

## 5. Tests & Verification

- [ ] 5.1 Write a unit test for the encrypted-cast round-trip. Verify plaintext is stored ciphertext and reads back correctly.
- [ ] 5.2 Write feature tests for the retention purge (expired removed, active kept, files deleted). Verify all pass.
- [ ] 5.3 Write feature tests for audit writes on deletion/export and ownership 403. Verify all pass.
- [ ] 5.4 Write feature tests for data-export and right-to-deletion endpoints. Verify all pass.
- [ ] 5.5 Run `vendor/bin/pint --dirty` and `php artisan test --compact`. Verify clean output with no regressions.

## 6. OpenSpec Archive & Plane Sync

- [ ] 6.1 Sync the privacy-security delta spec to main specs and archive `sprint8-security`. Verify change archived.
- [ ] 6.2 Update Plane: move Sprint 8 Task 68 through to Done with evidence comment, and note the change split (63-67 = advanced-intelligence, 68 = security) on the epic for traceability. Verify task and epic reflected.
