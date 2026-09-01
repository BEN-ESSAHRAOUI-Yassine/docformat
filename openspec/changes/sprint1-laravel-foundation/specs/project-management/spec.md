## Purpose

Provides project-level organization for documents. Projects group related documents and establish ownership boundaries for access control.

## ADDED Requirements

### Requirement: Project creation
The system SHALL allow authenticated users to create projects with a name and optional description. The creating user SHALL automatically become the project owner.

#### Scenario: Successful project creation
- **WHEN** an authenticated user submits a valid project name
- **THEN** a new Project record is created with the user as owner, and the project metadata is returned

#### Scenario: Duplicate project name
- **WHEN** a user creates a project with a name that already exists in their projects
- **THEN** the system allows it (project names are not unique globally, only per-owner for organizational purposes)

### Requirement: Project listing
The system SHALL allow authenticated users to list projects they own or are assigned to. Results SHALL be paginated.

#### Scenario: List own projects
- **WHEN** an authenticated user requests their project list
- **THEN** the system returns all projects where the user is the owner, sorted by most recently updated

#### Scenario: Empty project list
- **WHEN** an authenticated user with no projects requests their project list
- **THEN** the system returns an empty array with count 0

### Requirement: Project CRUD
The system SHALL allow project owners to view, update, and delete their projects. Deleting a project MUST NOT delete associated documents — documents become orphaned and must be explicitly reassigned or archived.

#### Scenario: View project details
- **WHEN** a project owner requests a project's details
- **THEN** the system returns the project metadata including document count and last activity timestamp

#### Scenario: Update project
- **WHEN** a project owner updates a project's name or description
- **THEN** the system updates the record and returns the modified project metadata

#### Scenario: Delete project
- **WHEN** a project owner deletes a project
- **THEN** the Project record is soft-deleted, associated documents are NOT deleted, and the project no longer appears in listing

### Requirement: Document-project association
Each document MUST belong to exactly one project. The system SHALL enforce this relationship at the database level with a foreign key constraint.

#### Scenario: Create document within project
- **WHEN** a user uploads a document to a specific project
- **THEN** the Document record is created with the project_id foreign key set

#### Scenario: Move document to different project
- **WHEN** a user moves a document to a different project they own
- **THEN** the Document's project_id is updated and the move is logged

### Requirement: Project access control
The system SHALL enforce that only the project owner can modify or delete the project. Other authenticated users MUST NOT access project resources unless explicitly added as members (future feature).

#### Scenario: Non-owner attempts to update project
- **WHEN** a user who is not the project owner attempts to update the project
- **THEN** the system returns a 403 forbidden response

#### Scenario: Non-owner attempts to delete project
- **WHEN** a user who is not the project owner attempts to delete the project
- **THEN** the system returns a 403 forbidden response
