## Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                      React SPA (Vite)                       │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐   │
│  │  Auth     │  │ Dashboard│  │Documents │  │  Style   │   │
│  │  Pages    │  │  Page    │  │  Pages   │  │ Profiles │   │
│  └────┬─────┘  └────┬─────┘  └────┬─────┘  └────┬─────┘   │
│       │              │              │              │          │
│  ┌────┴──────────────┴──────────────┴──────────────┴─────┐  │
│  │                   React Router v6                      │  │
│  └────────────────────────┬──────────────────────────────┘  │
│                           │                                  │
│  ┌────────────────────────┴──────────────────────────────┐  │
│  │              API Service Layer (Axios)                  │  │
│  │  ┌─────────┐  ┌──────────┐  ┌──────────┐             │  │
│  │  │  auth.js │  │documents │  │  style   │             │  │
│  │  │         │  │   .js    │  │Profiles  │             │  │
│  │  └─────────┘  └──────────┘  └──────────┘             │  │
│  └────────────────────────┬──────────────────────────────┘  │
│                           │                                  │
│  ┌────────────────────────┴──────────────────────────────┐  │
│  │              Zustand Store (Auth State)                 │  │
│  └───────────────────────────────────────────────────────┘  │
└───────────────────────────┬─────────────────────────────────┘
                            │ HTTP/JSON
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                   Laravel API (/api/v1)                      │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐   │
│  │   Auth   │  │ Document │  │  Style   │  │ Analysis │   │
│  │Controller│  │Controller│  │ Profile  │  │Controller│   │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘   │
└─────────────────────────────────────────────────────────────┘
```

## Component Design

### Auth Flow

```
Login Page ──POST /api/v1/login──► Laravel Sanctum
                                      │
                                      ▼
                              Returns { token, user }
                                      │
                                      ▼
                              Zustand store saves token
                              localStorage saves token
                                      │
                                      ▼
                              Axios interceptor adds
                              Authorization: Bearer {token}
                                      │
                                      ▼
                              Redirect to Dashboard
```

### Protected Route Pattern

```jsx
// ProtectedRoute.jsx
function ProtectedRoute({ children }) {
  const { token } = useAuthStore();
  if (!token) return <Navigate to="/login" />;
  return children;
}

// App.jsx
<Route element={<ProtectedRoute />}>
  <Route path="/dashboard" element={<Dashboard />} />
  <Route path="/documents" element={<DocumentList />} />
  // ...
</Route>
```

### API Service Pattern

```js
// api/client.js
const api = axios.create({
  baseURL: '/api/v1',
  headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }
});

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token');
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('token');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);
```

### Page Component Pattern

```jsx
// Each page follows this structure:
function SomePage() {
  const [data, setData] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    api.get('/endpoint')
      .then(res => setData(res.data))
      .catch(err => setError(err.message))
      .finally(() => setLoading(false));
  }, []);

  if (loading) return <Skeleton />;
  if (error) return <ErrorMessage message={error} />;

  return (
    <div>
      <PageHeader title="..." actions={[...]} />
      <Content data={data} />
    </div>
  );
}
```

## Design Decisions

### Why Zustand over Redux/Context

- Zero boilerplate, ~1 KB gzipped
- No providers/wrappers needed
- Works outside React components (useful for Axios interceptors)
- TypeScript-friendly without extra setup

### Why localStorage for Tokens

- Simple, works across tabs
- User chose this in discussion
- Mitigated by: httpOnly for session cookie, CSRF via Sanctum

### Why Vite Proxy in Development

- Avoids CORS issues during development
- Single origin for the browser (no cross-origin requests)
- Production: nginx serves both SPA and API from same domain

### Why shadcn/ui

- Accessible by default (WAI-ARIA compliant)
- Customizable with Tailwind CSS
- No runtime CSS-in-JS overhead
- Components copy-pasted (no dependency lock-in)

## File Structure

```
frontend/
├── index.html
├── package.json
├── vite.config.js
├── tailwind.config.js
├── postcss.config.js
├── components.json              # shadcn/ui config
├── src/
│   ├── main.jsx                 # Entry point
│   ├── App.jsx                  # Router setup
│   ├── index.css                # Tailwind imports + base styles
│   ├── lib/
│   │   └── utils.js             # cn() utility for shadcn/ui
│   ├── api/
│   │   ├── client.js            # Axios instance + interceptors
│   │   ├── auth.js              # login, register, logout, forgotPassword
│   │   ├── documents.js         # list, upload, get, delete
│   │   ├── projects.js          # CRUD
│   │   ├── styleProfiles.js     # CRUD, import, export
│   │   └── analysis.js          # analyze, getViolations
│   ├── stores/
│   │   └── authStore.js         # Zustand: token, user, login, logout
│   ├── hooks/
│   │   ├── useAuth.js           # Convenience hook for auth store
│   │   └── useDocuments.js      # Document CRUD hook
│   ├── components/
│   │   ├── ui/                  # shadcn/ui components (button, input, card, etc.)
│   │   ├── layout/
│   │   │   ├── AppLayout.jsx    # Main layout wrapper
│   │   │   ├── Sidebar.jsx      # Navigation sidebar
│   │   │   └── Header.jsx       # Top header with user menu
│   │   ├── documents/
│   │   │   ├── DocumentCard.jsx # Document preview card
│   │   │   └── FileUpload.jsx   # Drag-and-drop file upload
│   │   └── common/
│   │       ├── PageHeader.jsx   # Page title + actions
│   │       ├── Skeleton.jsx     # Loading skeleton
│   │       └── ErrorMessage.jsx # Error display
│   └── pages/
│       ├── auth/
│       │   ├── Login.jsx
│       │   ├── Register.jsx
│       │   └── ForgotPassword.jsx
│       ├── Dashboard.jsx
│       ├── documents/
│       │   ├── DocumentList.jsx
│       │   ├── DocumentUpload.jsx
│       │   └── DocumentView.jsx
│       └── style-profiles/
│           ├── StyleProfileList.jsx
│           └── StyleProfileEditor.jsx
```

## Backend Changes

### config/cors.php

```php
'allowed_origins' => ['http://localhost:5173'],
'supports_credentials' => true,
```

### config/sanctum.php

```php
'stateful' => ['localhost:5173', 'localhost:8000', '127.0.0.1:8000'],
```

## UI Design Language

### Color Palette

- Primary: `#2563EB` (blue-600) — action buttons, links, active states
- Background: `#F8FAFC` (slate-50) — page background
- Surface: `#FFFFFF` — cards, panels
- Text: `#0F172A` (slate-900) — primary text
- Muted: `#64748B` (slate-500) — secondary text
- Border: `#E2E8F0` (slate-200) — dividers
- Error: `#DC2626` (red-600) — destructive actions, errors
- Success: `#16A34A` (green-600) — success states
- Warning: `#D97706` (amber-600) — warnings

### Typography

- Font: Inter (system-ui fallback)
- Headings: font-semibold, slate-900
- Body: font-normal, slate-700
- Muted: font-normal, slate-500

### Spacing

- Page padding: 24px (p-6)
- Card padding: 16px (p-4)
- Gap between cards: 16px (gap-4)
- Sidebar width: 256px (w-64)

### Components

- Cards: white bg, subtle shadow, rounded-lg
- Buttons: rounded-md, font-medium, transition
- Inputs: rounded-md, border, focus:ring-2 focus:ring-blue-500
- Tables: striped rows, hover highlight
- Modals: centered, overlay, rounded-lg
