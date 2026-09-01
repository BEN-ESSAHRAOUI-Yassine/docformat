## Purpose

Provides user authentication, registration, and role-based access control for the document processing platform. Users must authenticate before uploading or managing documents.

## ADDED Requirements

### Requirement: User registration
The system SHALL allow new users to register with email and password. The system MUST send a verification email upon registration. The user account SHALL remain unverified until email verification is completed.

#### Scenario: Successful registration
- **WHEN** a user submits a valid email, name, and password
- **THEN** a new user account is created with role "owner", a verification email is sent, and the user is redirected to the dashboard with a "verify your email" notice

#### Scenario: Duplicate email registration
- **WHEN** a user submits a registration with an email that already exists
- **THEN** the system returns a validation error and no account is created

#### Scenario: Weak password registration
- **WHEN** a user submits a password that does not meet minimum strength requirements
- **THEN** the system returns a validation error listing the password requirements

### Requirement: User login
The system SHALL allow authenticated users to log in with email and password. The system MUST verify the email is verified before granting full access.

#### Scenario: Successful login
- **WHEN** a verified user submits valid credentials
- **THEN** the system creates a session and redirects to the dashboard

#### Scenario: Login with unverified email
- **WHEN** a user with an unverified email submits valid credentials
- **THEN** the system creates a session but restricts access to document upload and processing until email is verified

#### Scenario: Invalid credentials
- **WHEN** a user submits incorrect email or password
- **THEN** the system returns a generic "invalid credentials" error without revealing which field is incorrect

### Requirement: Password reset
The system SHALL allow users to request a password reset via email. The reset link MUST expire after 60 minutes and be single-use.

#### Scenario: Successful password reset request
- **WHEN** a user requests a password reset for a valid email
- **THEN** the system sends a password reset email with a time-limited token

#### Scenario: Expired reset link
- **WHEN** a user clicks a password reset link that has expired
- **THEN** the system displays an error and prompts the user to request a new link

### Requirement: Email verification
The system SHALL send a verification email upon registration. The verification link MUST expire after 48 hours.

#### Scenario: Successful email verification
- **WHEN** a user clicks the verification link in their email
- **THEN** the system marks the email as verified and removes the verification notice

#### Scenario: Expired verification link
- **WHEN** a user clicks a verification link that has expired
- **THEN** the system displays an error and offers to resend the verification email

### Requirement: Role-based access control
The system SHALL assign each user a role. For MVP, roles are "owner" and "admin". The role MUST be stored on the user record and checked in authorization policies.

#### Scenario: Owner role permissions
- **WHEN** a user with role "owner" accesses any resource
- **THEN** the system grants full access to all operations

#### Scenario: Admin role permissions
- **WHEN** a user with role "admin" accesses project or document resources
- **THEN** the system grants access to all operations within their assigned projects

### Requirement: Logout
The system SHALL allow authenticated users to log out, destroying the current session.

#### Scenario: Successful logout
- **WHEN** an authenticated user clicks "Logout"
- **THEN** the session is destroyed and the user is redirected to the login page
