## Purpose

Provides paraphrasing and synonym suggestions behind the provider adapter, always presented as suggestions, never auto-applied, and never designed to conceal plagiarism — suggesting citation when a source is detected.

## ADDED Requirements

### Requirement: Paraphrase suggestions

The system SHALL propose paraphrased wording for sentences and phrases as suggestions the user can accept, reject, edit, or ignore.

#### Scenario: Propose a paraphrase

- **WHEN** the user requests a paraphrase for a phrase
- **THEN** the system returns an alternative with a confidence indication

#### Scenario: Suggestions only

- **WHEN** a paraphrase is proposed
- **THEN** it is never auto-applied and requires the user's decision

### Requirement: Synonym suggestions

The system SHALL suggest synonyms for individual words or phrases without changing technical meaning.

#### Scenario: Suggest synonyms

- **WHEN** the user requests synonyms for a word
- **THEN** the system returns a set of alternatives that preserve the technical meaning

### Requirement: Citation-first paraphrasing

The system SHALL not use paraphrasing to conceal a detected source; when text appears to originate from a source, it shall suggest citation first.

#### Scenario: Source detected suggests citation

- **WHEN** text matches a detected source
- **THEN** the system suggests a citation rather than only a paraphrase
