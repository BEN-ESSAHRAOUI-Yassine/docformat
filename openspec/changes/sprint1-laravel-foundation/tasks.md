## 1. Authentication & User Management

- [ ] 1.1 Install Laravel Breeze with the `api` stack, run migrations, and verify default `users` table is created. **Verify:** `php artisan migrate --status` shows all migrations applied.
- [ ] 1.2 Add `role` column (enum: owner, admin) to `users` table via migration. Default to "owner". **Verify:** Migration runs without error, `Schema::hasColumn('users', 'role')` returns true.
- [ ] 1.3 Add `email_verified_at` column (already present from Breeze) and configure email verification in `AuthServiceProvider`. **Verify:** `Auth::shouldVerifyEmail()` returns true.
- [ ] 1.4 Create `UserRole` enum class with `OWNER` and `ADMIN` values. Add cast to `User` model. **Verify:** `UserRole::cases()` returns both values, `User::factory()->create(['role' => UserRole::ADMIN])` works.
- [ ] 1.5 Register Breeze routes (`/api/v1/auth/...`) under versioned prefix. Update route group in `routes/api.php`. **Verify:** `php artisan route:list --path=api/v1/auth` shows login, register, logout, password reset, email verification routes.
- [ ] 1.6 Write Pest tests for registration (success, duplicate email, weak password). **Verify:** `php artisan test --filter=RegistrationTest` passes.
- [ ] 1.7 Write Pest tests for login (success, invalid credentials, unverified email). **Verify:** `php artisan test --filter=LoginTest` passes.
- [ ] 1.8 Write Pest tests for password reset (request, reset with expired token). **Verify:** `php artisan test --filter=PasswordResetTest` passes.
- [ ] 1.9 Write Pest tests for email verification (verify, expired token, resend). **Verify:** `php artisan test --filter=EmailVerificationTest` passes.

## 2. Project Management

- [ ] 2.1 Create `projects` migration: id, name, description (nullable text), owner_id (FK to users), timestamps, soft deletes. **Verify:** Migration runs, `Schema::hasTable('projects')` returns true.
- [ ] 2.2 Create `Project` model with belongsTo User relationship, soft deletes, fillable attributes. **Verify:** `Project::factory()->create()` works, `project->owner` returns User.
- [ ] 2.3 Create `ProjectPolicy` with view/update/delete checks (owner only). Register in `AuthServiceProvider`. **Verify:** `ProjectPolicy::allows('update', $project)` returns true for owner, false for non-owner.
- [ ] 2.4 Create `ProjectController` with index, store, show, update, destroy methods. Use Form Request classes. **Verify:** Controller methods exist and return typed responses.
- [ ] 2.5 Create `StoreProjectRequest` and `UpdateProjectRequest` Form Request classes with validation rules. **Verify:** Validation passes for valid data, fails for missing name.
- [ ] 2.6 Register project routes under `/api/v1/projects` with auth middleware. **Verify:** `php artisan route:list --path=api/v1/projects` shows all CRUD routes.
- [ ] 2.7 Create `ProjectResource` API Resource for consistent JSON response shape. **Verify:** `ProjectResource::make($project)->toArray()` returns expected structure.
- [ ] 2.8 Write Pest tests for project CRUD (create, list, show, update, delete, authorization). **Verify:** `php artisan test --filter=ProjectTest` passes.
- [ ] 2.9 Create `Project` factory with owner relationship. **Verify:** `Project::factory()->create()` creates project with owner.

## 3. Document & Version Models

- [ ] 3.1 Create `documents` migration: id, name, original_filename, project_id (FK), status (enum string), current_version_id (nullable FK), file_hash (sha256, unique index), timestamps, soft deletes. **Verify:** Migration runs, `Schema::hasTable('documents')` returns true.
- [ ] 3.2 Create `document_versions` migration: id, document_id (FK), version_number (integer), file_path, file_size (unsigned big integer), mime_type, uploaded_by (FK to users), timestamps. **Verify:** Migration runs, `Schema::hasTable('document_versions')` returns true.
- [ ] 3.3 Create `document_elements` migration: id, document_id (FK), type (string — heading, table, image, paragraph, etc.), element_index (integer), content (longText, nullable), metadata (json, nullable), timestamps. **Verify:** Migration runs, `Schema::hasTable('document_elements')` returns true.
- [ ] 3.4 Create `Document` model with relationships (belongsTo Project, hasMany versions, hasMany elements, belongsTo currentVersion), status enum cast, soft deletes. **Verify:** `Document::factory()->create()` works, all relationships resolve.
- [ ] 3.5 Create `DocumentVersion` model with belongsTo Document relationship. **Verify:** Factory creates version linked to document.
- [ ] 3.6 Create `DocumentElement` model with belongsTo Document relationship. **Verify:** Factory creates element linked to document.
- [ ] 3.7 Create `DocumentStatus` enum class with all 11 states. Add cast to Document model. **Verify:** `DocumentStatus::cases()` returns all 11 values.
- [ ] 3.8 Create factories for Document, DocumentVersion, DocumentElement. **Verify:** Factories create valid model instances.
- [ ] 3.9 Create `ProjectPolicy` view/update checks for documents (must own project). Create `DocumentPolicy`. **Verify:** Authorization tests pass for document access.

