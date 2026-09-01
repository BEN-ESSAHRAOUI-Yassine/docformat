## Purpose

Provides the entry point for the entire document processing pipeline: upload a DOCX file, validate it, store the original immutably, and create database records for tracking.

## ADDED Requirements

### Requirement: Document upload endpoint
The system SHALL provide an API endpoint to upload DOCX files. The endpoint MUST accept multipart/form-data with a single file field. The endpoint MUST require authentication.

#### Scenario: Successful upload
- **WHEN** an authenticated user uploads a valid .docx file under the size limit
- **THEN** the system stores the original in the originals/ directory, creates a Document record with status "uploaded", creates a DocumentVersion record pointing to the stored file, and returns the document metadata

#### Scenario: Upload exceeds size limit
- **WHEN** a user uploads a file exceeding the maximum allowed size
- **THEN** the system returns a 422 validation error with the maximum size in the message

#### Scenario: Upload with invalid MIME type
- **WHEN** a user uploads a file that is not a .docx file
- **THEN** the system returns a 422 validation error indicating only .docx files are accepted

#### Scenario: Upload without authentication
- **WHEN** an unauthenticated user attempts to upload a file
- **THEN** the system returns a 401 unauthorized response

### Requirement: Original document immutability
The system SHALL store the original uploaded file in the originals/ directory. The original file MUST never be modified, overwritten, or deleted by any automated process. Processing MUST operate on working copies only.

#### Scenario: Original file remains unchanged after processing
- **WHEN** a document is uploaded and subsequent processing modifies headings, adds captions, or applies formatting
- **THEN** the original file in originals/ remains byte-identical to the uploaded file

#### Scenario: Working copy isolation
- **WHEN** a document is being processed
- **THEN** all modifications are applied to files in the working/ or versions/ directories, never to originals/

### Requirement: Document validation
The system SHALL validate uploaded DOCX files before accepting them. Validation MUST check: file is a valid ZIP archive, contains [Content_Types].xml, contains word/document.xml, and is not empty.

#### Scenario: Valid DOCX file
- **WHEN** a user uploads a file that is a valid DOCX (ZIP with required parts)
- **THEN** the system accepts the file and creates document records

#### Scenario: Corrupted DOCX file
- **WHEN** a user uploads a file that is a valid ZIP but missing required DOCX parts
- **THEN** the system returns a 422 validation error indicating the file is corrupted or invalid

#### Scenario: Non-ZIP file with .docx extension
- **WHEN** a user uploads a file with .docx extension that is not a valid ZIP archive
- **THEN** the system returns a 422 validation error indicating the file is not a valid DOCX

### Requirement: Document version tracking
The system SHALL create a DocumentVersion record for each uploaded file. The version MUST reference the stored file path, file size, MIME type, and creation timestamp.

#### Scenario: First upload creates version 1
- **WHEN** a user uploads a new document
- **THEN** a Document record is created and a DocumentVersion record with version_number=1 is associated with it

#### Scenario: Subsequent uploads create new versions
- **WHEN** a user uploads a new version of an existing document
- **THEN** a new DocumentVersion record with incremented version_number is created, the previous version is preserved, and the Document's current_version_id is updated

### Requirement: Duplicate detection
The system SHALL detect duplicate uploads based on file hash (SHA-256). If a duplicate is detected, the system MUST NOT create a new document but instead return the existing document's metadata.

#### Scenario: Duplicate file upload
- **WHEN** a user uploads a file with the same SHA-256 hash as an existing document
- **THEN** the system returns the existing document's metadata without creating a new record

### Requirement: Document status tracking
The system SHALL maintain a status field on each Document record. For Sprint 1, the initial status after upload SHALL be "uploaded". Status transitions will be implemented in future sprints.

#### Scenario: Status after upload
- **WHEN** a document is successfully uploaded
- **THEN** the Document record has status "uploaded" and timestamps for created_at and updated_at

### Requirement: File storage structure
The system SHALL use the following directory structure under storage/app/: originals/ (immutable source files), working/ (temporary processing copies), versions/ (persistent version snapshots), exports/ (generated output files), reports/ (quality reports), temporary/ (ephemeral processing files). The filesystem driver MUST be configurable via environment variables.

#### Scenario: Local development storage
- **WHEN** the filesystem driver is set to "local"
- **THEN** all directories are created under storage/app/ on the local filesystem

#### Scenario: S3-compatible production storage
- **WHEN** the filesystem driver is set to "s3"
- **THEN** files are stored on the configured S3-compatible storage service
