## Why

The platform currently has a complete Laravel backend API (auth, documents, projects, style profiles, analysis) but no user interface. Users cannot interact with the system without a frontend. Sprint 0.5 establishes the React SPA foundation that all future frontend tasks (Sprints 2–8+) will build upon. Without this foundation, no UI work can proceed.

## What Changes

- New `frontend/` directory with React 18 + Vite SPA
- Tailwind CSS + shadcn/ui component library for consistent, accessible UI
- Axios-based API service layer with Sanctum token authentication
- React Router v6 for client-side routing
- Zustand for lightweight state management
- Auth pages: Login, Register, Forgot Password
- App layout: sidebar navigation, header with user menu, main content area
- Dashboard page with document/project overview
- Document list and upload pages
- Style profile list and editor pages
- CORS configuration for frontend dev server
- Vite proxy for API calls during development

## Capabilities

### New Capabilities

- `frontend-spa`: React SPA with Vite, Tailwind CSS, shadcn/ui, React Router, Zustand
- `auth-pages`: Login, Register, Forgot Password pages with token-based auth
- `app-layout`: Sidebar navigation, header, responsive layout
- `api-service`: Axios instance with Sanctum token interceptors, API functions for all endpoints
- `document-pages`: Document list, upload, and detail view pages
- `style-profile-pages`: Style profile list, editor with live preview

### Modified Capabilities

- `cors-config`: Laravel CORS configuration updated for frontend dev server
- `sanctum-config`: Sanctum stateful domains updated for frontend URL

## Impact

- New directory: `frontend/` (React SPA, independent from Laravel)
- New dependency: Node.js 18+ required for frontend development
- Backend changes: `config/cors.php` updated, `config/sanctum.php` stateful domains updated
- No database migrations needed
- No changes to existing API endpoints
- Development workflow: two processes (Laravel on :8000, Vite on :5173)
- Production: frontend builds to `public/spa/` or separate deployment