## 4. Document Upload Pipeline

- [ ] 4.1 Create `StoreDocumentRequest` Form Request with DOCX validation (MIME type, file size, required fields). **Verify:** Validation passes for valid .docx, fails for non-docx and oversized files.
- [ ] 4.2 Create `DocumentUploadService` with methods: validateFile, computeHash, storeOriginal, createDocumentRecord, createVersionRecord. Use hash-based storage paths (`originals/{YYYY/MM/DD}/{sha256}.docx`). **Verify:** Upload a test file, confirm stored at correct path, database records created.
- [ ] 4.3 Create `DocumentController` with store method using `DocumentUploadService`. Return `DocumentResource` on success. **Verify:** `POST /api/v1/projects/{project}/documents` stores file and returns document metadata.
- [ ] 4.4 Implement duplicate detection: check file hash before storing. Return existing document metadata if duplicate found. **Verify:** Upload same file twice, second response returns existing document without new record.
- [ ] 4.5 Create `DocumentResource` API Resource with nested version information. **Verify:** Response includes id, name, status, versions array, project relationship.
- [ ] 4.6 Register document routes under `/api/v1/projects/{project}/documents` with auth middleware. **Verify:** `php artisan route:list --path=api/v1/projects` shows document routes.
- [ ] 4.7 Write Pest tests for document upload (success, invalid file, duplicate, size limit, auth required). **Verify:** `php artisan test --filter=DocumentUploadTest` passes.
- [ ] 4.8 Write Pest tests for document CRUD (list, show, delete within project). **Verify:** `php artisan test --filter=DocumentTest` passes.

## 5. Queue & Infrastructure

- [ ] 5.1 Create `queue_jobs` migration (Laravel default). Run `php artisan queue:table && php artisan migrate`. **Verify:** `queue_jobs` table exists, `php artisan queue:work --test` runs without error.
- [ ] 5.2 Create a sample `ProcessDocumentJob` class with handle method, retry logic, and failure tracking. **Verify:** Job class exists, can be dispatched, `php artisan queue:work --once` processes it.
- [ ] 5.3 Configure queue connection in `.env` (QUEUE_CONNECTION=database). Add queue names to config. **Verify:** `php artisan config:show queue.default` returns "database".
- [ ] 5.4 Write Pest test that dispatches `ProcessDocumentJob` and asserts it is queued. **Verify:** `php artisan test --filter=QueueTest` passes.
- [ ] 5.5 Document Horizon installation as a manual step for Linux production (skip on Windows). **Verify:** `docs/sprint1-setup.md` documents the manual Horizon install steps.

## 6. API Response Formatting & Error Handling

- [ ] 6.1 Create base `ApiResource` class with consistent envelope: `{ data: ..., meta: { ... } }`. All API Resources extend it. **Verify:** `ApiResource::make($model)->toArray()` returns enwrapped structure.
- [ ] 6.2 Create `JsonErrorResponse` helper for consistent error responses: `{ message: ..., errors: { ... } }`. **Verify:** Error responses follow consistent shape.
- [ ] 6.3 Add `HandleApiExceptions` trait to `App\Exceptions\Handler` for JSON error responses. **Verify:** 404, 403, 422, 500 errors return JSON, not HTML.
- [ ] 6.4 Write Pest tests for error responses (404, 403, 422 validation, 500). **Verify:** `php artisan test --filter=ApiErrorTest` passes.
- [ ] 6.5 Add rate limiting to auth routes (throttle: 60,1). Add rate limiting to document upload (throttle: 30,1). **Verify:** Exceeding rate limit returns 429 response.

## 7. Full Test Suite & Cleanup

- [ ] 7.1 Run full test suite: `php artisan test --compact`. Fix any failures. **Verify:** All tests pass, count is 34 (Sprint 0) + new Sprint 1 tests.
- [ ] 7.2 Run `vendor/bin/pint --dirty --format agent` to ensure code style compliance. **Verify:** No formatting errors.
- [ ] 7.3 Create `docs/sprint1-setup.md` documenting environment setup, migration steps, Horizon note, and API endpoint list. **Verify:** Documentation file exists and covers all setup steps.
- [ ] 7.4 Mark Sprint 1 tasks as "Done" on Plane. **Verify:** All Sprint 1 tasks show Done status on Plane board.
