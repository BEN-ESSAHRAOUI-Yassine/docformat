## MODIFIED Requirements

### Requirement: Analysis API endpoints

The system SHALL expose the following endpoints:

- `POST /api/v1/documents/{document}/analyze` — trigger analysis
- `GET /api/v1/documents/{document}/analysis` — retrieve latest analysis with detected elements
- `POST /api/v1/documents/{document}/analyze-style` — trigger style analysis with a profile

#### Scenario: Trigger analysis

- **WHEN** an authenticated user sends `POST /api/v1/documents/{document}/analyze`
- **THEN** the system dispatches the analysis job and returns 202 with the analysis ID and status `analyzing`

#### Scenario: Trigger analysis with style checking

- **WHEN** an authenticated user sends `POST /api/v1/documents/{document}/analyze` with `style_profile_id` parameter
- **THEN** the system dispatches the analysis job including style checking and returns 202

#### Scenario: Retrieve analysis results

- **WHEN** an authenticated user sends `GET /api/v1/documents/{document}/analysis`
- **THEN** the system returns the latest analysis with all detected elements, grouped by type

#### Scenario: No analysis exists

- **WHEN** an authenticated user requests analysis for a document that has never been analyzed
- **THEN** the system returns 404
