## Purpose

Provides a replaceable provider abstraction for intelligent text processing so the platform can swap AI/similarity backends without rewriting feature code, with a real external provider and a deterministic offline fallback.

## ADDED Requirements

### Requirement: Provider interface and registry

The system SHALL expose a provider adapter interface for intelligent text operations and a registry to select, configure, and fall back between providers per feature.

#### Scenario: Select the configured provider

- **WHEN** a feature requests text analysis
- **THEN** the system uses the provider configured for that feature

#### Scenario: Fall back on failure

- **WHEN** the primary provider fails or is unavailable
- **THEN** the system falls back to the next configured provider

#### Scenario: Provider registry resolves by name

- **WHEN** a provider name is requested
- **THEN** the registry returns a configured provider implementation

### Requirement: External provider adapter

The system SHALL provide at least one real external provider adapter that calls a remote AI service, configurable via environment settings.

#### Scenario: Groq provider used

- **WHEN** Groq is the configured provider
- **THEN** the system sends requests to the configured Groq endpoint with the configured model and key

### Requirement: Deterministic offline provider

The system SHALL provide a deterministic offline provider that requires no network access, so tests and offline use remain deterministic and private.

#### Scenario: Offline provider returns deterministic results

- **WHEN** the offline provider is used
- **THEN** it returns deterministic, stable results without external network calls

#### Scenario: Tests use the offline provider

- **WHEN** automated tests run
- **THEN** they exercise the deterministic offline provider so no external service is required
