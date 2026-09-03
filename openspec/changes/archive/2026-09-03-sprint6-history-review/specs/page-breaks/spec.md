## Purpose

Lets users insert manual page breaks before structural elements and toggle in-viewer paragraph marks, improving manual control over layout while keeping interface-only indicators out of exported documents.

## ADDED Requirements

### Requirement: Manual page break insertion

The system SHALL let a user insert a page break before a chapter, section, figure, table, or appendix of a document.

#### Scenario: Insert a page break before a chapter

- **WHEN** an owner requests a page break before a chapter element
- **THEN** the system inserts a page-break element before that element and marks its origin as user

#### Scenario: Insert a page break before figures or tables

- **WHEN** an owner requests a page break before a figure or table
- **THEN** the system inserts a page-break element before that figure or table

#### Scenario: Break inserted by user is distinguishable

- **WHEN** a page break was inserted manually
- **THEN** its record marks origin as `user`, distinct from automatically generated breaks

### Requirement: User page break removal

The system SHALL let a user remove a manually inserted page break.

#### Scenario: Remove a user page break

- **WHEN** an owner requests deletion of a manually inserted page break
- **THEN** the system removes the page-break element

#### Scenario: Automated breaks are not removable as user breaks

- **WHEN** a page break was generated automatically
- **THEN** the user break-removal operation does not remove it as a user-controlled break

### Requirement: Paragraph marks toggle

The system SHALL provide a viewer-only toggle to show or hide paragraph marks, equivalent to Word's show/hide formatting marks.

#### Scenario: Show paragraph marks

- **WHEN** an owner enables the paragraph marks toggle
- **THEN** paragraph marks are rendered in the viewer in a subtle, gray style

#### Scenario: Marks are interface-only

- **WHEN** paragraph marks are enabled in the viewer
- **THEN** the marks never appear in an exported document

### Requirement: Page break integrity preservation

The system SHALL preserve document integrity when inserting manual page breaks, never modifying the original file.

#### Scenario: Original document unchanged

- **WHEN** a manual page break is inserted
- **THEN** the original uploaded document is not modified

#### Scenario: Insertion is traceable and reversible

- **WHEN** a manual page break is inserted
- **THEN** the insertion is recorded as a reversible document action
