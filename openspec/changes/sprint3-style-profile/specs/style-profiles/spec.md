## Purpose

Manages configurable style profiles that define formatting rules for documents. Profiles support versioning, import/export, and multiple preset types (university, thesis, report, article, custom). Each profile contains a collection of style rules specifying expected formatting for headings, body text, captions, sources, and other element types.

## ADDED Requirements

### Requirement: Style profile CRUD

The system SHALL allow authenticated users to create, read, update, and delete style profiles. Each profile SHALL include: name, description, type (university, thesis, report, article, custom), language, version number, rules (JSON), and ownership.

#### Scenario: Create a style profile

- **WHEN** an authenticated user sends `POST /api/v1/style-profiles` with name, type, and rules
- **THEN** the system creates the profile with version 1 and returns 201 with the profile data

#### Scenario: List style profiles

- **WHEN** an authenticated user sends `GET /api/v1/style-profiles`
- **THEN** the system returns all profiles owned by the user, including built-in presets

#### Scenario: Get a style profile

- **WHEN** an authenticated user sends `GET /api/v1/style-profiles/{profile}`
- **THEN** the system returns the profile with its full rules

#### Scenario: Update a style profile

- **WHEN** an authenticated user sends `PUT /api/v1/style-profiles/{profile}` with modified rules
- **THEN** the system creates a new version of the profile (incrementing version number) and returns 200

#### Scenario: Delete a style profile

- **WHEN** an authenticated user sends `DELETE /api/v1/style-profiles/{profile}`
- **THEN** the system soft-deletes the profile (preserving history) and returns 204

#### Scenario: Cannot delete built-in profiles

- **WHEN** a user attempts to delete a system-provided preset profile
- **THEN** the system returns 403

### Requirement: Style profile versioning

The system SHALL maintain a version history for each style profile. Every modification SHALL create a new version while preserving previous versions.

#### Scenario: Version incremented on update

- **WHEN** a profile at version 3 is updated
- **THEN** the new version is 4 and the previous version is preserved

#### Scenario: Version history accessible

- **WHEN** a user requests `GET /api/v1/style-profiles/{profile}/versions`
- **THEN** the system returns all versions with their rules and creation timestamps

### Requirement: Style profile import/export

The system SHALL support exporting style profiles as JSON and importing profiles from JSON files.

#### Scenario: Export profile as JSON

- **WHEN** a user sends `GET /api/v1/style-profiles/{profile}/export`
- **THEN** the system returns a downloadable JSON file containing the profile rules and metadata

#### Scenario: Import profile from JSON

- **WHEN** a user sends `POST /api/v1/style-profiles/import` with a valid JSON profile file
- **THEN** the system creates a new profile from the imported data with version 1

#### Scenario: Import rejects invalid JSON

- **WHEN** a user imports a JSON file that does not match the profile schema
- **THEN** the system returns 422 with validation errors describing the schema mismatch

### Requirement: Default academic style profile

The system SHALL provide a built-in default academic style profile with the following formatting rules:

- Font: Times New Roman
- Body text: 11pt, black, justified
- Heading 1: 18pt, black, all caps, center
- Chapter/Level 1: 26pt, black, all caps, center, border, shading
- Level 2: 16pt, small caps, left, indent 0.25
- Level 3: 14pt, left, indent 0.5, numbering 1./2./3.
- Level 4: 12pt, indent 0.75, numbering 1.1/1.2
- Level 5: 12pt, indent 1.0, numbering 1.1.1/1.1.2
- Level 6: 12pt, indent 1.0, numbering 1.1.1.1
- Captions: 10pt, gray, center
- Sources: 10pt, gray, italic, underline, right

#### Scenario: Default profile available

- **WHEN** a new user creates their first document
- **THEN** the default academic style profile is available for selection

#### Scenario: Default profile is read-only

- **WHEN** a user attempts to modify the default academic profile
- **THEN** the system creates a copy of the profile under the user's ownership and applies changes to the copy

### Requirement: Style profile types

The system SHALL support the following profile types: university, thesis, report, article, and custom. Each type SHALL have sensible default rules that can be customized.

#### Scenario: Profile type determines defaults

- **WHEN** a user creates a profile with type "thesis"
- **THEN** the system pre-fills rules appropriate for thesis formatting

#### Scenario: Custom type starts empty

- **WHEN** a user creates a profile with type "custom"
- **THEN** the system creates the profile with minimal default rules

### Requirement: Ownership-based access control

The system SHALL enforce that only the profile owner can modify or delete a profile. Built-in profiles are read-only for all users.

#### Scenario: Non-owner cannot modify profile

- **WHEN** a user who does not own a profile sends `PUT /api/v1/style-profiles/{profile}`
- **THEN** the system returns 403

#### Scenario: Non-owner can read profile

- **WHEN** a user who does not own a profile sends `GET /api/v1/style-profiles/{profile}`
- **THEN** the system returns the profile data (read-only)
