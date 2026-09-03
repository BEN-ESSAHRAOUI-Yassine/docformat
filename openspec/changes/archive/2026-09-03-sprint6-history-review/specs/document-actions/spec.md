## Purpose

Provides a durable, traceable record of every document-changing operation and a reversible history with undo/redo so users can confidently review and roll back automated or manual changes.

## ADDED Requirements

### Requirement: Action log records document-changing operations

The system SHALL record every document-changing operation as a `DocumentAction` with: document id, user id, timestamp, action type, element type, element id, origin (automatic or manual), and old/new values where available.

#### Scenario: Log a manual heading assignment

- **WHEN** a user assigns a heading level to a detected element
- **THEN** the system creates a `DocumentAction` with origin `manual`, action type for heading assignment, the element id, and the previous and new values

#### Scenario: Log an automatic style correction

- **WHEN** an automated style correction is applied to an element
- **THEN** the system creates a `DocumentAction` with origin `automatic`, the affected element, and old/new values

#### Scenario: Actions listed for a document

- **WHEN** an owner requests the action history for a document
- **THEN** the system returns the actions ordered newest-first, filterable by action type, origin, or date range, and paginated

#### Scenario: Non-owner is denied history access

- **WHEN** a user who does not own the document requests its action history
- **THEN** the system returns 403

### Requirement: Undo and redo with historical depth

The system SHALL support undoing and redoing actions for a document, with a minimum history depth of 50 actions.

#### Scenario: Undo a reversible action

- **WHEN** an owner requests to undo the most recent reversible action
- **THEN** the system reverses the operation and records it, so the prior value is restored

#### Scenario: Redo an undone action

- **WHEN** an owner requests to redo an action that was previously undone
- **THEN** the system re-applies the operation

#### Scenario: History limited to 50 actions

- **WHEN** more than 50 actions have accumulated for a document
- **THEN** only the most recent 50 actions remain immediately reversible and older actions are culled

#### Scenario: Non-reversible action cannot be undone

- **WHEN** an action is classified as non-reversible (e.g. external analysis)
- **THEN** the system does not undo it and never presents it as having modified the document

### Requirement: Reversibility classification

The system SHALL classify each action as reversible, partially reversible, or non-reversible so the UI can present correct undo behavior.

#### Scenario: Fully reversible action

- **WHEN** an action can be fully reverted to its prior state
- **THEN** the system marks it as fully reversible

#### Scenario: Partially reversible action

- **WHEN** an action leaves some residual effect after reversal
- **THEN** the system marks it as partially reversible

#### Scenario: External analysis is non-reversible

- **WHEN** an external analysis runs that did not modify the document
- **THEN** the system marks the resulting action as non-reversible

### Requirement: Deterministic vs probabilistic distinction in history

The system SHALL record whether a change originated from a deterministic rule or a probabilistic estimate.

#### Scenario: Deterministic action origin recorded

- **WHEN** a deterministic correction (e.g. a style rule violation) is applied
- **THEN** the action records an automatic origin and deterministic basis

#### Scenario: Probabilistic suggestion origin recorded

- **WHEN** a probabilistic suggestion (e.g. possible citation match) is accepted
- **THEN** the action records that it originated from a probabilistic estimate
