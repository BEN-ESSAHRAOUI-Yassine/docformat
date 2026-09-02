# Frontend Architecture Specification

## Overview

The frontend is a React Single Page Application (SPA) served independently from the Laravel backend. It communicates with the backend exclusively through the REST API at `/api/v1/`.

## Authentication

### Token-Based Auth

- Tokens are obtained via `POST /api/v1/login` or `POST /api/v1/register`
- Tokens are stored in `localStorage` under key `token`
- Tokens are sent as `Authorization: Bearer {token}` header on all API requests
- On 401 response, token is cleared and user is redirected to `/login`

### Auth State

- Zustand store manages `token`, `user`, `isAuthenticated`
- Store persists token to localStorage automatically
- Store hydrates from localStorage on page load

### Protected Routes

- Routes wrapped in `<ProtectedRoute>` component
- Unauthenticated users redirected to `/login`
- After login, user redirected to `/dashboard`

## API Communication

### Request Format

```
POST /api/v1/documents
Content-Type: application/json
Accept: application/json
Authorization: Bearer {token}

{ "name": "example.docx", "project_id": "uuid" }
```

### Response Format

```json
{
  "data": { ... },
  "meta": { "current_page": 1, "last_page": 5, "per_page": 15 }
}
```

### Error Format

```json
{
  "message": "Validation failed",
  "errors": {
    "name": ["The name field is required."]
  }
}
```

## Page Requirements

### Login Page

- **Route:** `/login`
- **Form fields:** Email (required), Password (required)
- **Links:** "Forgot password?" → `/forgot-password`, "Register" → `/register`
- **Behavior:** On success, store token + user, redirect to `/dashboard`
- **Error display:** Inline field errors + global error banner
- **Loading:** Disable button, show spinner during request

### Register Page

- **Route:** `/register`
- **Form fields:** Name (required), Email (required), Password (required), Confirm Password (required)
- **Validation:** Passwords must match, email format, min 8 chars password
- **Links:** "Already have an account? Login" → `/login`
- **Behavior:** On success, store token + user, redirect to `/dashboard`
- **Error display:** Inline field errors + global error banner

### Forgot Password Page

- **Route:** `/forgot-password`
- **Form fields:** Email (required)
- **Behavior:** On success, show "Check your email" message
- **Links:** "Back to login" → `/login`

### Dashboard Page

- **Route:** `/dashboard` (protected)
- **Layout:** Full width with cards
- **Content:**
  - Welcome header with user name
  - Stats row: Total Documents, Total Projects, Active Profile
  - Recent Documents card (last 5, with status badges)
  - Quick Actions card (Upload Document, Create Project)
- **Data:** Fetches from `GET /api/v1/user` and `GET /api/v1/projects`

### Document List Page

- **Route:** `/documents` (protected)
- **Layout:** Full width with table
- **Content:**
  - Page header with "Upload Document" button
  - Filter bar: Status dropdown, Search input
  - Table: Name, Status, Uploaded, Analysis Score, Actions (View, Delete)
  - Pagination controls
- **Data:** `GET /api/v1/projects/{project}/documents`
- **Actions:** View → `/documents/{id}`, Delete → confirmation dialog

### Document Upload Page

- **Route:** `/documents/upload` (protected)
- **Layout:** Centered card (max-w-lg)
- **Content:**
  - Project selector dropdown
  - Drag-and-drop zone (accepts .docx only)
  - File preview (name, size)
  - Upload button with progress indicator
- **Behavior:** On success, redirect to document list
- **Data:** `GET /api/v1/projects` for project dropdown

### Document View Page

- **Route:** `/documents/:id` (protected)
- **Layout:** Full width
- **Content:**
  - Document header: name, status, upload date, project
  - Tabs: Analysis, Style Violations, Figures, Tables
  - Analysis tab: summary cards (headings, figures, tables, citations)
  - Style Violations tab: filterable list with severity badges
  - Placeholder for future 3-panel editor
- **Data:** `GET /api/v1/projects/{project}/documents/{id}`

### Style Profile List Page

- **Route:** `/style-profiles` (protected)
- **Layout:** Grid of cards
- **Content:**
  - Page header with "Create Profile" and "Import" buttons
  - Grid: Profile cards with name, type badge, language, rules count
  - Each card: Edit, Export, Delete actions
- **Data:** `GET /api/v1/style-profiles`

### Style Profile Editor Page

- **Route:** `/style-profiles/:id/edit` (protected)
- **Layout:** Two-column (form + preview)
- **Content:**
  - Left: Profile form (name, type, language), Rules editor (property groups)
  - Right: Live preview panel showing formatting changes
  - Top: Save, Export, Cancel buttons
- **Data:** `GET /api/v1/style-profiles/{id}`

## Component Contracts

### AppLayout

```jsx
// Props: none (uses Outlet for child routes)
// Renders: Sidebar + Header + main content area
// Behavior: Sidebar collapses on mobile (< 768px)
```

### Sidebar

```jsx
// Props: none (uses useLocation for active state)
// Renders: Navigation links with icons
// Links: Dashboard, Documents, Style Profiles
// Behavior: Active link highlighted, collapses on mobile
```

### Header

```jsx
// Props: none (uses useAuthStore for user)
// Renders: Page title (from route), user avatar dropdown
// Dropdown: Profile, Settings, Logout
// Behavior: Logout clears token, redirects to /login
```

### FileUpload

```jsx
// Props: onUpload(file), projectId, accept=".docx"
// Renders: Drag-and-drop zone, file preview, upload button
// Behavior: Validates file type, shows progress, calls onUpload
```

### DocumentCard

```jsx
// Props: document { id, name, status, uploaded_at, analysis_score }
// Renders: Card with name, status badge, date, score
// Behavior: Click navigates to document view
```

## State Management

### Auth Store (Zustand)

```javascript
{
  token: string | null,
  user: { id, name, email } | null,
  isAuthenticated: boolean,
  login: (token, user) => void,
  logout: () => void,
  setUser: (user) => void
}
```

### Persisted State

- `token` → localStorage key `token`
- `user` → localStorage key `user`
- Hydrated on store creation

## Error Handling

### HTTP Errors

- **401:** Clear token, redirect to `/login`
- **403:** Show "Access denied" message
- **404:** Show "Not found" message
- **422:** Display validation errors inline
- **500:** Show "Server error" message with retry button

### Network Errors

- Show "Network error" message with retry button
- Disable form submissions during network issues

## Responsive Breakpoints

- **Desktop:** ≥ 1024px — Full sidebar + content
- **Tablet:** 768px–1023px — Collapsed sidebar (icons only)
- **Mobile:** < 768px — Hidden sidebar, hamburger menu
