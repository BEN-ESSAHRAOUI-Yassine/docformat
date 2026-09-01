## Purpose

Compares actual document formatting against a selected style profile, detects violations, and produces actionable style issues with severity, location, and recommendations. Supports three enforcement modes: Strict, Recommended, and Audit Only.

## ADDED Requirements

### Requirement: Style engine compares document formatting against profile

The system SHALL read the current formatting of each document element and compare it against the rules defined in the selected style profile. Each comparison SHALL produce a violation when the actual formatting does not match the expected formatting.

#### Scenario: Font family violation detected

- **WHEN** a body paragraph uses Arial font and the profile requires Times New Roman
- **THEN** the system produces a violation with category "font", expected "Times New Roman", actual "Arial"

#### Scenario: Font size violation detected

- **WHEN** a Heading 1 paragraph is 14pt and the profile requires 18pt
- **THEN** the system produces a violation with category "font_size", expected 18, actual 14

#### Scenario: No violation for compliant formatting

- **WHEN** a body paragraph uses Times New Roman 11pt justified (matching the profile)
- **THEN** no violation is produced for that element

### Requirement: Individual style checks

The system SHALL implement modular style checks for: font family, font size, color, bold, italic, underline, all caps, small caps, alignment, indentation, spacing (before/after/line), line spacing, numbering, borders, shading, and paragraph style. Each check SHALL be independently toggleable per profile.

#### Scenario: Check is enabled

- **WHEN** the font family check is enabled in the profile
- **THEN** the system compares font family for all applicable elements

#### Scenario: Check is disabled

- **WHEN** the bold check is disabled in the profile
- **THEN** the system skips bold comparison for all elements

#### Scenario: All checks run by default

- **WHEN** a profile has no explicit check configuration
- **THEN** all checks are enabled by default

### Requirement: Violation severity levels

Each style violation SHALL have a severity: error (must fix), warning (should fix), or info (suggestion). Severity SHALL be configurable per rule in the profile.

#### Scenario: Error severity

- **WHEN** a required rule is violated (e.g., wrong font family in strict mode)
- **THEN** the violation severity is "error"

#### Scenario: Warning severity

- **WHEN** a recommended rule is violated (e.g., spacing slightly off)
- **THEN** the violation severity is "warning"

#### Scenario: Info severity

- **WHEN** a cosmetic preference is violated (e.g., different shade of gray)
- **THEN** the violation severity is "info"

### Requirement: Style violations stored and retrievable

The system SHALL store all detected style violations as records linked to the document analysis. Violations SHALL include: element reference, check type, expected value, actual value, severity, category, description, and recommendation.

#### Scenario: Violations stored after analysis

- **WHEN** style analysis completes on a document
- **THEN** all violations are stored and linked to the analysis record

#### Scenario: Violations retrievable via API

- **WHEN** a user sends `GET /api/v1/documents/{document}/style-violations`
- **THEN** the system returns all violations grouped by severity and category

#### Scenario: Violations filterable

- **WHEN** a user sends `GET /api/v1/documents/{document}/style-violations?severity=error`
- **THEN** the system returns only violations with the specified severity

### Requirement: Style enforcement modes

The system SHALL support three enforcement modes configurable per document and per profile:

- **Strict**: Automatically apply configured rules during processing. Violations are fixed without user review.
- **Recommended** (default): Generate suggestions and present them for user review. Users can accept, reject, or edit each suggestion.
- **Audit Only**: Detect issues but never modify the document. Report violations only.

#### Scenario: Strict mode auto-fixes

- **WHEN** style analysis runs in Strict mode and detects a font family violation
- **THEN** the system automatically corrects the font and logs the change

#### Scenario: Recommended mode suggests

- **WHEN** style analysis runs in Recommended mode and detects a font family violation
- **THEN** the system creates a suggestion for the user to accept or reject

#### Scenario: Audit Only mode reports

- **WHEN** style analysis runs in Audit Only mode and detects a font family violation
- **THEN** the system reports the violation but does not suggest or apply any fix

#### Scenario: Default mode is Recommended

- **WHEN** a new document is created without specifying enforcement mode
- **THEN** the system defaults to Recommended mode

### Requirement: Style analysis triggered from document analysis

The system SHALL allow style analysis to be triggered as part of the document analysis pipeline or as a separate operation.

#### Scenario: Style analysis as part of document analysis

- **WHEN** a user triggers document analysis with style checking enabled
- **THEN** the system runs structure analysis first, then style analysis using the selected profile

#### Scenario: Standalone style analysis

- **WHEN** a user sends `POST /api/v1/documents/{document}/analyze-style` with a profile ID
- **THEN** the system runs style analysis using the specified profile and returns 202

#### Scenario: No profile selected

- **WHEN** a user triggers style analysis without specifying a profile
- **THEN** the system uses the default academic style profile

### Requirement: Ownership-based access control

The system SHALL enforce that only the project owner can trigger style analysis or view style violations for a document.

#### Scenario: Non-owner cannot trigger style analysis

- **WHEN** a user who does not own the document's project sends `POST /api/v1/documents/{document}/analyze-style`
- **THEN** the system returns 403

#### Scenario: Non-owner cannot view violations

- **WHEN** a user who does not own the document's project sends `GET /api/v1/documents/{document}/style-violations`
- **THEN** the system returns 403
