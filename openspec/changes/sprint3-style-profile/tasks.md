## 1. Database & Models

- [ ] 1.1 Create `style_profiles` migration with columns: name, description, type, language, version, rules (json), is_system, user_id, timestamps. Verify migration runs cleanly.
- [ ] 1.2 Create `style_violations` migration with columns: analysis_id, element_id, check_type, expected_value, actual_value, severity, category, description, recommendation, timestamps. Verify migration runs cleanly.
- [ ] 1.3 Create `StyleProfile` model with casts (rules as array, is_system as boolean), relations (user, violations), and scopes. Verify factory creates valid records.
- [ ] 1.4 Create `StyleViolation` model with casts (expected_value/actual_value as array), relations (analysis, element). Verify factory creates valid records.
- [ ] 1.5 Create `StyleProfileFactory` with states for each type (university, thesis, report, article, custom) and system profiles. Verify factory states produce valid profiles.
- [ ] 1.6 Create `StyleViolationFactory` with states for each severity (error, warning, info). Verify factory states produce valid violations.

## 2. Style Profile CRUD

- [ ] 2.1 Create `StyleProfileController` with index, store, show, update, destroy actions. Verify each endpoint returns correct status codes.
- [ ] 2.2 Create `StoreStyleProfileRequest` with validation: name required, type in:university,thesis,report,article,custom, rules required. Verify validation rejects invalid data.
- [ ] 2.3 Create `UpdateStyleProfileRequest` with validation: name optional, rules optional. Verify validation rejects invalid data.
- [ ] 2.4 Implement profile versioning: update creates new version (increment version number), preserves old data. Verify version increments on update.
- [ ] 2.5 Create `StyleProfileResource` for JSON serialization. Verify API response structure.
- [ ] 2.6 Register style profile routes in `routes/api.php` under auth middleware. Verify routes are accessible.
- [ ] 2.7 Create `StyleProfilePolicy` with viewAny, view, create, update, delete methods. Verify non-owner gets 403.

## 3. Import/Export

- [ ] 3.1 Add export endpoint: `GET /api/v1/style-profiles/{profile}/export` returns JSON file download. Verify downloadable file contains valid profile JSON.
- [ ] 3.2 Add import endpoint: `POST /api/v1/style-profiles/import` accepts JSON file, validates schema, creates profile. Verify import creates valid profile with version 1.
- [ ] 3.3 Add import validation: reject invalid JSON schema with 422 and descriptive errors. Verify validation errors are clear.

## 4. Default Academic Profile

- [ ] 4.1 Create database seeder for default academic style profile with all specified formatting rules (Times New Roman, 11pt body, heading levels 1-6, captions, sources). Verify seeder creates profile with correct rules.
- [ ] 4.2 Implement copy-on-edit: when user modifies system profile, create copy under user's ownership. Verify original remains unchanged.
- [ ] 4.3 Prevent deletion of system profiles (return 403). Verify system profiles cannot be deleted.

## 5. Style Engine Core

- [ ] 5.1 Create `StyleCheckInterface` with `check(DetectedElement $element, array $rule): ?StyleViolation` method signature. Verify interface exists.
- [ ] 5.2 Create `FontFamilyCheck` implementing the interface. Verify it detects font family violations.
- [ ] 5.3 Create `FontSizeCheck` implementing the interface. Verify it detects font size violations.
- [ ] 5.4 Create `FontColorCheck` implementing the interface. Verify it detects color violations.
- [ ] 5.5 Create `BoldCheck`, `ItalicCheck`, `UnderlineCheck` implementing the interface. Verify each detects its respective violation.
- [ ] 5.6 Create `CapitalizationCheck` (all caps, small caps) implementing the interface. Verify it detects capitalization violations.
- [ ] 5.7 Create `AlignmentCheck` implementing the interface. Verify it detects alignment violations.
- [ ] 5.8 Create `IndentationCheck` implementing the interface. Verify it detects indentation violations.
- [ ] 5.9 Create `SpacingCheck` (before/after/line) implementing the interface. Verify it detects spacing violations.
- [ ] 5.10 Create `LineStyleCheck` (line spacing) implementing the interface. Verify it detects line spacing violations.
- [ ] 5.11 Create `StyleEngine` class that registers all checks, iterates over elements, and collects violations. Verify engine produces violations for non-compliant document.

## 6. Enforcement Modes

- [ ] 6.1 Add `enforcement_mode` column to documents table with default 'recommended'. Verify migration runs cleanly.
- [ ] 6.2 Implement Strict mode: engine returns fixes alongside violations. Verify fixes are generated correctly.
- [ ] 6.3 Implement Recommended mode: engine returns only violations as suggestions. Verify suggestions are generated correctly.
- [ ] 6.4 Implement Audit Only mode: engine returns violations with no fixes/suggestions. Verify no fixes are generated.

## 7. Integration & API

- [ ] 7.1 Create `AnalyzeStyleJob` queue job that runs StyleEngine on a document with a profile. Verify job dispatches and processes correctly.
- [ ] 7.2 Add `POST /api/v1/documents/{document}/analyze-style` endpoint with profile_id parameter. Verify endpoint returns 202.
- [ ] 7.3 Add `GET /api/v1/documents/{document}/style-violations` endpoint with severity filter. Verify endpoint returns filtered violations.
- [ ] 7.4 Create `StyleViolationResource` for JSON serialization. Verify API response structure.
- [ ] 7.5 Create `AnalyzeStyleRequest` with validation: profile_id required, must exist. Verify validation rejects invalid profile.
- [ ] 7.6 Add ownership check: non-owner gets 403 for analyze-style and style-violations endpoints. Verify authorization works.

## 8. Testing

- [ ] 8.1 Write feature tests for StyleProfile CRUD (create, read, update, delete, list). Verify all tests pass.
- [ ] 8.2 Write feature tests for import/export (export downloads JSON, import creates profile, invalid import rejected). Verify all tests pass.
- [ ] 8.3 Write feature tests for style engine (violation detection, no false positives, all checks toggleable). Verify all tests pass.
- [ ] 8.4 Write feature tests for enforcement modes (Strict auto-fixes, Recommended suggests, Audit Only reports). Verify all tests pass.
- [ ] 8.5 Write feature tests for API endpoints (analyze-style, style-violations, authorization). Verify all tests pass.
- [ ] 8.6 Write unit tests for each style check class. Verify all tests pass.
- [ ] 8.7 Run full test suite and verify no regressions. Verify all 83+ tests pass.
