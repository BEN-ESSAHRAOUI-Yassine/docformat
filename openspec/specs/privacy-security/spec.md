## Purpose

Protects confidential document content with encryption at rest, configurable retention and automatic cleanup, security-sensitive audit logging, and GDPR-style data export and right-to-deletion.

## ADDED Requirements

### Requirement: Encryption at rest

The system SHALL encrypt sensitive content columns at rest so stored data is not plaintext, decrypting transparently on read.

#### Scenario: Sensitive content stored encrypted

- **WHEN** sensitive content is persisted
- **THEN** the stored value is ciphertext and the model decrypts it on read

#### Scenario: Round-trip preserved

- **WHEN** a model with an encrypted column is saved and reloaded
- **THEN** the value reads back identically

### Requirement: Data retention

The system SHALL support a configurable retention policy and automatically clean up expired or soft-deleted documents and their files.

#### Scenario: Purge expired documents

- **WHEN** the scheduled cleanup runs
- **THEN** expired/soft-deleted documents and their stored files are removed

#### Scenario: Retention configurable

- **WHEN** an administrator configures the retention TTL
- **THEN** cleanup respects the configured retention period

### Requirement: Security-sensitive audit

The system SHALL record security-sensitive operations (access, export, external processing, deletion) to the action log.

#### Scenario: Audit a deletion

- **WHEN** a document is deleted
- **THEN** the action log records the deletion

#### Scenario: Audit an export

- **WHEN** a document is exported
- **THEN** the action log records the export

### Requirement: Data export

The system SHALL let a user export their data (projects and documents) for portability.

#### Scenario: Export user data

- **WHEN** a user requests their data
- **THEN** the system returns their projects and documents

### Requirement: Right to deletion

The system SHALL let a user delete their data.

#### Scenario: Delete user data

- **WHEN** a user requests deletion of their data
- **THEN** the system deletes their projects and documents
