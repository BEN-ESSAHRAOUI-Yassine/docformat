## 1. Backend Configuration

- [ ] 1.1 Update `config/cors.php` to allow `http://localhost:5173` origin and enable credentials. Verify CORS headers appear in API responses.
- [ ] 1.2 Update `config/sanctum.php` to add `localhost:5173` to stateful domains. Verify Sanctum cookie auth works from frontend.
- [ ] 1.3 Clear config cache: `php artisan config:clear`. Verify no config errors.

## 2. Vite + React Project Setup

- [ ] 2.1 Create `frontend/` directory and initialize Vite React project: `npm create vite@latest . -- --template react`. Verify dev server starts on port 5173.
- [ ] 2.2 Configure Vite proxy to forward `/api` requests to `http://localhost:8000`. Verify API calls work through proxy.
- [ ] 2.3 Install core dependencies: `axios`, `react-router-dom`, `zustand`, `lucide-react`. Verify imports work.
- [ ] 2.4 Configure React Router in `App.jsx` with routes for auth, dashboard, documents, style-profiles. Verify route navigation works.

## 3. Tailwind CSS + shadcn/ui Setup

- [ ] 3.1 Install Tailwind CSS with Vite plugin. Configure `tailwind.config.js` with content paths. Verify Tailwind classes apply.
- [ ] 3.2 Initialize shadcn/ui: `npx shadcn@latest init`. Configure `components.json` with style and paths. Verify `cn()` utility works.
- [ ] 3.3 Add shadcn/ui components: `button`, `input`, `card`, `dialog`, `table`, `badge`, `avatar`, `dropdown-menu`, `skeleton`, `separator`. Verify components import correctly.
- [ ] 3.4 Add global styles: Inter font import, base typography, CSS variables for shadcn/ui theming. Verify visual consistency.

## 4. API Service Layer

- [ ] 4.1 Create `src/api/client.js` with Axios instance, base URL `/api/v1`, request interceptor for Sanctum token from localStorage, response interceptor for 401 redirect. Verify token is sent in requests.
- [ ] 4.2 Create `src/api/auth.js` with `login(email, password)`, `register(name, email, password, password_confirmation)`, `logout()`, `forgotPassword(email)`, `getUser()`. Verify each function calls correct endpoint.
- [ ] 4.3 Create `src/api/documents.js` with `list()`, `upload(file)`, `get(id)`, `delete(id)`, `analyze(id)`, `getAnalysis(id)`. Verify each function calls correct endpoint.
- [ ] 4.4 Create `src/api/projects.js` with `list()`, `create(data)`, `get(id)`, `update(id, data)`, `delete(id)`. Verify each function calls correct endpoint.
- [ ] 4.5 Create `src/api/styleProfiles.js` with `list()`, `create(data)`, `get(id)`, `update(id, data)`, `delete(id)`, `export(id)`, `import(file)`. Verify each function calls correct endpoint.

## 5. Auth Store & Hooks

- [ ] 5.1 Create `src/stores/authStore.js` with Zustand: `token`, `user`, `isAuthenticated`, `login(token, user)`, `logout()`, `setUser(user)`. Persist token to localStorage. Verify state updates correctly.
- [ ] 5.2 Create `src/hooks/useAuth.js` convenience hook that wraps authStore. Provides `user`, `isAuthenticated`, `login`, `logout`, `loading`. Verify hook works in components.
- [ ] 5.3 Create `src/components/ProtectedRoute.jsx` that redirects to `/login` if not authenticated. Verify protected routes work.

## 6. Auth Pages

- [ ] 6.1 Create `src/pages/auth/Login.jsx` with email/password form, "Forgot password?" link, "Register" link. Handle loading state and error display. Verify login flow works end-to-end.
- [ ] 6.2 Create `src/pages/auth/Register.jsx` with name/email/password/confirm form, "Already have account? Login" link. Handle validation errors. Verify registration flow works.
- [ ] 6.3 Create `src/pages/auth/ForgotPassword.jsx` with email form, success message. Handle loading and errors. Verify password reset request works.

## 7. App Layout

- [ ] 7.1 Create `src/components/layout/AppLayout.jsx` with sidebar + header + main content area. Sidebar fixed 256px width, header 64px height. Verify layout renders correctly.
- [ ] 7.2 Create `src/components/layout/Sidebar.jsx` with navigation links: Dashboard, Documents, Style Profiles. Active state highlighting. User info at bottom. Verify navigation works.
- [ ] 7.3 Create `src/components/layout/Header.jsx` with page title, user avatar dropdown (Profile, Settings, Logout). Verify dropdown works.
- [ ] 7.4 Create `src/components/common/PageHeader.jsx` with title, description, and optional action buttons. Verify reusability across pages.
- [ ] 7.5 Create `src/components/common/Skeleton.jsx` loading component and `src/components/common/ErrorMessage.jsx` error component. Verify both render correctly.

## 8. Dashboard Page

- [ ] 8.1 Create `src/pages/Dashboard.jsx` with welcome message, recent documents list (last 5), quick actions (Upload Document, Create Project). Verify dashboard loads and displays data.
- [ ] 8.2 Create `src/components/documents/DocumentCard.jsx` with document name, status badge, upload date, analysis score. Verify card renders with correct data.

## 9. Document Pages

- [ ] 9.1 Create `src/pages/documents/DocumentList.jsx` with table of documents, status filters, sort by date/name, pagination. Verify list loads and displays documents.
- [ ] 9.2 Create `src/components/documents/FileUpload.jsx` with drag-and-drop zone, file type validation (.docx only), upload progress, preview. Verify upload works end-to-end.
- [ ] 9.3 Create `src/pages/documents/DocumentUpload.jsx` wrapping FileUpload with project selection and upload handler. Verify upload creates document and redirects to list.
- [ ] 9.4 Create `src/pages/documents/DocumentView.jsx` with document info header, analysis results, violations list. Placeholder for future 3-panel editor. Verify document detail loads.

## 10. Style Profile Pages

- [ ] 10.1 Create `src/pages/style-profiles/StyleProfileList.jsx` with cards for each profile, type badges (university, thesis, etc.), create/import buttons. Verify list loads.
- [ ] 10.2 Create `src/pages/style-profiles/StyleProfileEditor.jsx` with profile form (name, type, language), rules editor (property groups), live preview panel, save/export buttons. Verify editor loads and saves.

## 11. Integration Testing

- [ ] 11.1 Test complete auth flow: register → login → access dashboard → logout. Verify no broken states.
- [ ] 11.2 Test document flow: upload → list → view → delete. Verify document lifecycle works.
- [ ] 11.3 Test style profile flow: create → edit → export → import. Verify profile lifecycle works.
- [ ] 11.4 Test error handling: 401 unauthorized, 404 not found, 422 validation errors. Verify user-friendly error display.
- [ ] 11.5 Test responsive layout: sidebar collapses on mobile, pages stack vertically. Verify basic responsiveness.
