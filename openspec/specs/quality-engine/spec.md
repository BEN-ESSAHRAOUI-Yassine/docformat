## Purpose

Computes a deterministic quality score for a document from its detected issues, across configurable categories (formatting compliance, citation-bibliography consistency, figure/table management, style adherence), producing per-category scores and a weighted overall 0-100 score with error/warning/info counts.

## ADDED Requirements

### Requirement: Deterministic quality scoring

The system SHALL compute a deterministic quality score for a document from its collected issues, so the same inputs always produce the same score.

#### Scenario: Score computed from issues

- **WHEN** quality scoring runs on a document with collected issues
- **THEN** the system returns per-category scores, a weighted overall score (0-100), and error/warning/info counts

#### Scenario: Deterministic output

- **WHEN** the same document is scored twice without changing its issues
- **THEN** the resulting scores are identical

#### Scenario: No issues yields perfect score

- **WHEN** a document has no issues in a category
- **THEN** that category scores 100%

### Requirement: Weighted category scores

The system SHALL score each quality category using configured weights and compute the overall score from them.

#### Scenario: Category weights applied

- **WHEN** scoring runs
- **THEN** formatting compliance (40%), citation-bibliography consistency (25%), figure/table management (20%), and style adherence (15%) weights are applied to the overall score

### Requirement: Modular quality rules

The system SHALL support modular quality rules that can be enabled or disabled and whose severity can be configured.

#### Scenario: Enable and disable a rule

- **WHEN** a rule is disabled
- **THEN** the rule no longer contributes issues or scores

#### Scenario: Configure rule severity

- **WHEN** a rule's severity is changed
- **THEN** its issues are reported at the configured severity

### Requirement: Review-aware scoring

The system SHALL distinguish deterministic findings from probabilistic estimates in scoring so probabilistic items do not lower a deterministic score as facts.

#### Scenario: Probabilistic issues do not score as facts

- **WHEN** scoring runs
- **THEN** probabilistic issues are reported separately and do not reduce the deterministic category score
